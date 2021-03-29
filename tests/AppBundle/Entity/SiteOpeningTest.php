<?php

namespace Tests\AppBundle\Entity;

use AppBundle\Entity\SiteOpening;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class SiteOpeningTest extends AuthenticatedControllerTest
{
    public function testTimeFrom()
    {
        $siteOpening = new SiteOpening();

        $siteOpening->setTimeFrom('09:00');
        $this->assertEquals('9:00 am', $siteOpening->getFriendlyTimeFrom());

        $siteOpening->setTimeFrom('12:00');
        $this->assertEquals('12:00 pm', $siteOpening->getFriendlyTimeFrom());

        $siteOpening->setTimeFrom('breaking');
        $this->assertEmpty($siteOpening->getFriendlyTimeFrom());
    }

    public function testTimeTo()
    {
        $siteOpening = new SiteOpening();

        $siteOpening->setTimeTo('09:00');
        $this->assertEquals('9:00 am', $siteOpening->getFriendlyTimeTo());

        $siteOpening->setTimeTo('12:00');
        $this->assertEquals('12:00 pm', $siteOpening->getFriendlyTimeTo());

        $siteOpening->setTimeTo('breaking');
        $this->assertEmpty($siteOpening->getFriendlyTimeTo());
    }
}