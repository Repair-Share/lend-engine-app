<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class LoanControllerTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testShowLoanAction()
    {
        $crawler = $this->client->request('GET', '/loan/1000');
        $this->assertEquals(1, $crawler->filter('#page-loan-title')->count());
    }
}