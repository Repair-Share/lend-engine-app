<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class BasketControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testBasketAddItem()
    {
        $session = new Session(new MockFileSessionStorage());
        $session->set('time_zone', 'Europe/London');

        // Create an item
        $loanItemName = "BasketControllerTest ".rand();
        $loanItemId = $this->helpers->createItem($this->client, $loanItemName, ['type' => 'loan']);

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId);

        // Add an item to the basket
        $today = new \DateTime();
        $tomorrow = $today->modify("+1 day");
        $params = [
            'contactId' => $contactId, // the one set up in ContactControllerTest
            'from_site' => 1,
            'to_site'   => 1,
            'date_from' => $today->format("Y-m-d"),
            'time_from' => $today->format("09:00:00"),
            'date_to'   => $tomorrow->format("Y-m-d"),
            'time_to'   => $tomorrow->format("17:00:00")
        ];
        $this->client->request('POST', '/basket/add/'.$loanItemId.'?qty=1&contactId='.$contactId, $params);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('basketDetails', $crawler->html());

        // Check time zone OK
        $this->assertContains($today->format("j F")." 9:00 am", $crawler->html());
        $this->assertContains($tomorrow->format("j F")." 5:00 pm", $crawler->html());

        // Confirm the reservation
        $params = [
            'action' => 'reserve',
            'row_fee' => [
                $loanItemId => 10.00
            ]
        ];
        $this->client->request('POST', '/basket/confirm', $params);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Reservation created by', $crawler->html());

        $loanStatusText = $crawler->filter('#loanStatusLabel')->text();
        $this->assertEquals($loanStatusText, 'Reserved');

        // Confirm that the time zone was saved properly
        $this->assertContains($today->format("j F")." 9:00 am", $crawler->html());
        $this->assertContains($tomorrow->format("j F")." 5:00 pm", $crawler->html());

        $loanId = (int)$crawler->filter('#loanIdForTest')->attr('value');

        // Cancel the reservation
        $this->client->request('GET', "/member/booking/{$loanId}/cancel");
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $loanStatusText = $crawler->filter('#loanStatusLabel')->text();
        $this->assertEquals($loanStatusText, 'Cancelled');

    }

}