<?php

namespace Tests\AppBundle\Controller\Loan;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanWithStockItemTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testLoanWithStockItem()
    {
        $locationId = 2;
        $contactId = $this->helpers->createContact($this->client);
        $this->helpers->subscribeContact($this->client, $contactId);

        // Create a new loan item
        $itemName = "Check in loan item ".rand();
        $itemId = $this->helpers->createItem($this->client, $itemName, ['type' => 'loan']);

        // Create a new stock item
        $stockItemName = "Check in stock item ".rand();
        $stockItemId = $this->helpers->createItem($this->client, $stockItemName, [
            'type' => 'stock',
            'priceSell' => 3.00
        ]);

        // Add inventory of the stock item to default stock location
        $this->helpers->addInventory($this->client, $locationId, $stockItemId, 10);

        // Add a loan with the loan item
        $loanId = $this->helpers->createLoan($this->client, $contactId, [$itemId]);

        // Add the stock item
        $qty = 2;
        $this->helpers->addStockItemToLoan($this->client, $loanId, $locationId, $stockItemId, $qty);

        // Go to it
        $crawler = $this->client->request('GET', '/loan/'.$loanId);
        $this->assertContains("loan/{$loanId}", $crawler->html()); // in the link to delete the pending loan

        // Check it out
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'loan_check_out[paymentMethod]' => 1,
            'loan_check_out[paymentAmount]' => 16.00,
        ),'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // Checked out OK with correct totals
        $this->assertContains("16.00", $crawler->html());
        $loanStatusText = $crawler->filter('#loanStatusLabel')->text();
        $this->assertEquals($loanStatusText, 'On loan');

        // Check in the first item
        $rowId = $crawler->filter('.btn_checkin')->first()->attr('data-loan-row-id');
        $crawler = $this->client->request('GET', '/loan-row/'.$rowId.'/check-in/');
        $this->assertContains('Check in "'.$itemName.'"', $crawler->html());

        $form = $crawler->filter('form[name="item_check_in"]')->form(array(
            'item_check_in[notes]' => "Check in note text",
            'return_qty['.$stockItemId.']' => 1, // return stock item
        ),'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $loanStatusText = $crawler->filter('#loanStatusLabel')->text();
        $this->assertEquals($loanStatusText, 'Closed');

        $this->assertContains("Checked in", $crawler->html()); // flash message
        $this->assertContains("Check in note text", $crawler->html()); // note added
        $this->assertContains("Returned 1 Check in stock item", $crawler->html());
        $this->assertContains("13.00", $crawler->html()); // charged to account
        $this->assertContains("accountBalance = 3.00", $crawler->html()); // account balance JS

        // Check the stock levels
        $crawler = $this->client->request('GET', '/admin/item/'.$stockItemId);
        $this->assertContains("9 in stock", $crawler->html());
    }

}