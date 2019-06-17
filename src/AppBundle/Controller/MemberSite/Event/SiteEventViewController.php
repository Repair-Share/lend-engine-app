<?php

namespace AppBundle\Controller\MemberSite\Event;

use AppBundle\Entity\Event;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SiteEventViewController extends Controller
{
    /**
     * @Route("event/{eventId}", name="event_view", defaults={"eventId" = 0})
     */
    public function eventListAction(Request $request, $eventId)
    {
        $search = $request->get('search');
        $searchString = $search['value'];

        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        if (!$event = $eventService->get($eventId)) {

        }

        return $this->render(
            'member_site/pages/event.html.twig',
            [
                'event' => $event,
            ]
        );
    }

    /**
     * @Route("event/preview/{eventId}", name="event_preview", defaults={"eventId" = 0})
     */
    public function eventPreviewAction(Request $request, $eventId)
    {
        $search = $request->get('search');
        $searchString = $search['value'];

        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        if (!$event = $eventService->get($eventId)) {

        }

        return $this->render(
            'member_site/pages/event_preview.html.twig',
            [
                'event' => $event,
            ]
        );
    }
}