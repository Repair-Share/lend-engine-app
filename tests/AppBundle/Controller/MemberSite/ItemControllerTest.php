<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ItemControllerTest extends WebTestCase
{
    public function testListProductsAction()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/product/1000');

        // Item page shows
        $this->assertEquals(1, $crawler->filter('#item-core')->count());

        // Product has loaded into the page
        $this->assertEquals(1, $crawler->filter('#item-detail-description')->count());
    }
}