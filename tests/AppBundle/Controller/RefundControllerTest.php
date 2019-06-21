<?php

namespace Tests\AppBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;
use Symfony\Component\HttpFoundation\Session\Session;

class RefundControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Create an event
     */
    public function testRefundAction()
    {
        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        // Add credit
//        $paymentId = $this->helpers->addCredit($this->client, $contactId);

        // Refund the credit
//        $crawler = $this->client->request('GET', '/admin/event/list');
//        $this->assertContains('Event settings', $crawler->html());
    }

}