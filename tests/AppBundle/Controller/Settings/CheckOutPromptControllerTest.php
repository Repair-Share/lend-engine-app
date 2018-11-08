<?php

namespace Tests\AppBundle\Controller\Settings;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class CheckOutPromptListControllerTest extends AuthenticatedControllerTest
{
    public function testCheckOutPromptListAction()
    {
        $crawler = $this->client->request('GET', '/admin/checkOutPrompt/list');
        $this->assertEquals(1, $crawler->filter('#CheckOutPrompt-list')->count());
    }
}