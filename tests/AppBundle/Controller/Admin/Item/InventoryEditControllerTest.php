<?php

namespace Tests\AppBundle\Controller\Admin\Item;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class InventoryEditControllerTest extends AuthenticatedControllerTest
{
    public function testUpdateInventory()
    {
        // Create a stock item
        $stockItemName = "Stock item ".rand();
        $stockItemId = $this->helpers->createItem($this->client, $stockItemName, [
            'type' => 'stock'
        ]);

        // Add stock
        $crawler = $this->client->request('GET', "/admin/item/{$stockItemId}/inventory");
        $this->assertContains("Inventory for ".$stockItemName, $crawler->html());

        $form = $crawler->filter('form[name="inventory_edit"]')->form(array(
            'add_qty'      => 10,
            'add_location' => 2
        ),'POST');

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        $this->assertContains($stockItemName, $crawler->html());
        $this->assertContains("10 in stock", $crawler->html());

        // Remove stock
        $crawler = $this->client->request('GET', "/admin/item/{$stockItemId}/inventory");
        $form = $crawler->filter('form[name="inventory_edit"]')->form(array(
            'quantity[2]'  => 4
        ),'POST');

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();
        $this->assertContains("4 in stock", $crawler->html());

    }

}