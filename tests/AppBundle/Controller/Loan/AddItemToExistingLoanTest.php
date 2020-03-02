<?php

namespace Tests\AppBundle\Controller\Loan;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class AddItemToExistingLoanTest extends AuthenticatedControllerTest
{

    public function testAddItemToExistingLoan()
    {
        // Create items required
        $firstItemId = $this->helpers->createItem($this->client, "Item 1 / ".rand(1,10000));
        $secondItemId = $this->helpers->createItem($this->client, "Item 2 / ".rand(1,10000));

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId);

        // Create a new loan with first item
        $loanId = $this->helpers->createLoan($this->client, $contactId, $firstItemId);

        // Add an item to the loan
        $today = new \DateTime();
        $params = [
            'contactId' => $contactId,
            'from_site' => 1,
            'to_site'   => 1,
            'item_fee'   => 0.99,
            'date_from' => $today->format("Y-m-d"),
            'time_from' => $today->format("09:00:00"),
            'date_to'   => $today->format("Y-m-d"),
            'time_to'   => $today->format("17:00:00")
        ];
        $this->client->request('POST', '/basket/add/'.$secondItemId.'?contactId='.$contactId.'&active-loan='.$loanId, $params);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Item 1', $crawler->html());
        $this->assertContains('Item 2', $crawler->html());
        $this->assertContains('10.99', $crawler->html());
    }

}