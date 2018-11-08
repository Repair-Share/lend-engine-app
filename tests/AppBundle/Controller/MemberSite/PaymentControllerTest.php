<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class PaymentControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testPaymentAction()
    {
        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        $crawler = $this->client->request('GET', '/member/add-credit?c='.$contactId);
        $this->assertContains('Add credit', $crawler->html());

        // Cash payment
        $form = $crawler->filter('form[name="payment"]')->form(array(
            'paymentMethod' => 1,
            'paymentAmount' => 10.99,
            'paymentNote'   => 'Payment note',
        ),'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Payment recorded OK', $crawler->html());

        $crawler = $this->client->request('GET', '/admin/contact/'.$contactId);
        $this->assertContains('10.99', $crawler->html());

    }
}