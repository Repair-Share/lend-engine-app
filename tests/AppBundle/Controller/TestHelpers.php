<?php

/**
 * A class to create data for functional tests
 */

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;

class TestHelpers extends AuthenticatedControllerTest
{

    /**
     * @param Client $client
     * @param null $itemName
     * @param array $options
     * @return int
     */
    public function createItem(Client $client, $itemName = null, $options = [])
    {
        if (!$itemName) {
            $itemName = "Item ".rand();
        }

        $depositAmount = null;
        if (isset($options['depositAmount'])) {
            $depositAmount = $options['depositAmount'];
        }

        $priceSell = 2.99;
        if (isset($options['priceSell'])) {
            $priceSell = $options['priceSell'];
        }

        $loanFee = 1.50;
        if (isset($options['loanFee'])) {
            $loanFee = $options['loanFee'];
        }

        if (isset($options['type'])) {
            $type = $options['type'];
        } else {
            $type = 'loan';
        }

        $crawler = $client->request('GET', '/admin/item?type='.$type);
        $this->assertContains('Add a ', $crawler->html());

        $form = $crawler->filter('form[name="item"]')->form(array(
            'item[inventoryLocation]' => "2",
            'item[name]'     => $itemName,
            'item[sku]'      => "SKU-".rand(),
            'item[loanFee]'  => $loanFee,
            'item[maxLoanDays]' => 4,
            'item[condition]'   => 1,
            'item[keywords]'    => 'Comma, separated, keywords',
            'item[priceCost]'   => 1.99,
            'item[priceSell]'   => $priceSell,
            'item[depositAmount]' => $depositAmount,
            'item[brand]'       => "DEWALT",
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);

        $crawler = $client->followRedirect();

        $itemId = (int)$crawler->filter('#itemIdForTest')->attr('value');
        $this->assertGreaterThan(0, $itemId);

        return $itemId;
    }

    /**
     * @param Client $client
     * @return int
     */
    public function createContact(Client $client)
    {
        $crawler = $client->request('GET', '/admin/contact');
        $this->assertContains('Add a new contact', $crawler->html());

        $rand = rand(1,1000);
        $form = $crawler->filter('form[name="contact"]')->form(array(
            'contact[firstName]' => "Test ".$rand,
            'contact[lastName]'  => "Contact",
            'contact[email]'     => 'basket'.$rand.'@email.com',
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $contactId = (int)$crawler->filter('#contact-id')->text();
        $this->assertGreaterThan(0, $contactId);

        return $contactId;
    }

    /**
     * @param Client $client
     * @param $contactId
     */
    public function subscribeContact(Client $client, $contactId)
    {
        // Subscribe a contact to membership type 1
        $crawler = $client->request('GET', '/member/subscribe?membershipTypeId=1&c='.$contactId);
        $this->assertContains('Subscription payment', $crawler->html());

        $form = $crawler->filter('form[name="membership_subscribe"]')->form(array(
            'membership_subscribe[membershipType]' => 1,
            'membership_subscribe[price]'          => 15,
            'membership_subscribe[paymentMethod]'  => 1,
            'membership_subscribe[paymentAmount]'  => 15
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $this->assertContains('Subscribed OK', $crawler->html());

        // Confirm the user now has a membership
        $membershipId = (int)$crawler->filter('#active-membership-id')->text();
        $this->assertGreaterThan(0, $membershipId);
    }

    /**
     * @param Client $client
     * @param $contactId
     * @param float $amount
     * @return mixed|null|string
     */
    public function addCredit(Client $client, $contactId, $amount = 100.00)
    {
        $crawler = $client->request('GET', '/member/add-credit?c='.$contactId);
        $this->assertContains('Add credit', $crawler->html());

        $form = $crawler->filter('form[name="add_credit"]')->form(array(
            'add_credit[paymentMethod]' => 1,
            'add_credit[paymentAmount]' => $amount,
            'add_credit[paymentNote]'   => 'Payment note',
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);

        $crawler = $client->request('GET', '/admin/contact/'.$contactId);
        $this->assertContains('Charges and Payments', $crawler->html());
        $paymentId = $crawler->filter('.refund-button')->attr('id');
        $paymentId = str_replace('id-', '', $paymentId);

        return $paymentId;
    }

    /**
     * @param Client $client
     */
    public function createEvent(Client $client)
    {
        $crawler = $client->request('GET', '/admin/event');
        $this->assertContains('Create a new event', $crawler->html());

        $date = new \DateTime();
        $form = $crawler->filter('form[name="event"]')->form(array(
            'event[title]' => "Test event title ".$date->format("Y-m-d H:i:s"),
            'event[date]' => $date->format("Y-m-d"),
            'event[timeFrom]' => '09:00 am',
            'event[timeTo]'   => '11:00 am',
            'event[maxAttendees]' => '10',
            'event[price]' => '15',
            'event[description]' => "This it's an great Stuff.",
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $this->assertContains('Test event title', $crawler->html());
    }

    /**
     * @param Client $client
     * @param $contactId
     * @param array $itemIds
     * @param string $action
     * @return int
     */
    public function createLoan(Client $client, $contactId, $itemIds = [], $action = 'checkout')
    {
        // Add items to the basket
        $today = new \DateTime();

        $fees = [];
        foreach ($itemIds AS $itemId) {
            // Each item has to be a loan item!

            $fees[$itemId] = 10.00;

            $params = [
                'contactId' => $contactId,
                'from_site' => 1,
                'to_site'   => 1,
                'date_from' => $today->format("Y-m-d"),
                'time_from' => $today->format("09:00:00"),
                'date_to'   => $today->format("Y-m-d"),
                'time_to'   => $today->format("17:00:00")
            ];
            $client->request('POST', '/basket/add/'.$itemId.'?contactId='.$contactId, $params);

            $this->assertTrue($client->getResponse() instanceof RedirectResponse);
            $crawler = $client->followRedirect();

            $this->assertContains('basketDetails', $crawler->html());
            $this->assertContains("product/{$itemId}", $crawler->html()); // contains the item
        }

        // Confirm the loan (will be set to pending)
        $params = [
            'action' => $action,
            'row_fee' => $fees
        ];
        $client->request('POST', '/basket/confirm', $params);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $loanId = (int)$crawler->filter('#loanIdForTest')->attr('value');
        $this->assertGreaterThan(0, $loanId);

        return $loanId;
    }

    /**
     * @param Client $client
     * @param $loanId
     * @param int $locationId
     * @param $itemId
     * @param $qty
     */
    public function addStockItemToLoan(Client $client, $loanId, $locationId = 2, $itemId, $qty)
    {
        $crawler = $client->request('GET', '/product/'.$itemId);
        $form = $crawler->filter('form[name="add_stock_items"]')->form([
            'add_qty['.$locationId.']' => $qty,
            'loan_id' => $loanId
        ],'POST');
        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $this->assertContains('basketDetails', $crawler->html());
        $this->assertContains("product/{$itemId}", $crawler->html()); // contains the item
    }

    /**
     * @param Client $client
     * @param $locationId
     * @param $itemId
     * @param $qty
     * @return bool
     */
    public function addInventory(Client $client, $locationId, $itemId, $qty)
    {
        $params = [
            'add_location' => $locationId,
            'add_qty'   => $qty
        ];
        $client->request('POST', '/admin/item/'.$itemId.'/inventory', $params);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();
        $this->assertContains('Inventory updated', $crawler->html());
        return true;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function setSettingValue($key, $value)
    {
        $kernel = $this->bootKernel();

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $kernel->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository("AppBundle:Setting");

        /** @var \AppBundle\Entity\Setting $setting */
        if (!$setting = $repo->findOneBy(['setupKey' => $key])) {
            $setting = new Setting();
            $setting->setSetupKey($key);
        }

        // Change the setting to 'charge when placing reservation'
        $setting->setSetupValue($value);
        $em->persist($setting);
        $em->flush();

        return true;
    }

}