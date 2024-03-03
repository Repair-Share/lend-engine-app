<?php

/**
 * Handler for payment.js and billing.js
 * Works with Stripe to create and update payment intent
 */

namespace AppBundle\Controller\Payment;

use AppBundle\Entity\Payment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StripePaymentIntentController extends Controller
{

    /**
     * @param Request $request
     * @return Response
     * @Route("stripe/payment-intent", name="stripe_payment_intent")
     */
    public function createPaymentIntent(Request $request)
    {
        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        $em = $this->getDoctrine()->getManager();

        $message = '';

        $data = json_decode($request->getContent(), true);

        $amount = $data['amount']; // in pence

        // Used to insert a note for pending payments which is removed when the payment is linked to a form submission
        if (isset($data['paymentType'])) {
            $paymentType = $data['paymentType'];
        } else {
            $paymentType = null;
        }

        // We have to handle deposits separately as there may be multiple deposits for a single Stripe charge
        // Such as when a cart has multiple items each of which need a deposit
        if (isset($data['deposits'])) {
            $deposits = (float)$data['deposits']*100; // in pounds
        } else {
            $deposits = 0;
        }

        $minimumPaymentAmount = $settingsService->getSettingValue('stripe_minimum_payment');

        if (isset($data['stripePaymentMethodId'])) {
            // We've got a payment method from a user entering a card number or re-using a card
            $paymentMethodId = $data['stripePaymentMethodId'];

            if ($amount/100 < $minimumPaymentAmount) {
                return new JsonResponse(['error' => 'A minimum payment of '.number_format($minimumPaymentAmount, 2).' is required. <br>You could <a href="/member/add-credit">add credit</a> instead.']);
            }

            $contact = $contactService->get($data['contactId']);
            $customer = [
                'id' => $contact->getStripeCustomerId(),
                'description' => $contact->getName(),
                'email' => $contact->getEmail()
            ];

            $intent = $stripeService->createPaymentIntent($paymentMethodId, $amount, $customer);

            if (!$contact->getStripeCustomerId()) {
                $contact->setStripeCustomerId($intent->customer);
                $em->persist($contact);
                $em->flush();
                $message .= 'Added Stripe customer ID. ';
            }
        } else if (isset($data['paymentIntentId'])) {
            // A payment intent has been created and we are checking whether it has been completed
            $paymentIntentId = $data['paymentIntentId'];
            $intent = $stripeService->retrievePaymentIntent($paymentIntentId);
        } else {
            $intent = null;
        }

        $extraErrors = '';
        foreach ($stripeService->errors AS $error) {
            $extraErrors .= ' '.$error;
        }

        if ($intent == null) {
            // Failed to create or get a payment intent
            return new JsonResponse([
                'error' => $extraErrors,
                'message' => $message,
                'errors' => $stripeService->errors
            ]);
        } else if ($intent->status == 'requires_action' &&
            $intent->next_action->type == 'use_stripe_sdk') {
            // Tell the client to handle the action
            return new JsonResponse([
                'requires_action' => true,
                'payment_intent_client_secret' => $intent->client_secret,
                'message' => $message,
            ]);
        } else if ($intent->status == 'succeeded') {
            // The payment didn't need any additional actions and completed OK
            if (isset($data['contactId']) && isset($data['saveCard'])) {
                if ($data['saveCard'] == true) {
                    $paymentService->saveCard($data['contactId'], $intent->payment_method);
                }
            }

            $chargeId = $intent->charges->data[0]->id;

            $savePaymentAmount = $amount - $deposits;

            if ($savePaymentAmount > 0 && $paymentId = $this->savePendingPayment($data['contactId'], $savePaymentAmount/100, $chargeId, $paymentType)) {
                // Tell the form to complete which updates the payment in the DB
                return new JsonResponse([
                    'success' => true,
                    'payment_id' => $paymentId,
                    'charge_id' => $chargeId,
                    'message' => $message,
                ]);
            } else {
                // We couldn't save a pending payment (perhaps the paid amount is just deposits)
                return new JsonResponse([
                    'success' => true,
                    'charge_id' => $chargeId,
                    'message' => $message,
                ]);
            }

        } else {
            // Invalid status or intent
            return new JsonResponse([
                'error' => 'Invalid PaymentIntent status : '.$intent->status . $extraErrors,
                'errors' => $stripeService->errors,
                'message' => $message,
            ]);
        }
    }

    /**
     * Create an isolated payment in the database, which will then be updated to link to the relevant data
     * eg customer, membership, loan, credit
     * @param $contactId
     * @param $amount
     * @return bool|int
     */
    private function savePendingPayment($contactId, $amount, $chargeId, $paymentType)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        $user = $this->getUser();

        $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');
        $paymentMethod = $paymentService->getPaymentMethodById($stripePaymentMethodId);

        $contact = $contactService->get($contactId);

        $paymentDate = new \DateTime();

        $payment = new Payment();
        $payment->setCreatedBy($user);
        $payment->setContact($contact);
        $payment->setAmount($amount);
        $payment->setPaymentDate($paymentDate);
        $payment->setPaymentMethod($paymentMethod);
        $payment->setPspCode($chargeId);

        if ($paymentType) {
            // We don't have a payment type for "add credit" since it doesn't need to be linked to anything
            // A failed form submission will still add credit to account if the card is charged
            $payment->setNote("Pending {$paymentType}");

            // When the payment is linked to the request, the pendingPaymentType is cleared
            $this->get('session')->set('pendingPaymentType', $paymentType);
        } else {
            $payment->setNote("Pending payment");
        }

        try  {
            $em->persist($payment);
            $em->flush();

            // Update the balance to reflect the new payment
            $contactService->recalculateBalance($contact);

            return $payment->getId();
        } catch (\Exception $e) {
            $this->addFlash('error', 'There was an error creating a pending payment.');
            foreach ($paymentService->errors AS $error) {
                $this->addFlash('error', $error);
            }
            return false;
        }

    }

}
