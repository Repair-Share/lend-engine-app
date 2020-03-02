<?php

namespace AppBundle\Controller\MemberSite\Event;

use AppBundle\Entity\Attendee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Payment;
use AppBundle\Form\Type\EventBookingType;
use Doctrine\DBAL\DBALException;
use Postmark\PostmarkClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SiteEventBookingController extends Controller
{
    /**
     * @Route("event/{eventId}/book", name="event_book")
     */
    public function eventBookAction(Request $request, $eventId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');

        if ($userId = $this->get('session')->get('sessionUserId')) {
            $user = $contactService->get($userId);
        } else {
            $user = $this->getUser();
        }

        /** @var $event \AppBundle\Entity\Event */
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

        // Create the form
        $form = $this->createForm(EventBookingType::class, null, [
            'em' => $em,
            'action' => $this->generateUrl('event_book', ['eventId' => $event->getId()])
        ]);

        $form->handleRequest($request);

        if (($form->isSubmitted() && $form->isValid()) || $request->get('check_in')) {

            if ($alreadyBooked == true) {
                $this->addFlash("success", "You're already booked on this event.");
            } else {

                $attendee = new Attendee();
                $attendee->setEvent($event);
                $attendee->setContact($user);
                $attendee->setCreatedBy($this->getUser());
                $attendee->setIsConfirmed(true);

                $eventPrice    = $form->get("paymentAmount")->getData();
                $paymentMethod = $form->get("paymentMethod")->getData();

                $paymentOk = true;
                if ($eventPrice > 0) {
                    $attendee->setPrice($eventPrice);

                    // Create fee
                    $payment = new Payment();
                    $payment->setContact($user);
                    $payment->setType(Payment::PAYMENT_TYPE_FEE);
                    $payment->setEvent($event);
                    $payment->setAmount($eventPrice);
                    $payment->setCreatedBy($this->getUser());
                    $em->persist($payment);

                    if ($paymentMethod) {

                        $p = new Payment();
                        $p->setContact($user);
                        $p->setType(Payment::PAYMENT_TYPE_PAYMENT);
                        $p->setEvent($event);
                        $p->setAmount($eventPrice);
                        $p->setCreatedBy($this->getUser());
                        $p->setPaymentMethod($paymentMethod);

                        if ($stripePaymentMethodId == $paymentMethod->getId()) {
                            $p->setPspCode($request->get('chargeId'));
                        }

                        if ($paymentService->create($p)) {
                            $contactService->recalculateBalance($attendee->getContact());
                        } else {
                            $this->addFlash("error", "There was an error taking payment.");
                            foreach ($paymentService->errors AS $error) {
                                $this->addFlash('error', $error);
                            }
                            $paymentOk = false;
                        }

                    }
                }

                if ($paymentOk == true) {
                    $em->persist($attendee);
                    try {
                        $em->flush();

                        if ($request->get('check_in')) {
                            $this->addFlash("success", "Checked in - thank you!");
                        } else {
                            $this->addFlash("success", "You're booked in. See you soon!");
                            $this->sendBookingConfirmationEmail($attendee);
                        }
                    } catch (\Exception $e) {
                        $this->addFlash("error", $e->getMessage());
                    }
                } else {

                }

            }

            if ($user == $this->getUser()) {
                return $this->redirectToRoute('my_events');
            } else {
                return $this->redirectToRoute('event_list');
            }

        }

        return $this->redirectToRoute('event_list');

    }

    /**
     * @param Attendee $attendee
     * @return bool
     */
    private function sendBookingConfirmationEmail(Attendee $attendee)
    {
        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        $locale = $attendee->getContact()->getLocale();

        // Send email confirmation
        if ($toEmail = $attendee->getContact()->getEmail()) {

            $toName = $attendee->getContact()->getName();

            if (!$subject = $this->get('settings')->getSettingValue('email_booking_confirmation_subject')) {
                $subject = $this->get('translator')->trans('le_email.booking_confirmation.subject', [], 'emails', $locale);
            }

            // Save and switch locale for sending the email (it should be the same as the UI anyway)
            $sessionLocale = $this->get('translator')->getLocale();
            $this->get('translator')->setLocale($locale);

            $message = $this->renderView(
                'emails/booking_confirmation.html.twig',
                [
                    'attendee' => $attendee,
                    'message'  => ''
                ]
            );

            // Send the email
            $subject = $subject.' '.$attendee->getEvent()->getTitle();
            if (!$emailService->send($toEmail, $toName, $subject, $message, true)) {
                foreach ($emailService->getErrors() AS $msg) {
                    $this->addFlash('error', $msg);
                }
            }

            // Revert locale for the UI
            $this->get('translator')->setLocale($sessionLocale);
        }

        return true;

    }


}