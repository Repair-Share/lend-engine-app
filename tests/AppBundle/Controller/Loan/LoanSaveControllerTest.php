<?php

namespace Tests\AppBundle\Controller\Loan;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanSaveControllerTest extends AuthenticatedControllerTest
{

    public function testSaveChangesToLoan()
    {
        // Create a new loan
        $loanId = $this->helpers->createLoan($this->client);
        $crawler = $this->client->request('GET', '/loan/'.$loanId);

        // Add a note
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'row_fee[1000]' => 2.99,
        ),'POST');

        // Change the form action to save rather than check out
        $url = $this->client->getContainer()->get('router')->generate('loan_save', ['loanId' => $loanId], true);
        $node = $form->getNode(0);
        $node->setAttribute('action', $url);

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Saved OK', $crawler->html());
    }

}