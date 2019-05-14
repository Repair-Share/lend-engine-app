<?php

namespace Tests\Functional\AppBundle\Controller;

use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class AASetupTest extends AuthenticatedControllerTest
{
    public function setUp()
    {
        // This is loaded here since it's the first test (alphabetically)
        parent::loadTestDatabase();
        parent::setUp();
    }

    /**
     * An empty test at the start of the routine to tear down DB and load fixtures
     */
    public function testSetUp()
    {
        // A non-related test so that phpunit doesn't show this as a risky test
        $this->client->request('GET', '/');
        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }
}