<?php

// tests/AppBundle/Controller/Website/ItemListControllerTest.php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ItemListControllerTest extends WebTestCase
{
    public function testListProductsAction()
    {
        $client = static::createClient();

        // Website view
        $crawler = $client->request('GET', '/products');

        // Item list page shows
        $this->assertEquals(1, $crawler->filter('#site-item-list')->count());

        // Contains at least one item
        $this->assertGreaterThan(0, $crawler->filter('.site-item-tile')->count());
    }
}