<?php

namespace Tests\AppBundle\Controller\Loan;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanCheckInControllerTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testLoanCheckIn()
    {
        // Create a new item
        $itemName = "Check in test item ".rand();
        $itemId = $this->helpers->createItem($this->client, $itemName);

        // Add a loan
        $loanId = $this->helpers->createLoan($this->client, 2, $itemId);

        // Go to it
        $crawler = $this->client->request('GET', '/loan/'.$loanId);
        $this->assertContains("loan/{$loanId}", $crawler->html()); // in the link to delete the pending loan

        // Check it out
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'loan_check_out[paymentMethod]' => 1,
            'loan_check_out[paymentAmount]' => 10.00,
        ),'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $rowId = $crawler->filter('.btn_checkin')->first()->attr('data-loan-row-id');

        $crawler = $this->client->request('GET', '/loan-row/'.$rowId.'/check-in/');
        $this->assertContains('Check in "'.$itemName.'"', $crawler->html());

        $form = $crawler->filter('form[name="item_check_in"]')->form(array(
            'item_check_in[notes]' => "Check in note text",
            'item_check_in[contact]' => 2, // assign to staff
            'item_check_in[feeAmount]' => 1.29, // fee
        ),'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $loanStatusText = $crawler->filter('#loanStatusLabel')->text();
        $this->assertEquals($loanStatusText, 'Closed');

        $this->assertContains("Checked in", $crawler->html()); // flash message
        $this->assertContains("Check in note text", $crawler->html()); // note added
        $this->assertContains("Check-in fee for ".$itemName, $crawler->html()); // fee added
        $this->assertContains("1.29", $crawler->html()); // fee added
    }

}