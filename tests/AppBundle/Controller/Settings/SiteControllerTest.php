<?php

namespace Tests\AppBundle\Controller\Settings;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class SiteListControllerTest extends AuthenticatedControllerTest
{
    public function testSiteListAction()
    {
        $crawler = $this->client->request('GET', '/admin/site/list');
        $this->assertEquals(1, $crawler->filter('#Site-list')->count());
    }
}