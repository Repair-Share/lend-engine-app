<?php

namespace Tests\AppBundle\Controller;

use AppBundle\Entity\Contact;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MembershipControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testListAction()
    {
        $crawler = $this->client->request('GET', '/admin/membership/list');
        $this->assertContains('Memberships', $crawler->html());
    }

    public function testSubscriptionAction()
    {
        $crawler = $this->client->request('GET', '/admin/membership/contact/3');
        $this->assertContains('New membership for', $crawler->html());

        $form = $crawler->filter('form[name="membership"]')->form(array(
            'membership[membershipType]' => 1,
            'membership[price]'          => 15,
            'membership[paymentMethod]'  => 1,
            'membership[paymentAmount]'  => 15
        ),'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Membership saved', $crawler->html());

        $membershipId = (int)$crawler->filter('#active-membership-id')->text();
        $this->assertGreaterThan(0, $membershipId);

        return $membershipId;
    }

    /**
     * @depends testSubscriptionAction
     * @param $membershipId
     */
    public function testMembershipCancel($membershipId)
    {
        $this->client->request('GET', '/admin/membership/'.$membershipId.'/cancel');

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Membership cancelled', $crawler->html());
    }

}