<?php

namespace Tests\AppBundle\Controller\Item;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ItemMoveControllerTest extends AuthenticatedControllerTest
{
    public function testMoveAction()
    {
        $crawler = $this->client->request('GET', '/admin/item/1001/move/');
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

        $this->assertContains('Test item name', $crawler->html());
        $this->assertContains('Item location has been updated', $crawler->html());
    }
}