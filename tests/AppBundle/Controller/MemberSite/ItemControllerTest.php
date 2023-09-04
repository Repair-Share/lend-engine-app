<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ItemControllerTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testArchivedItems()
    {
        // Create an item
        $itemName = 'Archived Item ' . uniqid();
        $itemId   = $this->helpers->createItem($this->client, 'Arhived Item ' . $itemName);

        // Check on the list (should be there)
        $crawler = $this->unAuthClient->request(
            'GET',
            '/product/' . $itemId
        );
        $this->assertContains($itemName, $crawler->html());

        // Archive it
        $crawler = $this->client->request(
            'GET',
            '/admin/item/' . $itemId
        );

        $this->assertContains($itemName, $crawler->html());

        $postArray = [
            'item' => [
                'name'          => $itemName,
                'sku'           => $crawler->filter('#item_sku')->attr('value'),
                'serial'        => $crawler->filter('#item_serial')->attr('value'),
                'note'          => $crawler->filter('#item_note')->attr('value'),
                'loanFee'       => $crawler->filter('#item_loanFee')->attr('value'),
                'maxLoanDays'   => $crawler->filter('#item_maxLoanDays')->attr('value'),
                'depositAmount' => $crawler->filter('#item_depositAmount')->attr('value'),
                'condition'     => 1,
                'keywords'      => $crawler->filter('#item_keywords')->attr('value'),
                'priceCost'     => $crawler->filter('#item_priceCost')->attr('value'),
                'priceSell'     => $crawler->filter('#item_priceSell')->attr('value'),
                'brand'         => $crawler->filter('#item_brand')->attr('value'),

                'showOnWebsite' => false // Archive it
            ]
        ];

        $form = $crawler->filter('form[name="item"]')->form($postArray, 'POST');

        $this->client->submit($form);

        $crawler = $this->client->followRedirect();

        $this->assertContains($itemName, $crawler->html());

        // Check on with logged in client -> must be there
        $crawler = $this->client->request(
            'GET',
            '/product/' . $itemId
        );

        $this->assertContains($itemName, $crawler->html());

        // Check on the list as not logged in (must NOT be there)
        $crawler = $this->unAuthClient->request(
            'GET',
            '/product/' . $itemId
        );

        $crawler = $this->unAuthClient->followRedirect();

        $this->assertContains('This item listing is not available for members.', $crawler->html());

    }
}