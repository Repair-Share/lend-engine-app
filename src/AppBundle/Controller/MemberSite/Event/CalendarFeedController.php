<?php

namespace AppBundle\Controller\MemberSite\Event;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class CalendarFeedController extends Controller
{
    /**
     * @Route("events/ical", name="event_ical")
     */
    public function iCalFeed()
    {
        /** @var $eventService \AppBundle\Services\Event\EventService */
        $eventService = $this->get('service.event');

        $dFrom = new \DateTime("-30 days");
        $dTo   = new \DateTime("+60 days");
        $filter = [
            'from'    => $dFrom->format("Y-m-d"),
            'to'      => $dTo->format("Y-m-d"),
        ];
        $filter['status'] = ["PUBLISHED"];

        $results = $eventService->eventSearch(0, 500, $filter);

        return $this->render(
            'event/ical.html.twig',
            [
                'events' => $results['data']
            ]
        );
    }
}