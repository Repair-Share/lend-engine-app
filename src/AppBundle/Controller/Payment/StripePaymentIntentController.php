<?php

/**
 * Handler for payment.js and billing.js
 * Works with Stripe to create and update payment intent
 */

namespace AppBundle\Controller\Payment;

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

        $minimumPaymentAmount = $settingsService->getSettingValue('stripe_minimum_payment');

        if (isset($data['stripePaymentMethodId'])) {

            // We've got a payment method from a user entering a card number or re-using a card
            $paymentMethodId = $data['stripePaymentMethodId'];
            $amount = $data['amount'];
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
            return new JsonResponse([
                'error' => $extraErrors,
                'message' => $message,
                'errors' => $stripeService->errors
            ]);
        } else if ($intent->status == 'requires_action' &&
            $intent->next_action->type == 'use_stripe_sdk') {
            # Tell the client to handle the action
            return new JsonResponse([
                'requires_action' => true,
                'payment_intent_client_secret' => $intent->client_secret,
                'message' => $message,
            ]);
        } else if ($intent->status == 'succeeded') {

            # The payment didn't need any additional actions and completed OK
            if (isset($data['contactId']) && isset($data['saveCard'])) {
                if ($data['saveCard']) {
                    $paymentService->saveCard($data['contactId'], $intent->payment_method);
                }
            }

            # Handle post-payment fulfillment
            return new JsonResponse([
                'success' => true,
                'charge_id' => $intent->charges->data[0]->id,
                'message' => $message,
            ]);

        } else {
            # Invalid status or intent
            return new JsonResponse([
                'error' => 'Invalid PaymentIntent status : '.$intent->status . $extraErrors,
                'errors' => $stripeService->errors,
                'message' => $message,
            ]);
        }
    }

}
