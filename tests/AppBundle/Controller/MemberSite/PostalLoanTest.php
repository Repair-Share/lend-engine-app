<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class PostalLoanTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testPostalLoan()
    {
        $this->helpers->setSettingValue('postal_loans', 1);
        $this->helpers->setSettingValue('postal_item_fee', 1.50);
        $this->helpers->setSettingValue('postal_loan_fee', 2);

        // Create a loan item
        $loanItemName = "Test loan item PostalLoanTest ".rand();
        $loanItemId = $this->helpers->createItem($this->client, $loanItemName, [
            'type' => 'loan',
            'loanFee' => 1.99
        ]);

        // Create a service item
        $shippingItemName = "Test shipping PostalLoanTest ".rand();
        $itemId = $this->helpers->createItem($this->client, $shippingItemName, [
            'type' => 'service'
        ]);

        $this->helpers->setSettingValue('postal_shipping_item', $itemId);

        $session = new Session(new MockFileSessionStorage());
        $session->set('time_zone', 'Europe/London');

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId);

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
        $this->client->request('POST', '/basket/add/'.$loanItemId.'?qty=1&contactId='.$contactId, $params);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // Item added ok
        $this->assertContains($loanItemName, $crawler->html());
        $this->assertContains("product/{$loanItemId}", $crawler->html());

        // Postal loan settings turn these on:
        $this->assertContains("Send by post", $crawler->html());
        $this->assertContains("1.50 per item", $crawler->html());
        $this->assertContains("2.00 per loan", $crawler->html());

        // Set it as postal then save
        $form = $crawler->filter('form[name="form_basket"]')->form(array(
            'collect_from' => 'post'
        ),'POST');
        $url = $this->client->getContainer()->get('router')->generate('basket_save', [], true);
        $node = $form->getNode(0);
        $node->setAttribute('action', $url);
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // Basket should be updated
        $this->assertContains('Items will be shipped to', $crawler->html());

        // Confirm the loan
        $params = [
            'action' => 'checkout',
            'row_fee' => [$loanItemId => 1.99]
        ];
        $this->client->request('POST', '/basket/confirm', $params);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // Shipping item should be added
        $this->assertContains($shippingItemName, $crawler->html());
        $this->assertContains('Items will be shipped to', $crawler->html());

        // Check it out
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'loan_check_out[paymentMethod]' => 1,
            'loan_check_out[paymentAmount]' => 5.49,
        ),'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // Loan screen contains the right data
        $this->assertContains('3.50', $crawler->html()); // shipping
        $this->assertContains('5.49', $crawler->html()); // loan total
        $this->assertContains('Send by post', $crawler->html()); // warning box with address
        $loanStatusText = $crawler->filter('#loanStatusLabel')->text();
        $this->assertEquals($loanStatusText, 'On loan');

        // Confirm we can check the loan in
        $rowId = $crawler->filter('.btn_checkin')->first()->attr('data-loan-row-id');

        $crawler = $this->client->request('GET', '/loan-row/'.$rowId.'/check-in/');
        $this->assertContains('Check in "'.$loanItemName.'"', $crawler->html());

        $form = $crawler->filter('form[name="item_check_in"]')->form(array(
            'item_check_in[notes]' => "",
            'item_check_in[feeAmount]' => null
        ),'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $loanStatusText = $crawler->filter('#loanStatusLabel')->text();
        $this->assertEquals($loanStatusText, 'Closed');

        // Turn the setting off again
        $this->helpers->setSettingValue('postal_loans', 0);

    }

}