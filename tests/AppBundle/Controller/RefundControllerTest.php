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

        // Add credit (defaults to amount = 100.00)
        $paymentId = $this->helpers->addCredit($this->client, $contactId);

        // Refund the payment
        $crawler = $this->client->request('GET', '/admin/refund?id='.$paymentId.'&amount=100');
        $this->assertContains('Refund a payment', $crawler->html());

        $note = "Refund note ".rand();

        $form = $crawler->filter('form[name="refund"]')->form(array(
            'refund[amount]' => 5,
            'refund[paymentId]' => $paymentId,
            'refund[paymentMethod]'   => 1,
            'refund[note]'   => $note,
        ),'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);

        // Confirm it shows on the payment list for the contact
        $crawler = $this->client->request('GET', '/admin/contact/'.$contactId);
        $this->assertContains($note, $crawler->html());

        // Confirm account balance is recalculating
        $this->assertContains("95.00", $crawler->html());
    }

}