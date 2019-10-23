<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanExtendControllerTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testExtendLoan()
    {
        // Create a new item
        $itemId = $this->helpers->createItem($this->client, "Extension test item ".rand());

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

        $this->assertContains("Change return date", $crawler->html());
        $rowId = $crawler->filter('.btn_extend')->first()->attr('data-loan-row-id');

        $crawler = $this->client->request('GET', '/product/1000?extend='.$rowId);
        $this->assertContains("Choose a new return date", $crawler->html());

        // Add a day to the loan
        $itemDueInAt = $crawler->filter('#itemDueInAt')->attr('value');
        $return = new \DateTime($itemDueInAt);
        $return->modify("+1 day");

        $form = $crawler->filter('form[name="loan_extend"]')->form(array(
            'new_return_date' => $return->format("Y-m-d"),
            'new_return_time' => $return->format("H:i:00"),
            'new_return_site_id' => 1,
            'extension_fee_amount' => 2.35,
            'loan_extend[paymentMethod]' => 1,
            'loan_extend[paymentAmount]' => 2.35,
        ),'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains("Loan return date was updated OK", $crawler->html()); // flash message
        $this->assertContains("Updated return date", $crawler->html()); // note added
        $this->assertContains("2.35", $crawler->html()); // fee added
    }

}