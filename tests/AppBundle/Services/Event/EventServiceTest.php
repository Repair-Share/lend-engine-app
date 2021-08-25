<?php

namespace Tests\AppBundle\Services\Event;

use AppBundle\Entity\Event;
use Tests\AppBundle\Controller\AuthenticatedControllerTest;

class EventServiceTest extends AuthenticatedControllerTest
{
    public function testEventTimes()
    {
        $event = new Event();

        $asserts = [
            '09:00' => [
                '9',
                '900',
                '9:00',
                '09:00'
            ],
            '09:30' => [
                '930',
                '9:30',
                '09:30'
            ],
            '17:00' => [
                '17',
                '1700',
                '17:00'
            ],
            '17:30' => [
                '1730',
                '17:30'
            ]
        ];

        foreach ($asserts as $expected => $tests) {

            foreach ($tests as $test) {
                $event->setTimeFrom($test);
                $this->assertEquals($expected, $event->getTimeFrom());

                $event->setTimeTo($test);
                $this->assertEquals($expected, $event->getTimeTo());
            }

        }

    }

    public function testEventTimesEmpty()
    {
        $event = new Event();

        $event->setTimeFrom('29:00');
        $event->setTimeTo('27:00');

        $this->assertEmpty($event->getTimeFrom());
        $this->assertEmpty($event->getTimeTo());

        $event->setTimeFrom('9:60');
        $event->setTimeTo('17:70');

        $this->assertEmpty($event->getTimeFrom());
        $this->assertEmpty($event->getTimeTo());

        $event->setTimeFrom('x:10');
        $event->setTimeTo('y:20');

        $this->assertEmpty($event->getTimeFrom());
        $this->assertEmpty($event->getTimeTo());

        $event->setTimeFrom('breaking');
        $event->setTimeTo('breaking');

        $this->assertEmpty($event->getTimeFrom());
        $this->assertEmpty($event->getTimeTo());
    }
}