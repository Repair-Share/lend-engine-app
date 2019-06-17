<?php

namespace AppBundle\Controller\MemberSite\Event;

use AppBundle\Entity\Event;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SiteEventListController extends Controller
{
    /**
     * @Route("events", name="event_list")
     */
    public function eventListAction(Request $request)
    {
        $search = $request->get('search');
        $searchString = $search['value'];

        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        $filter = [];
        if ($searchString) {
            $filter['search'] = $searchString;
        }
        $filter['status'] = ['PUBLISHED'];

        /***** THE MAIN QUERY ******/
        $searchResults = $eventService->eventSearch(0, 100, $filter);
        $totalRecords  = $searchResults['totalResults'];
        $events        = $searchResults['data'];

        return $this->render(
            'member_site/pages/event_list.html.twig',
            [
                'events' => $events,
            ]
        );
    }

    /**
     * @Route("events/json", name="event_feed")
     */
    public function eventCalendarFeed(Request $request)
    {
        $data = [];
        $dateFrom = $request->get('start');
        $dateTo   = $request->get('end');

        /** @var $eventService \AppBundle\Services\Event\EventService */
        $eventService = $this->get('service.event');

        $dFrom = new \DateTime($dateFrom);
        $dTo   = new \DateTime($dateTo);
        $filter = [
            'from'    => $dFrom->format("Y-m-d"),
            'to'      => $dTo->format("Y-m-d"),
        ];
        $filter['status'] = ["PUBLISHED"];

        $results = $eventService->eventSearch(0, 500, $filter);

        foreach ($results['data'] AS $event) {
            /** @var $event \AppBundle\Entity\Event */

            $s_start = $event->getDate()->format("Y-m-d").' '.substr($event->getTimeFrom(), 0, 2).':'.substr($event->getTimeFrom(), 2, 2).':00';
            $s_end   = $event->getDate()->format("Y-m-d").' '.substr($event->getTimeTo(), 0, 2).':'.substr($event->getTimeTo(), 2, 2).':00';
            $site = $event->getSite();

            if ($event->getType() != 'c') {
                $data[] = [
                    'eventId'  => $event->getId(),
                    'siteId'   => $site->getId(),
                    'siteName' => $site->getName(),
                    'title'    => $event->getTitle(),
                    'color'    => '#b1ada2',
                    'start'    => $s_start,
                    'end'      => $s_end,
                ];
            }
        }

        $data = array_values($data);

        return $this->json($data);
    }


}