<?php

namespace AppBundle\Controller\Event;

use AppBundle\Entity\Payment;
use AppBundle\Form\Type\EventPaymentType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class EventPaymentController extends Controller
{
    /**
     * @Route("admin/event/attendee/{attendeeId}/take-payment", requirements={"attendeeId": "\d+"}, name="event_payment")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function eventPayment(Request $request, $attendeeId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        /** @var \AppBundle\Repository\AttendeeRepository $attendeeRepo */
        $attendeeRepo = $em->getRepository('AppBundle:Attendee');

        // Add Stripe fee
        $feeAmount = (float)$this->get('settings')->getSettingValue('stripe_fee');
        $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');

        /** @var \AppBundle\Entity\Attendee $attendee */
        if (!$attendee = $attendeeRepo->find($attendeeId)) {
            return $this->redirectToRoute('admin_event_list');
        }

        $event = $attendee->getEvent();

        $options = [
            'action' => $this->generateUrl('event_payment', ['attendeeId' => $attendeeId])
        ];
        $form = $this->createForm(EventPaymentType::class, null, $options);

        // Since we're not mapping an entity to the form
        $form->get('paymentAmount')->setData($attendee->getPrice());
        $form->get('attendeeId')->setData($attendee->getId());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $paymentAmount = $form->get('paymentAmount')->getData();
            $paymentMethod = $form->get('paymentMethod')->getData();
            $paymentNote   = $form->get('paymentNote')->getData();

            $token   = $form->get('stripeToken')->getData();
            $cardId  = $form->get('stripeCardId')->getData();

            // Create a payment which is saved when we receive OK from Stripe
            $payment = new Payment();
            $payment->setCreatedBy($this->getUser());
            $payment->setPaymentMethod($paymentMethod);
            $payment->setAmount($paymentAmount);
            $payment->setNote($paymentNote);
            $payment->setContact($attendee->getContact());
            $payment->setEvent($event);
            $payment->setType(Payment::PAYMENT_TYPE_PAYMENT);

            if ($token || $cardId) {
                $cardDetails = [
                    'token'  => $token,
                    'cardId' => $cardId,
                ];
                if ($feeAmount > 0 && $paymentMethod->getId() == $stripePaymentMethodId) {
                    $paymentAmount += $feeAmount;
                    $payment->setAmount($paymentAmount);
                }
            } else {
                $cardDetails = null;
            }

            if ($paymentService->create($payment, $cardDetails)) {
                $contactService->recalculateBalance($attendee->getContact());
                $this->addFlash("success", "Payment taken OK");
            } else {
                $this->addFlash("error", "There was an error taking payment.");
                foreach ($paymentService->errors AS $error) {
                    $this->addFlash('error', $error);
                }
            }

            return $this->redirectToRoute('event_admin', ['eventId' => $event->getId()]);
        }

        // Get any saved cards
        $contactService->loadCustomerCards($attendee->getContact());

        return $this->render(
            'modals/event_payment.html.twig',
            array(
                'title' => "Take payment from ".$attendee->getContact()->getName(),
                'subtitle' => "For event : <strong>{$event->getTitle()}</strong>",
                'attendee' => $attendee,
                'form' => $form->createView(),
            )
        );
    }

}