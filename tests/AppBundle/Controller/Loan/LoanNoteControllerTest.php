<?php

namespace Tests\AppBundle\Controller\Loan;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanNoteControllerTest extends AuthenticatedControllerTest
{

    public function testAddNote()
    {
        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId);

        // Create a new loan
        $loanId = $this->helpers->createLoan($this->client, $contactId);
        $crawler = $this->client->request('GET', '/loan/'.$loanId);

        // Add a note
        $form = $crawler->filter('form[name="add_note"]')->form(array(
            'loanNotes' => "A test note added",
        ),'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('A test note added', $crawler->html());
    }

}