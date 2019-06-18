<?php

namespace AppBundle\Controller\Event;

use AppBundle\Entity\Attendee;
use AppBundle\Entity\Event;
use AppBundle\Form\Type\EventType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class EventController extends Controller
{
    /**
     * @Route("admin/event/{eventId}", requirements={"eventId": "\d+"}, defaults={"eventId": 0}, name="event_admin")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function eventEdit(Request $request, $eventId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\EventRepository $eventRepo */
        $eventRepo = $em->getRepository('AppBundle:Event');

        /** @var \AppBundle\Repository\AttendeeRepository $attendeeRepo */
        $attendeeRepo = $em->getRepository('AppBundle:Attendee');

        /** @var \AppBundle\Entity\Event $event */
        if ($event = $eventRepo->find($eventId)) {
            $title = $event->getTitle();
        } else {
            $event = new Event();
            $event->setCreatedBy($this->getUser());
            $event->setDate(new \DateTime());
            $event->setType('o');
            $event->setStatus(Event::STATUS_DRAFT);

            $title = "Create a new event";
        }

        $label = '';
        switch ($event->getStatus()) {
            case Event::STATUS_DRAFT:
                $label = '<span class="label label-default pull-right">DRAFT</span>';
                break;
            case Event::STATUS_PAST:
                $label = '<span class="label bg-black pull-right">PAST</span>';
                break;
            case Event::STATUS_PUBLISHED:
                $label = '<span class="label label-success pull-right">LIVE</span>';
                break;
        }

        $options = [];
        $form = $this->createForm(EventType::class, $event, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$eventId) {
                $attendee = new Attendee();
                $attendee->setCreatedBy($this->getUser());
                $attendee->setContact($this->getUser());
                $attendee->setEvent($event);
                $attendee->setType(Attendee::TYPE_ORGANISER);
                $event->addAttendee($attendee);
            }

            $d = $form->get('date')->getData();
            $date = new \DateTime($d);
            $event->setDate($date);

            $from = $form->get('timeFrom')->getData();
            $from = str_replace(':', '', $from);
            $from = str_replace(' am', '', $from);
            $from = str_replace(' pm', '', $from);
            $event->setTimeFrom($from);

            $to = $form->get('timeTo')->getData();
            $to = str_replace(':', '', $to);
            $to = str_replace(' am', '', $to);
            $to = str_replace(' pm', '', $to);
            $event->setTimeTo($to);

            if ($task = $request->request->get('batchActionTask')) {
                if ($attendeeIds = $request->request->get('attendees')) {
                    foreach ($attendeeIds AS $aID) {
                        switch ($task) {
                            case "remove":
                                if ($a = $attendeeRepo->find($aID)) {
                                    /*
                                     *
                                     *
                                     *
                                     *
                                     * @TODO remove payments before removing attendee
                                     *
                                     *
                                     * 
                                     *
                                     *
                                     */
                                    $em->remove($a);
                                }
                                break;
                            case "organiser":
                                if ($a = $attendeeRepo->find($aID)) {
                                    $a->setType(Attendee::TYPE_ORGANISER);
                                    $em->persist($a);
                                }
                                break;
                        }
                    }
                }
            }

            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'Saved.');

            return $this->redirectToRoute('event_admin', ['eventId' => $event->getId()]);
        }

        return $this->render(
            'event/event.html.twig',
            array(
                'title' => $title,
                'event' => $event,
                'label' => $label,
                'eventDate' => $event->getDate()->format("D M d Y"),
                'form' => $form->createView(),
            )
        );
    }

}