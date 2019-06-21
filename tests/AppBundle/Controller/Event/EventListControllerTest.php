<?php

namespace Tests\AppBundle\Controller\Event;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class EventListControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Create an event
     */
    public function testListAction()
    {
        $crawler = $this->client->request('GET', '/admin/event/list');
        $this->assertContains('Event settings', $crawler->html());
    }

}