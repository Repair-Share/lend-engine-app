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
        $loanId = $this->helpers->createLoan($this->client, $contactId, [$firstItemId]);

        // Add an item to the loan
        $today = new \DateTime();
        $params = [
            'contactId' => $contactId,
            'from_site' => 1,
            'to_site'   => 1,
            'item_fee'   => 0.99,
            'date_from' => $today->format("Y-m-d"),
            'time_from' => $today->format("09:00:00"),
            'date_to'   => $today->modify("+1 day")->format("Y-m-d"),
            'time_to'   => $today->modify("+1 day")->format("17:00:00")
        ];
        $this->client->request('POST', '/basket/add/'.$secondItemId.'?contactId='.$contactId.'&active-loan='.$loanId, $params);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Item 1', $crawler->html());
        $this->assertContains('Item 2', $crawler->html());
        $this->assertContains('10.99', $crawler->html());
        $this->assertContains($secondItemId . ' 5:00 pm', $crawler->html());
    }

    public function testAddItemToCheckedOutLoan()
    {
        // Create items required
        $firstItemId  = $this->helpers->createItem($this->client, "Item 1 / " . rand(1, 10000));
        $secondItemId = $this->helpers->createItem($this->client, "Item 2 / " . rand(1, 10000));

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId);

        // Create a new loan with first item
        $loanId = $this->helpers->createLoan($this->client, $contactId, [$firstItemId]);

        // Open the loan to add an item to this existing loan
        $crawler = $this->client->request('GET', '/loan/' . $loanId . '/add-loan-item');
        $crawler = $this->client->followRedirect();
        $this->assertContains("Choose an item to add to loan {$loanId}", $crawler->html());

        // Get the loan
        $crawler = $this->client->request('GET', '/loan/' . $loanId);
        $this->assertContains("loan/{$loanId}", $crawler->html()); // We are on the loan page

        // Check out the loan
        $form = $crawler->filter('form[name="loan_check_out"]')->form(array(
            'loan_check_out[paymentMethod]' => 1,
            'loan_check_out[paymentAmount]' => 10.00,
        ), 'POST');
        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // Add an item to the existing, checked out loan
        $today  = new \DateTime();
        $params = [
            'contactId' => $contactId,
            'from_site' => 1,
            'to_site'   => 1,
            'item_fee'  => 0.99,
            'date_from' => $today->format("Y-m-d"),
            'time_from' => $today->format("09:00:00"),
            'date_to'   => $today->modify("+1 day")->format("Y-m-d"),
            'time_to'   => $today->modify("+1 day")->format("17:00:00")
        ];

        $this->client->request(
            'POST',
            '/basket/add/' . $secondItemId . '?contactId=' . $contactId . '&active-loan=' . $loanId,
            $params
        );

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // Must fail
        $this->assertContains("You can't add an item to a loan when", $crawler->html());
    }

}