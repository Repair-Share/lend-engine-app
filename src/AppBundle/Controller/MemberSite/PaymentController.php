<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Payment;
use AppBundle\Form\Type\AddCreditType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
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

        $em = $this->getDoctrine()->getManager();

        $message = '';

        $data = json_decode($request->getContent(), true);

        $minimumPaymentAmount = $this->get('settings')->getSettingValue('stripe_minimum_payment');

        if (isset($data['stripePaymentMethodId'])) {
            // We've got a payment method from a user entering a card number or re-using a card
            $paymentMethodId = $data['stripePaymentMethodId'];
            $amount = $data['amount'];
            if ($amount/100 < $minimumPaymentAmount) {
                return new JsonResponse(['error' => 'A minimum payment of '.number_format($minimumPaymentAmount, 2).' is required.']);
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

    /**
     * @param Request $request
     * @return Response
     * @Route("member/add-credit", name="add_credit")
     */
    public function addCredit(Request $request)
    {
        // Added if user chooses Stripe
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        /** @var \AppBundle\Repository\PaymentMethodRepository $pmRepo */
        $pmRepo = $em->getRepository('AppBundle:PaymentMethod');

        $minimumPaymentAmount = $this->get('settings')->getSettingValue('stripe_minimum_payment');

        /** @var \AppBundle\Entity\Contact $contact */
        if ($contactId = $request->get('c')) {
            if (!$contact = $contactRepo->find($contactId)) {
                $this->addFlash('error', "Can't find that contact");
                return $this->redirectToRoute('add_credit');
            }
        } else {
            $contact = $this->getUser();
        }

        // Create the form
        $form = $this->createForm(AddCreditType::class, null, [
            'em' => $em,
            'action' => $this->generateUrl('add_credit')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $amount   = $form->get('paymentAmount')->getData();

            if ($amount < 0) {
                $this->addFlash('error', "Payment amount must be more than zero. Refunds should be issued using the payments list.");
            } else if ($amount > 0) {
                $paymentMethodId = $form->get('paymentMethod')->getData();
                $paymentMethod = $pmRepo->find($paymentMethodId);

                $paymentDate = new \DateTime();
                $paymentOk = true;

                $payment = new Payment();
                $payment->setCreatedBy($user);
                $payment->setContact($contact);
                $payment->setAmount($amount);
                $payment->setPaymentDate($paymentDate);
                $payment->setPaymentMethod($paymentMethod);
                if ($note = $form->get('paymentNote')->getData()) {
                    $payment->setNote($note);
                } else {
                    $payment->setNote("Credit added.");
                }

                $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');

                if ($stripePaymentMethodId == $paymentMethod->getId()) {
                    $payment->setPspCode($request->get('chargeId'));
                }

                if (!$paymentService->create($payment)) {
                    $paymentOk = false;
                    foreach ($paymentService->errors AS $error) {
                        $this->addFlash('error', $error);
                    }
                }

                if ($paymentOk == true) {
                    // Update the contact balance
                    $contactService->recalculateBalance($contact);
                    $this->addFlash('success', 'Payment recorded OK.');
                } else {
                    $this->addFlash('error', 'There was an error creating the payment.');
                }

                if ($request->get('return') == 'basket') {
                    return $this->redirectToRoute('basket_show', ['payment' => 'ok']);
                } else if ($request->get('return') == 'admin') {
                    return $this->redirectToRoute('contact', ['id' => $contact->getId()]);
                }

                return $this->redirectToRoute('payments');

            }

        }

        $contact = $contactService->loadCustomerCards($contact);

        if (!$paymentAmount = $request->get('amount')) {
            $paymentAmount = $minimumPaymentAmount;
        }

        // Switch the web session to requested user
        if ($user->getId() != $contact->getId()) {
            $this->get('session')->set('sessionUserId', $contact->getId());
        }

        if ($request->get('modal')) {
            // Opening from admin
            $template = 'modals/add_credit.html.twig';
        } else {
            // Member site
            $template = 'member_site/pages/add_credit.html.twig';
        }

        return $this->render(
            $template,
            [
                'user'    => $contact,
                'contact' => $contact,
                'initialPaymentAmount' => $paymentAmount,
                'form' => $form->createView()
            ]
        );

    }

    /**
     * @param Request $request
     * @return Response
     * @Route("remove-payment-method", name="remove_payment_method")
     */
    public function removePaymentMethod(Request $request)
    {
        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        $paymentMethodId = $request->get('paymentMethodId');
        $c = $request->get('c');

        if (!$stripeService->removePaymentMethod($paymentMethodId)) {
            foreach ($stripeService->errors AS $error) {
                $this->addFlash("error", $error);
            }
        } else {
            $this->addFlash("success", "Removed card OK");
        }

        return $this->redirectToRoute('add_credit', ['c' => $c]);
    }

}
