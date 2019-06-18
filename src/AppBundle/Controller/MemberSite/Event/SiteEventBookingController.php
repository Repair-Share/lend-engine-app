<?php

namespace AppBundle\Controller\MemberSite\Event;

use AppBundle\Entity\Attendee;
use AppBundle\Entity\Event;
use AppBundle\Entity\Payment;
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
        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        $em = $this->getDoctrine()->getManager();

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

        if ($alreadyBooked == true) {
            $this->addFlash("success", "You're already booked on this event.");
        } else {

            $attendee = new Attendee();
            $attendee->setEvent($event);
            $attendee->setContact($user);
            $attendee->setCreatedBy($this->getUser());
            $attendee->setIsConfirmed(true);

            $eventPrice    = $request->request->get("paymentAmount");
            $paymentMethod = $request->request->get("paymentMethod");

            if ($eventPrice > 0) {
                $attendee->setPrice($eventPrice);

                // Create fee for the financials
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
                    $paymentService->create($p);
                }
            }

            $em->persist($attendee);
            try {
                $em->flush();

                if ($request->get('check_in')) {
                    $this->addFlash("success", "Checked in - thank you!");
                } else {
                    $this->addFlash("success", "You're booked in. See you soon!");
                    $this->sendBookingConfirmationEmail($attendee);
                }

                // Update the account if any changes have been made to payments
                $contactService->recalculateBalance($user);

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


    private function sendBookingConfirmationEmail(Attendee $attendee)
    {

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('service.tenant');

        $senderName     = $tenantService->getCompanyName();
        $replyToEmail   = $tenantService->getReplyToEmail();
        $fromEmail      = $tenantService->getSenderEmail();
        $postmarkApiKey = $tenantService->getSetting('postmark_api_key');

        $locale = $attendee->getContact()->getLocale();

        $client = new PostmarkClient($postmarkApiKey);

        // Send email confirmation
        if ($toEmail = $attendee->getContact()->getEmail()) {

            if (!$subject = $this->get('settings')->getSettingValue('email_booking_confirmation_subject')) {
                $subject = $this->get('translator')->trans('le_email.booking_confirmation.subject', [], 'emails', $locale);
            }

            try {

                // Save and switch locale for sending the email (it should be the same as the UI anyway)
                $sessionLocale = $this->get('translator')->getLocale();
                $this->get('translator')->setLocale($locale);

                $message = $this->renderView(
                    'emails/booking_confirmation.html.twig',
                    array(
                        'attendee' => $attendee,
                        'message'  => ''
                    )
                );

                $client->sendEmail(
                    "{$senderName} <{$fromEmail}>",
                    $toEmail,
                    $subject.' : '.$attendee->getEvent()->getTitle(),
                    $message,
                    null,
                    null,
                    true,
                    $replyToEmail
                );

                // Revert locale for the UI
                $this->get('translator')->setLocale($sessionLocale);

            } catch (\Exception $generalException) {
//                $this->addFlash("error", $generalException->getMessage());
            }

        }

        // Also send an email to company admin
        if ($replyToEmail != 'email@demo.com') {
            try {

                if ($toEmail) {
                    $toName = $attendee->getContact()->getName();
                    $msg = "This is a copy of the email sent to {$toName} ({$toEmail}).";
                } else {
                    $msg = "The member does not have an email address.";
                }

                $message = $this->renderView(
                    'emails/booking_confirmation.html.twig',
                    array(
                        'attendee' => $attendee,
                        'message'  => ''
                    )
                );

                $client->sendEmail(
                    "{$senderName} <{$fromEmail}>",
                    $replyToEmail,
                    "Booking confirmation : ".$attendee->getEvent()->getTitle(),
                    $message,
                    null,
                    null,
                    true,
                    $replyToEmail
                );

            } catch (\Exception $generalException) {

            }
        }

        return true;

    }


}