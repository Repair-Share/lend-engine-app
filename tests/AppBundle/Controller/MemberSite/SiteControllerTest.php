<?php

// tests/AppBundle/Controller/Website/SiteControllerTest.php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SiteControllerTest extends WebTestCase
{
    public function testIndexAction()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(1, $crawler->filter('#site-welcome')->count());
    }

    public function testHomeAction()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/welcome');

        $this->assertEquals(1, $crawler->filter('#site-welcome')->count());
    }
}