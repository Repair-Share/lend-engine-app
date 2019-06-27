<?php

/**
 * A class to create data for functional tests
 */

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;

class TestHelpers extends AuthenticatedControllerTest
{

    /**
     * @param Client $client
     * @param null $itemName
     * @param float $depositAmount
     * @return int
     */
    public function createItem(Client $client, $itemName = null, $depositAmount = 0.00)
    {
        if (!$itemName) {
            $itemName = "Item ".rand();
        }

        $crawler = $client->request('GET', '/admin/item?typeId=33');
        $this->assertContains('Add a new item', $crawler->html());

        $form = $crawler->filter('form[name="item"]')->form(array(
            'item[inventoryLocation]' => "2",
            'item[name]'     => $itemName,
            'item[sku]'      => "SKU-".rand(),
            'item[loanFee]'  => 1.50,
            'item[maxLoanDays]' => 4,
            'item[condition]'   => 1,
            'item[keywords]'    => 'Comma, separated, keywords',
            'item[priceCost]'   => 1.99,
            'item[priceSell]'   => 2.99,
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
        $crawler = $client->request('GET', '/admin/membership/contact/'.$contactId);
        $this->assertContains('New membership for', $crawler->html());

        $form = $crawler->filter('form[name="membership"]')->form(array(
            'membership[membershipType]' => 1,
            'membership[price]'          => 15,
            'membership[paymentMethod]'  => 1,
            'membership[paymentAmount]'  => 15
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $this->assertContains('Membership saved', $crawler->html());

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

        $form = $crawler->filter('form[name="payment"]')->form(array(
            'paymentMethod' => 1,
            'paymentAmount' => $amount,
            'paymentNote'   => 'Payment note',
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
     * @param int $itemId
     * @return int
     */
    public function createLoan(Client $client, $contactId, $itemId = 1000)
    {
        // Add an item to the basket
        $today = new \DateTime();
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

        // Confirm the loan (will be set to pending)
        $params = [
            'action' => 'checkout',
            'row_fee' => [
                $itemId => 10.00
            ]
        ];
        $client->request('POST', '/basket/confirm', $params);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $loanId = (int)$crawler->filter('#loanIdForTest')->attr('value');
        $this->assertGreaterThan(0, $loanId);

        return $loanId;
    }

}