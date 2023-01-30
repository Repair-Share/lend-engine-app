<?php

namespace Tests\AppBundle\Controller\Loan;

use AppBundle\Entity\PaymentMethod;
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
        $loanId = $this->helpers->createLoan($this->client, 2, [$itemId]);

        // Go to it
        $crawler = $this->client->request('GET', '/loan/'.$loanId);
        $this->assertContains("loan/{$loanId}", $crawler->html()); // We are on the loan page

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

    public function testLoanCheckInWithRefund()
    {
        // Create a new item with deposit amount
        $itemName = "Check in test item with deposit " . rand();
        $itemId   = $this->helpers->createItem($this->client, $itemName, ['depositAmount' => 10]);

        // Add a loan
        $loanId = $this->helpers->createLoan($this->client, 2, [$itemId]);

        // Go to it
        $crawler = $this->client->request('GET', '/loan/' . $loanId);
        $this->assertContains("loan/{$loanId}", $crawler->html()); // We are on the loan page

        // Check it out
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'loan_check_out[paymentMethod]' => 1,
            'loan_check_out[paymentAmount]' => 10.00,
        ), 'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $rowId = $crawler->filter('.btn_checkin')->first()->attr('data-loan-row-id');

        $crawler = $this->client->request('GET', '/loan-row/' . $rowId . '/check-in/');
        $this->assertContains('Check in "' . $itemName . '"', $crawler->html());
        $this->assertContains('A deposit of <strong>£10.00</strong> was taken for this item.', $crawler->html());

        // Open the refund modal
        $paymentId = $crawler->filter('.refund-button')->attr('data-payment-id');
        $crawler   = $this->client->request(
            'GET',
            '/admin/refund?id=' . $paymentId . '&amount=10.00&goToCheckInItem=' . $rowId
        );

        $this->assertContains('This screen is used when you are giving money back to a member.', $crawler->html());
        $this->assertContains('Create Debit to LE account', $crawler->html());

        $form = $crawler->filter('form[name="refund"]')->form([
            'refund[amount]'       => 10,
            'refund[note]'         => 'Debit LE account test',
            'refund[debitAccount]' => false
        ], 'POST');

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('The deposit has been refunded.', $crawler->html());

        // Check the payments
        $crawler = $this->client->request(
            'GET',
            '/member/payments'
        );

        // Not perfect, but better than nothing
        $this->assertContains(
            PaymentMethod::PAYMENT_METHOD_DEBIT_ACCOUNT,
            $crawler->html()
        );

    }

}