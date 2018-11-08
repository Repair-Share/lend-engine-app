<?php

// tests/AppBundle/Controller/Website/UserControllerTest.php

namespace Tests\AppBundle\Controller\MemberSite;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class UserControllerTest extends AuthenticatedControllerTest
{
    public function testProfile()
    {
        $crawler = $this->client->request('GET', '/profile/');
        $this->assertContains('My account', $crawler->html());
    }

    public function testLoans()
    {
        $crawler = $this->client->request('GET', '/member/loans');
        $this->assertContains('Loans', $crawler->html());
    }

    public function testPayments()
    {
        $crawler = $this->client->request('GET', '/member/payments');
        $this->assertContains('Payments', $crawler->html());
    }

    public function testRegistrationWelcome()
    {
        $crawler = $this->client->request('GET', '/member/welcome');
        $this->assertEquals(1, $crawler->filter('#registration-welcome')->count());
    }
}