<?php

namespace Tests\AppBundle\Controller\MemberSite;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class SiteDataControllerTest extends AuthenticatedControllerTest
{
    public function testCustomOpeningHours()
    {
        // Add a new site
        $siteID = $this->helpers->createSite($this->client);

        // Set up this week's Monday
        $firstDayOfWeek = 1; // Monday

        $monday = new \DateTime();

        $difference = ($firstDayOfWeek - $monday->format('N'));
        $monday->modify("$difference days");

        $tuesday = clone $monday;
        $tuesday->modify('1 day');

        $wednesday = clone $monday;
        $wednesday->modify('2 days');

        // Add custom hours / holiday to the site

        // Monday is closed
        $this->helpers->addSiteOpeningHours(
            $this->client,
            $siteID,
            $monday,
            '0900',
            '1700',
            false
        );

        // Tuesday is opened
        $this->helpers->addSiteOpeningHours(
            $this->client,
            $siteID,
            $tuesday,
            '0900',
            '1700',
            true
        );

        // Create an item
        $itemID = $this->helpers->createItem($this->client);

        // Check site data
        $now = new \DateTime();

        $uri = '/site-data?itemId=' . $itemID
               . '&start=' . urlencode($now->modify('-2 weeks')->format('Y-m-d'))
               . '&end=' . urlencode($now->modify('+4 weeks')->format('Y-m-d'))
               . '&siteId=' . $siteID;

        $this->client->request('GET', $uri);
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        // At this stage we only search in the response content and don't decode the JSON data to array
        $responseContent = $response->getContent();

        $this->assertNotContains($monday->format('Y-m-d 09:00:00'), $responseContent); // Monday is closed
        $this->assertContains($tuesday->format('Y-m-d 09:00:00'), $responseContent); // Tuesday is opened
        $this->assertNotContains($wednesday->format('Y-m-d 09:00:00'), $responseContent); // Wednesday is closed

    }
}