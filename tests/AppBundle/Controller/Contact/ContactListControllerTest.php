<?php

namespace Tests\AppBundle\Controller\Contact;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ContactListControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testContactListAction()
    {
        $crawler = $this->client->request('GET', '/admin/contact/list');
        $this->assertEquals(1, $crawler->filter('#data-table-contact')->count());
    }

    public function testTableListAction()
    {
        $this->client->request('GET', '/admin/dt/contact/list?draw=1&start=0&length=50');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains('test@email.com', $this->client->getResponse()->getContent());
    }
}