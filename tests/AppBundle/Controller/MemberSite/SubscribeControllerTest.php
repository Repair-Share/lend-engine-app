<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class SubscribeControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testSubscribe()
    {
        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        $crawler = $this->client->request('GET', '/member/subscribe?membershipTypeId=2&c=' . $contactId);
        $this->assertContains('Subscription payment', $crawler->html());

        // Cash payment
        $form = $crawler->filter('form[name="membership_subscribe"]')->form(array(
            'membership_subscribe[membershipType]' => 2,
            'membership_subscribe[price]'          => 0,
        ), 'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Subscribed OK', $crawler->html()); // flash message
        $this->assertContains('member until', $crawler->html()); // admin tools on member site

    }

    public function testPaymentMembership()
    {
        $this->helpers->setupFakeStripe();
        $this->helpers->setSettingValue('pay_membership_at_pickup', 1);

        // Add a new membership
        $membershipTypeId = $this->helpers->addMembership('Annual', 10, 365);

        // Choose membership
        $crawler = $this->client->request(
            'GET',
            '/member/subscribe?membershipTypeId=' . $membershipTypeId . '&c=&itemId=/'
        );

        $this->assertContains('Subscription payment', $crawler->html());
        $this->assertSame(sizeof($crawler->filter('.pay-membership-at-pickup')), 1);
    }
}