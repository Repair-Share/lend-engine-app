<?php

namespace AppBundle\Controller\Admin\Event;

use AppBundle\Entity\Event;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class EventPublishController extends Controller
{
    /**
     * @Route("admin/event/{eventId}/publish", requirements={"eventId": "\d+"}, name="event_publish")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function eventPublish($eventId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\EventRepository $eventRepo */
        $eventRepo = $em->getRepository('AppBundle:Event');

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');

        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        /** @var $billingService \AppBundle\Services\BillingService */
        $billingService = $this->get('billing');

        /** @var \AppBundle\Entity\Event $event */
        if ($event = $eventRepo->find($eventId)) {

            $plan = $settingsService->getTenant(false)->getPlan();
            $maxLiveEvents = $billingService->getMaxEvents($plan);

            $count = $eventService->countLiveEvents();
            if ($count >= $maxLiveEvents) {
                $this->addFlash('error', "You've reached the maximum number of live events allowed on your plan ($maxLiveEvents). Please un-publish some events or upgrade via the billing screen.");
                return $this->redirectToRoute('admin_event_list');
            }

            $event->setStatus(Event::STATUS_PUBLISHED);
            $em->persist($event);
            $em->flush();

            // Activate the member-site listings
            $count++;
            if ($count > 0) {
                $settingsService->setSettingValue('show_events_online', 1);
            }

            $this->addFlash("success", "Event has been published!");

            return $this->redirectToRoute('event_admin', ['eventId' => $eventId]);
        } else {
            $this->addFlash("error", "Event {$eventId} not found.");
        }
        return $this->redirectToRoute('admin_event_list');
    }

    /**
     * @Route("admin/event/{eventId}/unpublish", requirements={"eventId": "\d+"}, name="event_unpublish")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function eventUnPublish($eventId)
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

            $this->addFlash("success", "Your event has been unpublished.");
            return $this->redirectToRoute('event_admin', ['eventId' => $eventId]);
        } else {
            $this->addFlash("error", "Event {$eventId} not found.");
        }
        return $this->redirectToRoute('admin_event_list');
    }

}