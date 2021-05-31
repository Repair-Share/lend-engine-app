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
     * @param null $contactName
     * @return int
     */
    public function createContact(Client $client, $contactName = null)
    {
        $crawler = $client->request('GET', '/admin/contact');
        $this->assertContains('Add a new contact', $crawler->html());

        if (!$contactName) {
            $contactName = "Test ".microtime(true);
        }

        $form = $crawler->filter('form[name="contact"]')->form(array(
            'contact[firstName]' => $contactName,
            'contact[lastName]'  => "Contact",
            'contact[email]'     => microtime(true).'@email.com',
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
     * @param int $membershipTypeId
     */
    public function subscribeContact(Client $client, $contactId, $membershipTypeId = 1)
    {
        // Subscribe a contact to membership type 1
        $crawler = $client->request('GET', '/member/subscribe?membershipTypeId='.$membershipTypeId.'&c='.$contactId);
        $this->assertContains('Subscription payment', $crawler->html());

        $form = $crawler->filter('form[name="membership_subscribe"]')->form(array(
            'membership_subscribe[membershipType]' => $membershipTypeId,
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
     * @return null|string
     */
    public function createEvent(Client $client, $maxAttendees = 10)
    {
        $crawler = $client->request('GET', '/admin/event');
        $this->assertContains('Create a new event', $crawler->html());

        $eventTitle = "Test event title ".microtime(true);

        $date = new \DateTime();
        $form = $crawler->filter('form[name="event"]')->form(array(
            'event[title]' => $eventTitle,
            'event[date]' => $date->format("Y-m-d"),
            'event[timeFrom]' => '09:00 am',
            'event[timeTo]'   => '11:00 am',
            'event[maxAttendees]' => $maxAttendees,
            'event[price]' => '15',
            'event[description]' => "It's the event description.",
        ),'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $this->assertContains("This event is not published yet.", $crawler->html());
        $this->assertContains($eventTitle, $crawler->html());
        $this->assertEquals("15", $crawler->filter('#event_price')->attr('value'));
        $this->assertEquals($maxAttendees, $crawler->filter('#event_maxAttendees')->attr('value'));
        $this->assertEquals("09:00", $crawler->filter('#event_timeFrom')->attr('value'));
        $this->assertEquals("11:00", $crawler->filter('#event_timeTo')->attr('value'));

        // Confirm the creator has been added as attendee
        $this->assertContains('hello@lend-engine.com', $crawler->html());

        $eventId = $crawler->filter('#eventIdForTest')->attr('value');

        // Publish it
        $client->request('GET', "/admin/event/{$eventId}/publish");
        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $statusText = $crawler->filter('#eventStatusLabel')->text();
        $this->assertEquals($statusText, 'LIVE');

        return $eventId;
    }

    /**
     * @param Client $client
     * @param $contactId
     * @param array $itemIds
     * @param string $action
     * @return int
     */
    public function createLoan(Client $client, $contactId, $itemIds = [1000], $action = 'checkout', $dayOffset = 0)
    {
        // Add items to the basket
        $today = new \DateTime();

        if ($dayOffset) {
            $today = $today->modify($dayOffset . " day");
        }

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
                'date_to'   => $today->modify("+1 day")->format("Y-m-d"),
                'time_to'   => $today->modify("+1 day")->format("17:00:00")
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

    /**
     * @param Client $client
     * @param $loanId
     * @return bool
     */
    public function checkoutLoan(Client $client, $loanId)
    {
        $crawler = $client->request('GET', '/loan/'.$loanId);
        $this->assertContains("loan/{$loanId}", $crawler->html()); // in the link to delete the pending loan

        // Check it out
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'loan_check_out[paymentMethod]' => 1,
            'loan_check_out[paymentAmount]' => 16.00,
        ),'POST');
        $client->submit($form);
        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        return true;
    }

    /**
     * @param Client $client
     * @param $loanId
     * @return bool
     */
    public function checkinLoan(Client $client, $loanId)
    {
        $crawler = $client->request('GET', '/loan/'.$loanId);
        $rowId = $crawler->filter('.btn_checkin')->first()->attr('data-loan-row-id');

        $crawler = $client->request('GET', '/loan-row/'.$rowId.'/check-in/');
        $form = $crawler->filter('form[name="item_check_in"]')->form(array(
            'item_check_in[notes]' => "",
            'item_check_in[feeAmount]' => 0
        ),'POST');
        $client->submit($form);
        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $loanStatusText = $crawler->filter('#loanStatusLabel')->text();
        $this->assertEquals($loanStatusText, 'Closed');

        return true;
    }

    /**
     * Extract the entity ID for a selected value (eg a setup list for recently created thing)
     * @param Client $client
     * @param $url
     * @param $name
     * @return int|null
     */
    public function getEntityId(Client $client, $url, $name)
    {
        $crawler = $client->request('GET', $url);

        $rows = $crawler->filter('tr')->each(function($node) {
            $id  = $node->attr('id');
            $text  = $node->text();
            return compact('id', 'text');
        });

        $createdId = null;
        foreach ($rows AS $row) {
            if (strstr($row['text'], $name)) {
                $createdId = (int)str_replace('tr', '', $row['id']);
            }
        }

        return $createdId;
    }

    /**
     * @param  Client  $client
     * @return null|string
     */
    public function createSite(Client $client)
    {
        $crawler = $client->request('GET', '/admin/site/list');
        $this->assertContains('Sites', $crawler->html());
        $this->assertContains('Add a site', $crawler->html());

        $button = $crawler
            ->filter('a:contains("Add a site")') // find all buttons with the text "Add a site"
            ->eq(0) // select the first button in the list
            ->link() // and click it
        ;

        // Opened a modal
        $crawler = $client->click($button);
        $this->assertContains('Add a new site', $crawler->html());

        $button = $crawler
            ->filter('a:contains("Add opening hours")') // find all buttons with the text "Add a site"
            ->eq(0) // select the first button in the list
            ->link() // and click it
        ;

        $siteName = 'Test site ' . uniqid();

        $form = $crawler->filter('form[name="site"]')->form(array(
            'site[name]'      => $siteName,
            'site[post_code]' => 'PO12345',

            'site[siteOpenings][0][weekDay]'  => '1',
            'site[siteOpenings][0][timeFrom]' => '0900',
            'site[siteOpenings][0][timeTo]'   => '1700'
        ), 'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);
        $crawler = $client->followRedirect();

        $this->assertContains("Site saved.", $crawler->html());
        $this->assertContains($siteName, $crawler->html());

        return $this->getSiteId($client, $siteName);
    }

    /**
     * Extract the site ID for a selected value (eg a setup list for recently created thing)
     * @param  Client  $client
     * @param $siteName
     * @return int|null
     */
    public function getSiteId(Client $client, $siteName)
    {
        $siteID = null;

        $crawler = $client->request('GET', '/admin/site/list');

        $crawler->filter('.site-id')->each(function ($node) use ($siteName, &$siteID) {
            $id    = $node->attr('id');
            $value = $node->attr('value');

            if (trim($siteName) === trim($value)) {
                $siteID = str_replace('siteIdForTest', '', $id);
            }

        });

        return $siteID;
    }

    /**
     * @param  Client  $client
     * @param $siteID
     * @param $date
     * @param $timeFrom
     * @param $timeTo
     * @param $opened
     * @return null|string
     */
    public function addSiteOpeningHours(Client $client, $siteID, $date, $timeFrom, $timeTo, $opened)
    {
        $crawler = $client->request('GET', '/admin/site/' . $siteID . '/event/list');
        $this->assertContains('Custom opening hours', $crawler->html());

        $button = $crawler
            ->filter('a:contains("Add new")') // find all buttons with the text "Add a site"
            ->eq(0) // select the first button in the list
            ->link() // and click it
        ;

        $crawler = $client->click($button);
        $this->assertContains('Add custom hours for', $crawler->html());

        $form = $crawler->filter('form[name="opening_hours"]')->form(array(
            'opening_hours[date]'     => $date->format('D M d Y'),
            'opening_hours[type]'     => ($opened ? 'o' : 'c'),
            'opening_hours[timeFrom]' => $timeFrom,
            'opening_hours[timeTo]'   => $timeTo,
            'opening_hours[site]'     => $siteID
        ), 'POST');

        $client->submit($form);

        $this->assertTrue($client->getResponse() instanceof RedirectResponse);

        $crawler = $client->followRedirect();
        $this->assertContains('Saved.', $crawler->html());
        $this->assertContains($date->format('l j F Y'), $crawler->html());
    }

}