<?php

namespace Tests\AppBundle\Controller\Event;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class EventBookingTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Book a member onto an event (as admin)
     */
    public function testEventBookingAsAdmin()
    {
        // Create a contact
        $contactName = "EventBookingTest ".microtime(true);
        $contactId = $this->helpers->createContact($this->client, $contactName);

        // Create event
        $eventId = $this->helpers->createEvent($this->client);

        // Browse as created user
        $this->client->request('GET', '/switch-to/'.$contactId.'?go=events');
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();
        $this->assertContains("You are now browsing as {$contactName}", $crawler->html());

        // Open the event modal
        $crawler = $this->client->request('GET', '/event/preview/'.$eventId);

        // You are booking XX onto this event
        $this->assertContains($contactName, $crawler->html());

        $form = $crawler->filter('form[name="event_booking"]')->form(array(
            'c' => $contactId,
            'event_booking[paymentAmount]' => 15,
            'event_booking[paymentMethod]' => 1
        ),'POST');

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains("You're booked in. See you soon", $crawler->html());

        // Confirm it appears on their bookings list
        $crawler = $this->client->request('GET', '/member/my-events');

        // We are looking at the right user details
        $this->assertContains($contactName, $crawler->filter('#browseAsUser')->text());

        $this->assertContains("9:00 am - 11:00 am", $crawler->html());
        $this->assertContains("event/preview/{$eventId}", $crawler->html());

        $this->assertContains("currentUserId  = {$contactId}", $crawler->html());
        $this->assertContains("accountBalance = 0.00", $crawler->html());

    }

}