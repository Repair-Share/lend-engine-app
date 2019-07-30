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
        $crawler = $this->client->request('GET', '/choose_membership?c=3');
        $this->assertContains('Choose a membership type', $crawler->html());

        $crawler = $this->client->request('GET', '/member/subscribe?membershipTypeId=1&c=3');
        $this->assertContains('Subscription payment', $crawler->html());

        $form = $crawler->filter('form[name="membership_subscribe"]')->form(array(
            'membership_subscribe[membershipType]' => 1,
            'membership_subscribe[price]'          => 15,
            'membership_subscribe[paymentMethod]'  => 1,
            'membership_subscribe[paymentAmount]'  => 15
        ),'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('Subscribed OK', $crawler->html());

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