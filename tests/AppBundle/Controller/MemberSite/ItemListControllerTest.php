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

    public function testSearchResultsWithOrTerms()
    {
        $this->helpers->setSettingValue('search_terms', '1');

        $itemName1 = 'aaaa bbbb cccc ' . rand();
        $itemName2 = 'aaaa bbbb ' . rand();
        $itemName3 = 'bbbb cccc ' . rand();

        $this->helpers->createItem(
            $this->client,
            $itemName1 . ' ' . rand()
        );

        $this->helpers->createItem(
            $this->client,
            $itemName2 . ' ' . rand()
        );

        $this->helpers->createItem(
            $this->client,
            $itemName3 . ' ' . rand()
        );

        foreach (
            [
                'aaaa bbbb cccc',
                'aaaa bbbb',
                'bbbb',
                'aaaa bbbb bosch'
            ] as $searchParam
        ) {

            $crawler = $this->client->request(
                'GET',
                '/products?search=' . $searchParam
            );

            $this->assertContains(
                $itemName1,
                $crawler->html()
            );

            $this->assertContains(
                $itemName2,
                $crawler->html()
            );

            $this->assertContains(
                $itemName3,
                $crawler->html()
            );

        }

        foreach (
            [
                'aaaa',
                'aaa'
            ] as $searchParam
        ) {

            $crawler = $this->client->request(
                'GET',
                '/products?search=' . $searchParam
            );

            $this->assertContains(
                $itemName1,
                $crawler->html()
            );

            $this->assertContains(
                $itemName2,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName3,
                $crawler->html()
            );

        }

        foreach (
            [
                'cccc',
                'ccc'
            ] as $searchParam
        ) {

            $crawler = $this->client->request(
                'GET',
                '/products?search=' . $searchParam
            );

            $this->assertContains(
                $itemName1,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName2,
                $crawler->html()
            );

            $this->assertContains(
                $itemName3,
                $crawler->html()
            );

        }

        foreach (
            [
                'aaaax',
            ] as $searchParam
        ) {

            $crawler = $this->client->request(
                'GET',
                '/products?search=' . $searchParam
            );

            $this->assertNotContains(
                $itemName1,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName2,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName3,
                $crawler->html()
            );

        }

    }

    public function testSearchResultsWithAndTerms()
    {
        $this->helpers->setSettingValue('search_terms', '0');

        $itemName1 = 'one two three ' . rand();
        $itemName2 = 'one two ' . rand();
        $itemName3 = 'two three ' . rand();

        $this->helpers->createItem(
            $this->client,
            $itemName1 . ' ' . rand()
        );

        $this->helpers->createItem(
            $this->client,
            $itemName2 . ' ' . rand()
        );

        $this->helpers->createItem(
            $this->client,
            $itemName3 . ' ' . rand()
        );

        foreach (
            [
                'one',
                'one two',
                'two one'
            ] as $searchParam
        ) {

            $crawler = $this->client->request(
                'GET',
                '/products?search=' . $searchParam
            );

            $this->assertContains(
                $itemName1,
                $crawler->html()
            );

            $this->assertContains(
                $itemName2,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName3,
                $crawler->html()
            );

        }

        foreach (
            [
                'one three',
                'three one'
            ] as $searchParam
        ) {

            $crawler = $this->client->request(
                'GET',
                '/products?search=' . $searchParam
            );

            $this->assertContains(
                $itemName1,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName2,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName3,
                $crawler->html()
            );

        }

        foreach (
            [
                'two three',
                'three two'
            ] as $searchParam
        ) {

            $crawler = $this->client->request(
                'GET',
                '/products?search=' . $searchParam
            );

            $this->assertContains(
                $itemName1,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName2,
                $crawler->html()
            );

            $this->assertContains(
                $itemName3,
                $crawler->html()
            );

        }

        foreach (
            [
                'two'
            ] as $searchParam
        ) {

            $crawler = $this->client->request(
                'GET',
                '/products?search=' . $searchParam
            );

            $this->assertContains(
                $itemName1,
                $crawler->html()
            );

            $this->assertContains(
                $itemName2,
                $crawler->html()
            );

            $this->assertContains(
                $itemName3,
                $crawler->html()
            );

        }

        foreach (
            [
                'one two four'
            ] as $searchParam
        ) {

            $crawler = $this->client->request(
                'GET',
                '/products?search=' . $searchParam
            );

            $this->assertNotContains(
                $itemName1,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName2,
                $crawler->html()
            );

            $this->assertNotContains(
                $itemName3,
                $crawler->html()
            );

        }
    }

    public function testSearchResultsWithSpaces()
    {
        $itemName = 'one two three ' . rand();

        $this->helpers->createItem(
            $this->client,
            $itemName . ' ' . rand()
        );

        $crawler = $this->client->request(
            'GET',
            '/products?search=+one+two++' // Searching with spaces
        );

        $this->assertNotContains(
            '[Syntax Error]',
            $crawler->html()
        );
    }
}