<?php

namespace AppBundle\Controller\Event;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class EventDeleteController extends Controller
{
    /**
     * @Route("admin/event/{eventId}/delete", requirements={"eventId": "\d+"}, name="event_delete")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function eventDelete($eventId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\EventRepository $eventRepo */
        $eventRepo = $em->getRepository('AppBundle:Event');

        /** @var \AppBundle\Entity\Event $event */
        if ($event = $eventRepo->find($eventId)) {
            $em->remove($event);
            $em->flush();
            $this->addFlash("success", "Your event has been deleted.");
        } else {
            $this->addFlash("error", "Event {$eventId} not found.");
        }

        return $this->redirectToRoute('admin_event_list');
    }

}