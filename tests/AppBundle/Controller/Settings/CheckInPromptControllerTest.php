<?php

namespace Tests\AppBundle\Controller\Settings;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class CheckInPromptListControllerTest extends AuthenticatedControllerTest
{
    public function testCheckInPromptListAction()
    {
        $crawler = $this->client->request('GET', '/admin/checkInPrompt/list');
        $this->assertEquals(1, $crawler->filter('#CheckInPrompt-list')->count());
    }
}