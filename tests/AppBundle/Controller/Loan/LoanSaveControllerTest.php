<?php

namespace Tests\AppBundle\Controller\Loan;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanSaveControllerTest extends AuthenticatedControllerTest
{

    public function testSaveChangesToLoan()
    {
        // Create a new loan item
        $itemName = "Test item for LoanSaveControllerTest".rand();
        $itemId = $this->helpers->createItem($this->client, $itemName, ['type' => 'loan']);

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId);

        // Create a new loan
        $loanId = $this->helpers->createLoan($this->client, $contactId, [$itemId]);
        $crawler = $this->client->request('GET', '/loan/'.$loanId);

        $loanRowId = (int)$crawler->filter('.loanRow')->attr('id');

        // Change the item amount
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'row_fee['.$loanRowId.']' => 2.99,
        ),'POST');

        // Change the form action to save rather than check out
        $url = $this->client->getContainer()->get('router')->generate('loan_save', ['loanId' => $loanId], true);
        $node = $form->getNode(0);
        $node->setAttribute('action', $url);

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Saved changes OK', $crawler->html());
    }

}