<?php

namespace Tests\AppBundle\Controller\Admin\Item;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ItemMoveControllerTest extends AuthenticatedControllerTest
{
    public function testMoveAction()
    {
        $crawler = $this->client->request('GET', '/admin/item/move/1001');
        $this->assertContains('Move / assign', $crawler->html());

        $form = $crawler->filter('form[name="item_move"]')->form(array(
            'item_move[location]' => "2",
            'item_move[contact]'  => "2",
            'item_move[notes]'    => "Unit test's move notes",
            'item_move[cost]'     => '1.50',
        ),'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);
        $crawler = $this->client->followRedirect();

        // We're sent back to the admin item list
        $this->assertContains('Items', $crawler->html());
        $this->assertContains('item(s) updated OK', $crawler->html());
    }
}