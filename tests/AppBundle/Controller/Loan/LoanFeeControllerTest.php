<?php

namespace Tests\AppBundle\Controller\Loan;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanFeeControllerTest extends AuthenticatedControllerTest
{

    public function testAddFee()
    {
        // Create a new loan item
        $itemName = "Test item for LoanFeeControllerTest ".rand();
        $itemId = $this->helpers->createItem($this->client, $itemName, ['type' => 'loan']);

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId, 2);

        // Create a new loan
        $loanId = $this->helpers->createLoan($this->client, $contactId, [$itemId]);
        $crawler = $this->client->request('GET', '/loan/'.$loanId);

        // Add a fee
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