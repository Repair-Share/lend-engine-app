<?php

namespace Tests\AppBundle\Controller\Item;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ItemCopyControllerTest extends AuthenticatedControllerTest
{

    public function testCopyProduct()
    {
        $itemId = $this->helpers->createItem($this->client, "CopyItem");

        $this->client->request('GET', '/admin/item/'.$itemId.'/copy');
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains('CopyItem', $crawler->html());
    }

}