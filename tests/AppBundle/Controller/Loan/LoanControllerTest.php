<?php

namespace Tests\AppBundle\Controller\Loan;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanControllerTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Relies on loan 1000 created at testBasketAddItem() test
     */
    public function testShowLoanAction()
    {
        $crawler = $this->client->request('GET', '/loan/1000');
        $this->assertEquals(1, $crawler->filter('#page-loan-title')->count());
    }

    /**
     * Assumes the settings are "charge when items are checked out", not when reservation is placed
     */
    public function testLoanWithContactBalanceAndFee()
    {
        // Create an item with a deposit amount
        $itemId = $this->helpers->createItem($this->client, null, 3.00);

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId, 2);

        // Create a new loan
        $loanId = $this->helpers->createLoan($this->client, $contactId, $itemId);
        $crawler = $this->client->request('GET', '/loan/'.$loanId);

        // Set the loan amount
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'row_fee['.$itemId.']' => 2.99,
        ),'POST');

        // Change the form action to save rather than check out
        $url = $this->client->getContainer()->get('router')->generate('loan_save', ['loanId' => $loanId], true);
        $node = $form->getNode(0);
        $node->setAttribute('action', $url);
        $this->client->submit($form);
        $crawler = $this->client->followRedirect();

        // Add a fee
        $feeNote = "Test fee ".rand();
        $form = $crawler->filter('form[name="add_fee"]')->form(array(
            'feeAmount' => 1.50,
            'feeReason' => $feeNote,
        ),'POST');
        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // Loan total should now be 4.49
        // 1.50 already charged
        // 2.99 due to pay
        $basketBalance = (float)$crawler->filter('#basketBalanceAmount')->text();
        $this->assertEquals(2.99, $basketBalance);

        // Contact balance is 2.00 (set when adding credit)
        // Amount due to pay should be 2.49
        $subtotal = (float)$crawler->filter('#loanSubtotalAmount')->text();
        $this->assertEquals(2.49, $subtotal);

        // Add deposits
        $deposits = (float)$crawler->filter('#loanDepositsAmount')->text();
        $this->assertEquals(3.00, $deposits);

        // Take payment and complete the process
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'loan_check_out[paymentMethod]' => 1,
            'loan_check_out[paymentAmount]' => 5.49,
        ),'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains("Checked out loan", $crawler->html());

        $basketTotal = (float)$crawler->filter('#basketTotalAmount')->text();
        $this->assertEquals(4.49, $basketTotal);

        $contactBalance = (float)$crawler->filter('#contactBalanceAmount')->text();
        $this->assertEquals(0.00, $contactBalance);

    }

}