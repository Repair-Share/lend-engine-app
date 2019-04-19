<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Payment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{

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

        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        /** @var \AppBundle\Repository\PaymentMethodRepository $pmRepo */
        $pmRepo = $em->getRepository('AppBundle:PaymentMethod');

        $minimumPaymentAmount = $this->get('settings')->getSettingValue('stripe_minimum_payment');
        $stripeUseSavedCards = $this->get('settings')->getSettingValue('stripe_use_saved_cards');

        /** @var \AppBundle\Entity\Contact $contact */

        if ($contactId = $request->get('c')) {
            if (!$contact = $contactRepo->find($contactId)) {
                $this->addFlash('error', "Can't find that contact");
                return $this->redirectToRoute('add_credit');
            }
        } else {
            $contact = $this->getUser();
        }

        $amount = $request->get('paymentAmount');
        if ($amount < 0) {
            $this->addFlash('error', "Payment amount must be more than zero. Refunds should be issued using the payments list.");
        } else if ($amount > 0) {

            $paymentMethodId = $request->get('paymentMethod');
            $paymentMethod = $pmRepo->find($paymentMethodId);

            $paymentDate = new \DateTime();
            $paymentOk = true;

            $payment = new Payment();
            $payment->setCreatedBy($user);
            $payment->setContact($contact);
            $payment->setAmount($amount);
            $payment->setPaymentDate($paymentDate);
            $payment->setPaymentMethod($paymentMethod);
            if ($note = $request->get('paymentNote')) {
                $payment->setNote($note);
            } else {
                $payment->setNote("Credit added.");
            }

            $cardDetails = null;
            $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');

            if ($stripePaymentMethodId == $paymentMethod->getId()) {
                if ($amount < $minimumPaymentAmount) {
                    $this->addFlash('error', 'A minimum payment of '.number_format($minimumPaymentAmount, 2).' is required.');
                    return $this->redirectToRoute('add_credit', ['c' => $contact->getId()]);
                }
                $cardDetails = [
                    'token'  => $request->get('stripeToken'),
                    'cardId' => $request->get('stripeCardId'),
                ];
            }

            if (!$paymentService->create($payment, $cardDetails)) {
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

        $customerStripeId = $contact->getStripeCustomerId();
        if ($customerStripeId && $stripeUseSavedCards) {
            // Retrieve their cards
            $stripeCustomer = $stripeService->getCustomerById($customerStripeId);

            if (isset($stripeCustomer['sources']['data'])) {
                foreach($stripeCustomer['sources']['data'] AS $source) {
                    $creditCard = new CreditCard();
                    $creditCard->setLast4($source['last4']);
                    $creditCard->setExpMonth($source['exp_month']);
                    $creditCard->setExpYear($source['exp_year']);
                    $creditCard->setBrand($source['brand']);
                    $creditCard->setCardId($source['id']);
                    $contact->addCreditCard($creditCard);
                }
            }
        }

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

        $paymentMethods = $pmRepo->findAllOrderedByName();

        return $this->render(
            $template,
            [
                'user'    => $contact,
                'contact' => $contact,
                'initialPaymentAmount' => $paymentAmount,
                'paymentMethods' => $paymentMethods
            ]
        );

    }

}
