<?php

namespace Tests\AppBundle\Controller\Loan;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanFeeControllerTest extends AuthenticatedControllerTest
{

    public function testAddFee()
    {
        // Create a new loan
        $loanId = $this->helpers->createLoan($this->client);
        $crawler = $this->client->request('GET', '/loan/'.$loanId);

        // Add a note
        $feeNote = "Test fee ".rand();
        $form = $crawler->filter('form[name="add_fee"]')->form(array(
            'feeAmount' => 1.50,
            'feeReason' => $feeNote,
        ),'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Added fee OK', $crawler->html());
        $this->assertContains($feeNote, $crawler->html());
    }

}