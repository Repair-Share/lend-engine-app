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
     * @Route("event/preview/{eventId}", name="event_preview", defaults={"eventId" = 0})
     */
    public function eventPreviewAction(Request $request, $eventId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Repository\PaymentMethodRepository $pmRepo */
        $pmRepo = $em->getRepository('AppBundle:PaymentMethod');

        $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');

        if ($userId = $this->get('session')->get('sessionUserId')) {
            $user = $contactService->get($userId);
        } else {
            $user = $this->getUser();
        }

        /** @var $event \AppBundle\Entity\Event */
        if (!$event = $eventService->get($eventId)) {
            return $this->redirectToRoute('event_list');
        }

        $paymentMethods = $pmRepo->findAllOrderedByName();

        $alreadyBooked = false;
        foreach ($event->getAttendees() AS $attendee) {
            if ($attendee->getContact() == $user) {
                $alreadyBooked = true;
            }
        }

        return $this->render(
            'member_site/pages/event_preview.html.twig',
            [
                'event' => $event,
                'user' => $user,
                'alreadyBooked' => $alreadyBooked,
                'stripePaymentMethodId' => $stripePaymentMethodId,
                'paymentMethods' => $paymentMethods
            ]
        );
    }
}