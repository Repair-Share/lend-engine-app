<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MemberSiteItemControllerTest extends WebTestCase
{
    public function testListProductsAction()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/product/1000');

        // Test that the item page shows
        $this->assertContains('Test item', $crawler->html());
    }
}