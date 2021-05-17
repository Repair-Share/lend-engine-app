<?php

namespace Tests\AppBundle\Controller\MemberSite\Event;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class SiteEventViewControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testMaxAttendees()
    {
        $maxAttendees = 2;

        // Create an event with max 2 attendees
        $eventId = $this->helpers->createEvent($this->client, $maxAttendees);

        for ($i = 1; $i < $maxAttendees + 1; $i++) {

            $assertFull = false;

            if ($i === $maxAttendees) {
                $assertFull = true;
            }

            // Create contact
            $contactId = $this->helpers->createContact($this->client);
            $this->helpers->subscribeContact($this->client, $contactId);
            $this->helpers->addCredit($this->client, $contactId);

            // Switch to contact
            $crawler = $this->client->request('GET', '/switch-to/' . $contactId);

            // Open the event
            $crawler = $this->client->request('GET', '/event/preview/' . $eventId);

            if ($assertFull) {

                $this->assertContains('This event is now full.', $crawler->html());

            } else {

                // Check the book now button
                $this->assertContains('id="buttonBook"', $crawler->html());

                // Check the check in link
                $this->assertContains('Check in', $crawler->html());

                // Event should not full yet
                $this->assertNotContains('This event is now full.', $crawler->html());

                // Check in as a guest
                $crawler = $this->client->request('GET', '/event/' . $eventId . '/book?check_in=true');

            }

        }

    }

}