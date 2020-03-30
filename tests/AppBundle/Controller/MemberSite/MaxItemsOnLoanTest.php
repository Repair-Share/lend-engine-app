<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class MaxItemsOnLoanTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testMaxItemsOnLoan()
    {
        // Create first item
        $loanItemName = "MaxItemsOnLoanTest ".rand();
        $loanItemId = $this->helpers->createItem($this->client, $loanItemName, ['type' => 'loan']);

        $secondItemName = "MaxItemsOnLoanTest ".rand();
        $secondItemId = $this->helpers->createItem($this->client, $secondItemName, ['type' => 'loan']);

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Create a membership type
        $crawler = $this->client->request('GET', '/admin/membershipType');

        $membershipTypeName = "MaxItemsOnLoanTest ".microtime(true);
        $form = $crawler->filter('form[name="membership_type_form"]')->form(array(
            'membership_type_form[name]' => $membershipTypeName,
            'membership_type_form[price]' => 0,
            'membership_type_form[maxItems]' => 1,
            'membership_type_form[creditLimit]' => 100
        ),'POST');

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains("Add a membership type", $crawler->html());
        $this->assertContains($membershipTypeName, $crawler->html());

        $createdId = $this->helpers->getEntityId($this->client, '/admin/membershipType/list', $membershipTypeName);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId, $createdId);

        // Add and check out loan
        $loanId = $this->helpers->createLoan($this->client, $contactId, [$loanItemId]);
        $this->helpers->checkoutLoan($this->client, $loanId);

        // Try to add another item
        $today = new \DateTime();
        $tomorrow = $today->modify("+1 day");
        $params = [
            'contactId' => $contactId,
            'from_site' => 1,
            'to_site'   => 1,
            'date_from' => $today->format("Y-m-d"),
            'time_from' => $today->format("09:00:00"),
            'date_to'   => $tomorrow->format("Y-m-d"),
            'time_to'   => $tomorrow->format("17:00:00")
        ];
        $this->client->request('POST', '/basket/add/'.$secondItemId.'?qty=1&contactId='.$contactId, $params);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('The maximum for your membership is 1', $crawler->html());

        // Check in the first item to make it available for other tests
        $this->helpers->checkinLoan($this->client, $loanId);
    }

}