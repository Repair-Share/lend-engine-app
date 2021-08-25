<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ItemListControllerTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testLoanPeriodFees()
    {
        $this->helpers->setSettingValue('default_loan_days', 14);

        $itemName = basename(str_replace('\\', '/', __CLASS__)) . ' ' . rand();

        $itemID = $this->helpers->createItem(
            $this->client,
            $itemName,
            [
                'maxLoanDays' => 7,
                'loanFee'     => 10
            ]
        );

        $crawler = $this->client->request(
            'GET',
            '/products?search=' . $itemName
        );

        $this->assertContains(
            '£10.00for7days',
            $this->helpers->compressHtml($crawler->html())
        );

        $crawler = $this->client->request(
            'GET',
            '/product/' . $itemID
        );

        $this->assertContains(
            '£10.00for7days',
            $this->helpers->compressHtml($crawler->html())
        );
    }
}