<?php

namespace AppBundle\Controller\MemberSite\Event;

use AppBundle\Entity\Attendee;
use AppBundle\Entity\Event;
use Doctrine\DBAL\DBALException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class SiteEventBookingController extends Controller
{
    /**
     * @Route("event/{eventId}/book", name="event_book")
     */
    public function eventBookAction($eventId)
    {
        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        $em = $this->getDoctrine()->getManager();

        if ($userId = $this->get('session')->get('sessionUserId')) {
            $user = $contactService->get($userId);
        } else {
            $user = $this->getUser();
        }

        if (!$event = $eventService->get($eventId)) {
            $this->addFlash("error", "No event found with ID {$eventId}");
            return $this->redirectToRoute('event_list');
        }

        $alreadyBooked = false;
        foreach ($event->getAttendees() AS $a) {
            if ($a->getContact() == $user) {
                $alreadyBooked = true;
            }
        }

        if ($alreadyBooked == true) {
            $this->addFlash("success", "You're already booked on this event.");
        } else {
            $attendee = new Attendee();
            $attendee->setEvent($event);
            $attendee->setContact($user);
            $attendee->setCreatedBy($this->getUser());
            $attendee->setIsConfirmed(true);
            $em->persist($attendee);
            try {
                $em->flush();
                $this->addFlash("success", "Booked!");
                $this->sendEventConfirmationEmail($event);
            } catch (\Exception $e) {
                $this->addFlash("error", $e->getMessage());
            }
        }

        if ($user == $this->getUser()) {
            return $this->redirectToRoute('my_events');
        } else {
            return $this->redirectToRoute('event_list');
        }
    }

    /**
     * @param $event Event
     */
    private function sendEventConfirmationEmail(Event $event)
    {

    }

}