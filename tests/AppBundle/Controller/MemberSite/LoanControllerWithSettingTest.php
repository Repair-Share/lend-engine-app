<?php

namespace Tests\AppBundle\Controller\MemberSite;

use AppBundle\Entity\Setting;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanControllerWithSettingTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * A separate test for when the settings are 'charge amount when reservation is CREATED'
     */
    public function testLoanWhenChargedAtReservation()
    {
        $this->helpers->setSettingValue('charge_daily_fee', 1);

        // Create an item with a deposit amount
        $itemId = $this->helpers->createItem($this->client);

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId, 12.00);

        // Create a new loan
        // Due to the setting change the amount of 10.00 will be charged to account now
        $loanId = $this->helpers->createLoan($this->client, $contactId, $itemId, 'reservation');
        $crawler = $this->client->request('GET', '/loan/'.$loanId);

        $contactBalance = (float)$crawler->filter('#contactBalanceAmount')->text();
        $this->assertEquals(2.00, $contactBalance);

        // Add a fee
        $feeNote = "Test fee ".rand();
        $form = $crawler->filter('form[name="add_fee"]')->form(array(
            'feeAmount' => 1.50,
            'feeReason' => $feeNote,
        ),'POST');
        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // Loan total should now be 11.50
        // 11.50 already charged
        $basketBalance = (float)$crawler->filter('#basketBalanceAmount')->text();
        $this->assertEquals(0.00, $basketBalance);

        $subtotal = (float)$crawler->filter('#loanSubtotalAmount')->text();
        $this->assertEquals(0.00, $subtotal);

        $toPay = (float)$crawler->filter('#loanToPayAmount')->text();
        $this->assertEquals(0.00, $toPay);

        // Complete the process, no payment needed
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(

        ),'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains("Checked out loan", $crawler->html());

        $basketTotal = (float)$crawler->filter('#basketTotalAmount')->text();
        $this->assertEquals(11.50, $basketTotal);

        // Confirm we've not charged the customer
        $contactBalance = (float)$crawler->filter('#contactBalanceAmount')->text();
        $this->assertEquals(0.50, $contactBalance);

        // Reset the setting back afterwards
        $this->helpers->setSettingValue('charge_daily_fee', 0);
    }

}