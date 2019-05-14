<?php

// Tests/Functional/AppBundle/ApplicationAvailabilityFunctionalTest.php
/**
 *
 * A simple but useful file to ensure that all non-entity-specific pages are showing a successful response
 *
 */

namespace Tests\Functional\AppBundle;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class ApplicationAvailabilityFunctionalTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        // This is loaded here since it's the first test (alphabetically)
        parent::loadTestDatabase();
        parent::setUp();
    }

    /**
     * @dataProvider urlProvider
     */
    public function testPageIsSuccessful($url)
    {
        $this->client->request('GET', $url);
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * @return array
     */
    public function urlProvider()
    {
        return array(
            // FOS user bundle pages
            array('/login'),
            array('/profile/edit'),
            array('/profile/change-password'),
        );
    }
}