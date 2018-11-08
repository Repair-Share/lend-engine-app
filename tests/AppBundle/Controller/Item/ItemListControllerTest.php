<?php

namespace Tests\AppBundle\Controller\Item;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ItemListControllerTest extends AuthenticatedControllerTest
{
    public function testListAction()
    {
        $crawler = $this->client->request('GET', '/admin/item/list');
        $this->assertEquals(1, $crawler->filter('#item-list-body')->count());
    }

    public function testInventoryListAction()
    {
        $this->client->request('GET', '/admin/dt/item/list?draw=1&start=0&length=50');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains('Test item', $this->client->getResponse()->getContent());
    }
}