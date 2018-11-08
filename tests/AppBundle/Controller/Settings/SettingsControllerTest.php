<?php

namespace Tests\AppBundle\Controller\Settings;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class SettingsControllerTest extends AuthenticatedControllerTest
{
    public function testSettingsAction()
    {
        $crawler = $this->client->request('GET', '/admin/settings');
        $this->assertEquals(1, $crawler->filter('#settings-settings')->count());
    }
}