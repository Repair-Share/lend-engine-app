<?php

namespace Tests\AppBundle\Controller\Item;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ChooseItemTypeControllerTest extends AuthenticatedControllerTest
{
    public function testChooseItemTypeAction()
    {
        $crawler = $this->client->request('GET', '/admin/item_type');
        $this->assertContains('Choose item type', $crawler->html());
        $this->assertContains('Choose a type', $crawler->html());
    }
}