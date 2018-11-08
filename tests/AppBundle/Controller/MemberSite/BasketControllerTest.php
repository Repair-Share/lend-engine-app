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

        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Subscribe them
        $this->helpers->subscribeContact($this->client, $contactId);

        // Add credit
        $this->helpers->addCredit($this->client, $contactId);

        // Add an item to the basket
        $today = new \DateTime();
        $params = [
            'contactId' => $contactId, // the one set up in ContactControllerTest
            'from_site' => 1,
            'to_site'   => 1,
            'date_from' => $today->format("Y-m-d 09:00:00"),
            'date_to'   => $today->format("Y-m-d 17:00:00")
        ];
        $this->client->request('POST', '/basket/add/1000?contactId='.$contactId, $params);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('basketDetails', $crawler->html());

        // Check time zone OK
        $this->assertContains($today->format("d F")." 9:00 am", $crawler->html());
        $this->assertContains($today->format("d F")." 5:00 pm", $crawler->html());

        // Confirm the reservation
        $params = [
            'row_fee' => [
                1000 => 10.00
            ]
        ];
        $this->client->request('POST', '/basket/confirm', $params);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $basketAmount = $crawler->filter('#basketTotalAmount')->text();
        $this->assertEquals('10.00', $basketAmount);

        // Confirm that the time zone was saved properly
        $this->assertContains($today->format("d F")." 9:00 am", $crawler->html());
        $this->assertContains($today->format("d F")." 5:00 pm", $crawler->html());

    }

}