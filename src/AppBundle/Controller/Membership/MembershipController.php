<?php

namespace AppBundle\Controller\Membership;

use AppBundle\Entity\CreditCard;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Form\Type\MembershipType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class MembershipController extends Controller
{
    /**
     * Page which holds an empty table (results via AJAX)
     * @Route("admin/membership/list", name="membership_list")
     */
    public function listAction(Request $request)
    {
        $searchString = $request->get('search');
        return $this->render(
            'membership/membership_list.html.twig',
            array(
                'searchString' => $searchString
            )
        );
    }

    /**
     * @Route("admin/dt/membership/list", name="dt_membership_list")
     */
    public function tableListAction(Request $request)
    {
        $data = array();

        // Get from the DB
        $em = $this->getDoctrine()->getManager();

        $search = $request->get('search');
        $searchString = $search['value'];

        $start  = $request->get('start');
        $length = $request->get('length');

        /** @var \AppBundle\Repository\MembershipRepository $repo */
        $repo = $em->getRepository('AppBundle:Membership');

        $subscriptions = $repo->search($start, $length, $searchString);
        $totalRecords  = $repo->countAll();

        foreach ($subscriptions AS $i) {
            /** @var $i \AppBundle\Entity\Membership */

            $action = '';
            if ($i->getStatus() == Membership::SUBS_STATUS_ACTIVE) {
                $status = '<span class="label bg-green">ACTIVE</span>';
                $cancelUrl = $this->generateUrl('membership_cancel', array('id' => $i->getId()));
                $action = '<a href="' . $cancelUrl . '">Cancel</a>';
            } else if ($i->getStatus() == Membership::SUBS_STATUS_EXPIRED) {
                $status = '<span class="label bg-orange">EXPIRED</span>';
            } else {
                $status = '<span class="label bg-gray">CANCELLED</span>';
            }

            $contactUrl   = $this->generateUrl('contact', array('id' => $i->getContact()->getId()));

            $data[] = array(
                '<a href="'.$contactUrl.'">'.$i->getContact()->getFirstName().' '.$i->getContact()->getLastName().'</a>',
                $i->getMembershipType()->getName(),
                $i->getCreatedAt()->format("d M Y"),
                $i->getStartsAt()->format("d M Y"),
                $i->getExpiresAt()->format("d M Y"),
                $status,
                number_format($i->getPrice(), 2),
                $action
            );
        }

        if ($searchString) {
            $count = count($data);
        } else {
            $count = $totalRecords;
        }

        $draw = $request->get('draw');

        return new Response(
            json_encode(array(
                'data' => $data,
                'recordsFiltered' => $count,
                'draw' => (int)$draw
            )),
            200,
            array('Content-Type' => 'application/json')
        );
    }

    /**
     * Modal content for managing memberships
     * @Route("admin/membership/contact/{contactId}", defaults={"contactId" = 0}, requirements={"contactId": "\d+"}, name="membership")
     */
    public function subscriptionAction(Request $request, $contactId)
    {
        $em = $this->getDoctrine()->getManager();

        $membershipTypeRepo = $em->getRepository('AppBundle:MembershipType');

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        $stripeUseSavedCards = $this->get('settings')->getSettingValue('stripe_use_saved_cards');

        $membership = new Membership();
        $user = $this->getUser();
        $membership->setCreatedBy($user);

        /** @var \AppBundle\Entity\Contact $contact */
        if (!$contact = $contactRepo->find($contactId)) {
            $this->addFlash('error', "A contact was not found with ID {$contactId}.");
            return $this->redirectToRoute('homepage');
        }

        $membership->setContact($contact);

        $form = $this->createForm(MembershipType::class, $membership, [
            'em' => $em,
            'action' => $this->generateUrl('membership', ['contactId' => $contact->getId()])
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Get the subscription length
            $membershipTypeId = $form->get('membershipType')->getData();

            /** @var \AppBundle\Entity\MembershipType $membershipType */
            $membershipType = $membershipTypeRepo->find($membershipTypeId);
            $duration = $membershipType->getDuration();
            $expiresAt = new \DateTime();
            $expiresAt->modify("+ {$duration} days");

            $membership->setExpiresAt($expiresAt);
            $startsAt = new \DateTime();
            $membership->setStartsAt($startsAt);

            $em->persist($membership);

            if ($form->get('price')->getData() > 0) {

                $fee = new Payment();
                $fee->setCreatedBy($user);
                $amount = $form->get('price')->getData();
                $fee->setAmount(-$amount);
                $fee->setContact($contact);
                $fee->setMembership($membership);
                $fee->setNote("Membership fee.");

                if (!$paymentService->create($fee, null)) {
                    foreach ($paymentService->errors AS $error) {
                        $this->addFlash('error', $error);
                    }
                }

            }

            // Add Stripe fee
            $feeAmount = (float)$this->get('settings')->getSettingValue('stripe_fee');
            $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');

            $paymentOk = true;

            if ($form->get('paymentAmount')->getData() > 0) {

                $amount  = $form->get('paymentAmount')->getData();
                $paymentMethod = $form->get('paymentMethod')->getData();

                $token   = $form->get('stripeToken')->getData();
                $cardId  = $form->get('stripeCardId')->getData();

                // Create a payment which is saved when we receive OK from Stripe
                $payment = new Payment();
                $payment->setCreatedBy($user);
                $payment->setPaymentMethod($paymentMethod);
                $payment->setAmount($amount);
                $paymentNote = Payment::TEXT_PAYMENT_RECEIVED.'. '.$form->get('paymentNote')->getData();
                $payment->setNote($paymentNote);
                $payment->setContact($contact);

                if ($token || $cardId) {
                    $cardDetails = [
                        'token'  => $token,
                        'cardId' => $cardId,
                    ];
                    if ($feeAmount > 0 && $paymentMethod->getId() == $stripePaymentMethodId) {
                        $amount += $feeAmount;
                        $payment->setAmount($amount);
                    }
                } else {
                    $cardDetails = null;
                }

                if (!$paymentService->create($payment, $cardDetails)) {
                    $paymentOk = false;
                    foreach ($paymentService->errors AS $error) {
                        $this->addFlash('error', $error);
                    }
                }

            }

            if ($paymentOk == true) {
                $contact->setActiveMembership($membership);
                $em->persist($contact);

                $note = new Note();
                $note->setContact($contact);
                $note->setCreatedBy($user);
                $note->setCreatedAt(new \DateTime());
                $note->setText("Subscribed to ".$membershipType->getName()." membership.");
                $em->persist($note);

                try {
                    $em->flush();
                    $this->addFlash('success', 'Membership saved.');
                    $contactService->recalculateBalance($contact);
                } catch (\Exception $generalException) {
                    $this->addFlash('error', 'There was an error creating the membership.');
                    $this->addFlash('debug', $generalException->getMessage());
                }
            }

            return $this->redirectToRoute('contact', array('id' => $contact->getId()));

        }

        // Required to set the price when a user chooses a subscription type in the modal
        $membershipTypePrices = array();
        $membershipTypes = $membershipTypeRepo->findAll();
        foreach ($membershipTypes AS $membershipType) {
            /** @var $membershipType \AppBundle\Entity\MembershipType */
            $membershipTypePrices[] = array(
                'id' => $membershipType->getId(),
                'price' => $membershipType->getPrice()
            );
        }

        $customerStripeId = $contact->getStripeCustomerId();
        if ($customerStripeId && $stripeUseSavedCards) {
            // retrieve their cards
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

        return $this->render(
            'modals/membership.html.twig',
            array(
                'form' => $form->createView(),
                'membershipTypePrices' => $membershipTypePrices,
                'contact' => $contact
            )
        );

    }

}