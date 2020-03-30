<?php

namespace Tests\AppBundle\Controller\Event;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class EventControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Create an event
     */
    public function testEventEdit()
    {
        $session = new Session(new MockFileSessionStorage());
        $session->set('time_zone', 'Europe/London');

        // Create event and verify the details are saved
        $eventId = $this->helpers->createEvent($this->client);
    }

}