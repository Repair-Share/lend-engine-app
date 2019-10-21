<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ChooseMembershipControllerTest extends AuthenticatedControllerTest
{

    public function setUp()
    {
        parent::setUp();
    }

    public function testChooseMembership()
    {
        // Create a contact
        $contactId = $this->helpers->createContact($this->client);

        $crawler = $this->client->request('GET', '/choose_membership?c='.$contactId);
        $this->assertContains('Choose a membership type', $crawler->html());
        $this->assertContains('Temporary', $crawler->html()); // fixtures membership type
    }
}