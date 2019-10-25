<?php

namespace Tests\AppBundle\Controller\Item;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ItemControllerTest extends AuthenticatedControllerTest
{
    public function testItemAction()
    {
        $crawler = $this->client->request('GET', '/admin/item?sectorId=33');
        $this->assertContains('Add a new item', $crawler->html());

        $form = $crawler->filter('form[name="item"]')->form(array(
            'item[inventoryLocation]' => "3", // repair
            'item[name]'     => "Test item name",
            'item[sku]'      => "SKU-HERE",
            'item[serial]'   => '7978134691348',
            'item[note]'     => 'Short description',
            'item[loanFee]'  => 1.50,
            'item[maxLoanDays]' => 4,
            'item[condition]'   => 1,
            'item[keywords]'    => 'Comma, separated, keywords',
            'item[priceCost]'   => 1.99,
            'item[priceSell]'   => 2.99,
            'item[brand]'       => "Sony",
        ),'POST');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);

        $crawler = $this->client->followRedirect();

        $this->assertContains('Test item name', $crawler->html());
    }
}