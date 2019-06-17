<?php

namespace AppBundle\Controller\Event;

use AppBundle\Entity\Event;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class EventListController extends Controller
{

    /**
     * Page which holds an empty table (results via AJAX)
     * @Route("admin/event/list", name="admin_event_list")
     */
    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $sites = $em->getRepository('AppBundle:Site')->findOrderedByName();

        $searchString = $request->get('search');

        $eventStatuses = [
            Event::STATUS_DRAFT => 'Draft',
            Event::STATUS_PUBLISHED => 'Live',
            Event::STATUS_PAST => 'Past',
        ];

        if (!$selectedStatuses = $request->get('filterStatus')) {
            $selectedStatuses = ['DRAFT', 'PUBLISHED'];
        }

        return $this->render(
            'event/event_list.html.twig',
            [
                'searchString' => $searchString,
                'sites' => $sites,
                'eventStatuses' => $eventStatuses,
                'selectedStatuses' => $selectedStatuses
            ]
        );
    }

    /**
     * JSON responder for DataTables AJAX list
     * @Route("admin/dt/event/list", name="dt_event_list")
     */
    public function eventListAction(Request $request)
    {
        $data = array();

        $draw = $request->get('draw');

        $search = $request->get('search');
        $searchString = $search['value'];

        $start  = $request->get('start');
        $length = $request->get('length');

        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        $filter = [];
        if ($searchString) {
            $filter['search'] = $searchString;
        }
        if ($status = $request->get('status')) {
            $filter['status'] = $status;
        } else {
            $filter['status'] = ['PUBLISHED', 'DRAFT'];
        }

        /***** THE MAIN QUERY ******/
        $searchResults = $eventService->eventSearch($start, $length, $filter);
        $totalRecords = $searchResults['totalResults'];
        $events    = $searchResults['data'];

        /** @var \AppBundle\Entity\Event $event */
        foreach ($events AS $event) {
            $columns = [];

            $css = '';
            switch ($event->getStatus()) {
                case Event::STATUS_DRAFT:
                case '':
                    $css = 'e-draft';
                    $status = '<div class="e-status e-draft label">DRAFT</div>';
                    break;
                case Event::STATUS_PUBLISHED:
                    $css = 'e-live';
                    $status = '<div class="e-status e-live label">LIVE</div>';
                    break;
                case Event::STATUS_PAST:
                    $css = 'e-past';
                    $status = '<div class="e-status e-past label">PAST</div>';
                    break;
            }

            $time     = $event->getDate()->format("d F Y");
            $day      = $event->getDate()->format("d");
            $dayName  = $event->getDate()->format("D");
            $month    = $event->getDate()->format("M 'y");
            $timeFrom = new \DateTime($event->getDate()->format("Y-m-d").' '.$event->getTimeFrom());
            $timeTo   = new \DateTime($event->getDate()->format("Y-m-d").' '.$event->getTimeTo());
            $time     = $time.'<div class="small">'.$timeFrom->format("g:i a").' to '.$timeTo->format("g:i a").'</div>';

            $columns[] = '<div class="e-dayname '.$css.'">'.$dayName.'</div><div class="e-day '.$css.'">'.$day.'</div><div class="e-month '.$css.'">'.$month.'</div>';

            // Add columns
            if (!$title = $event->getTitle()) {
                $title = '- not set -';
            }
            $editUrl = $this->generateUrl('event_admin', ['eventId' => $event->getId()]);
            $columns[] = '<a href="'.$editUrl.'">'.$title.'</a>';

            $location = $event->getSite()->getName();
            $location = $location.'<div class="small">'.$event->getSite()->getAddress().'</div>';
            $columns[] = $location;

            if ($event->getMaxAttendees() > 0) {
                $attendees = $event->getAttendees()->count()." / ".$event->getMaxAttendees();
            } else {
                $attendees = $event->getAttendees()->count();
            }
            $columns[] = $attendees;
            $columns[] = $status;

            $publishLink   = $this->generateUrl('event_publish', ['eventId' => $event->getId()]);
            $unpublishLink = $this->generateUrl('event_unpublish', ['eventId' => $event->getId()]);
            if (!$event->getStatus() || $event->getStatus() == Event::STATUS_DRAFT) {
                $publishLink = '<a href="'.$publishLink.'">Publish</a>';
            } else if ($event->getStatus() == Event::STATUS_PUBLISHED) {
                $publishLink = '<a href="'.$unpublishLink.'">Un-publish</a>';
            } else if ($event->getStatus() == Event::STATUS_PAST) {
                $publishLink = 'Past';
            } else {
                $publishLink = '';
            }

            $deleteLink  = $this->generateUrl('event_delete', ['eventId' => $event->getId()]);
            $cloneLink   = $this->generateUrl('event_copy', ['eventId' => $event->getId()]);

            $links = '<li>'.$publishLink.'</li>';
            $links .= '<li><a href="'.$cloneLink.'">Clone to new event</a></li>';
            $links .= '<li role="separator" class="divider"></li>';
            $links .= '<li><a href="'.$deleteLink.'" class="delete-link">Delete</a></li>';

            $linkHtml = '
<div class="dropdown">
  <button class="btn btn-default btn-sm dropdown-toggle" type="button" data-toggle="dropdown">Action
  <span class="caret"></span></button>
  <ul class="dropdown-menu pull-right">
    '.$links.'
  </ul>
</div>';

            $columns[] = $linkHtml;

            $data[] = $columns;
        }

        return new Response(
            json_encode(array(
                'data' => $data,
                'recordsFiltered' => $totalRecords,
                'draw' => (int)$draw
            )),
            200,
            array('Content-Type' => 'application/json')
        );
    }

}