<?php

namespace Tests\AppBundle\Controller\Settings;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class OpeningHoursControllerTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        parent::setUp();
    }

    public function testMultipleOpeningHours()
    {
        $siteID = $this->helpers->createSite($this->client);

        $repeat = 3;

        $params = [
            'opening_hours' => [
                'date'           => 'Mon Dec 26 2022 to Wed Dec 28 2022',
                'type'           => 'o',
                'timeFrom'       => '9',
                'timeChangeover' => '',
                'timeTo'         => '18',
                'site'           => $siteID,
                'repeat'         => $repeat
            ]
        ];

        $this->client->request('POST', '/admin/site/' . $siteID . '/event', $params);
        $this->assertTrue($this->client->getResponse() instanceof RedirectResponse);

        $crawler = $this->client->followRedirect();

        $this->assertContains('About custom opening hours', $crawler->html());
        $this->assertContains('Monday 26 December 2022', $crawler->html());
        $this->assertContains('Tuesday 27 December 2022', $crawler->html());
        $this->assertContains('Wednesday 28 December 2022', $crawler->html());
        $this->assertContains('Tuesday 26 December 2023', $crawler->html());
        $this->assertContains('Wednesday 27 December 2023', $crawler->html());
        $this->assertContains('Thursday 28 December 2023', $crawler->html());
        $this->assertContains('Thursday 26 December 2024', $crawler->html());
        $this->assertContains('Friday 27 December 2024', $crawler->html());
        $this->assertContains('Saturday 28 December 2024', $crawler->html());
    }
}