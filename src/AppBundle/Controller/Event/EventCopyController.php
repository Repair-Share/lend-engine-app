<?php

namespace AppBundle\Controller\Event;

use AppBundle\Entity\Event;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class EventCopyController extends Controller
{
    /**
     * @Route("admin/event/{eventId}/copy", requirements={"eventId": "\d+"}, name="event_copy")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function eventCopy($eventId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\EventRepository $eventRepo */
        $eventRepo = $em->getRepository('AppBundle:Event');

        /** @var \AppBundle\Entity\Event $event */
        if ($event = $eventRepo->find($eventId)) {

            $newEvent = clone $event;
            $em->detach($newEvent);

            $newEvent->setCreatedBy($this->getUser());
            $newEvent->setStatus(Event::STATUS_DRAFT);

            $em->persist($newEvent);
            $em->flush();

            $this->addFlash("success", "Copied OK.");
            return $this->redirectToRoute('event_admin', ['eventId' => $newEvent->getId()]);

        } else {
            return $this->redirectToRoute('admin_event_list');
        }

    }

}