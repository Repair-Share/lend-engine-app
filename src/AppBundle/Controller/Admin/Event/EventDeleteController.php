<?php

namespace AppBundle\Controller\Admin\Event;

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

            $deleteInstructions = '';

            // Check attendees
            $canDelete = $eventRepo->validateDelete($eventId);

            if (!$canDelete) {
                $deleteInstructions = 'Event cannot be deleted. Please remove all attendees first.';
            }

            // Check payments
            if ($canDelete == true) {

                $canDelete = $eventRepo->validateDeleteWithPayments($eventId);

                if (!$canDelete) {
                    $deleteInstructions = "You can't delete events which have payments. Please delete the payment(s) first.";
                }

            }

            if ($canDelete == true) {
                $em->remove($event);
                $em->flush();
                $this->addFlash("success", "Your event has been deleted.");
            } else {
                $this->addFlash("error", $deleteInstructions);
            }
        } else {
            $this->addFlash("error", "Event {$eventId} not found.");
        }

        return $this->redirectToRoute('admin_event_list');
    }

}