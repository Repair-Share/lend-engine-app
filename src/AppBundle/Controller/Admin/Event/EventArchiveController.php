<?php

namespace AppBundle\Controller\Admin\Event;

use AppBundle\Entity\Event;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class EventArchiveController extends Controller
{
    /**
     * @Route("admin/event/{eventId}/archive", requirements={"eventId": "\d+"}, name="event_archive")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function eventArchive($eventId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');

        /** @var \AppBundle\Repository\EventRepository $eventRepo */
        $eventRepo = $em->getRepository('AppBundle:Event');

        /** @var \AppBundle\Entity\Event $event */
        if ($event = $eventRepo->find($eventId)) {
            $event->setStatus(Event::STATUS_ARCHIVED);
            $em->persist($event);
            $em->flush();

            // Deactivate the member-site listings
            $count = $eventService->countLiveEvents();
            if ($count < 1) {
                $settingsService->setSettingValue('show_events_online', 0);
            }

            $this->addFlash("success", "Your event has been archived.");
        } else {
            $this->addFlash("error", "Event {$eventId} not found.");
        }
        return $this->redirectToRoute('admin_event_list');
    }

    /**
     * @Route("admin/event/{eventId}/unarchive", requirements={"eventId": "\d+"}, name="event_unarchive")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function eventUnarchive($eventId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');

        /** @var \AppBundle\Repository\EventRepository $eventRepo */
        $eventRepo = $em->getRepository('AppBundle:Event');

        /** @var \AppBundle\Entity\Event $event */
        if ($event = $eventRepo->find($eventId)) {
            $event->setStatus(Event::STATUS_DRAFT);
            $em->persist($event);
            $em->flush();

            // Deactivate the member-site listings
            $count = $eventService->countLiveEvents();
            if ($count < 1) {
                $settingsService->setSettingValue('show_events_online', 0);
            }

            $this->addFlash("success", "Your event has been unarchived.");
        } else {
            $this->addFlash("error", "Event {$eventId} not found.");
        }
        return $this->redirectToRoute('admin_event_list');
    }

}