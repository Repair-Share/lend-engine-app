<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Form\Type\MembershipSubscribeType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscribeController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     * @Route("member/subscribe", name="subscribe")
     *
     * New registrations are directed here following a signup if there is a membership type set to 'self serve'
     * If the membership type has a value, then we need to have card payment first
     *
     */
    public function subscribe(Request $request)
    {

        /** @var \AppBundle\Entity\Contact $user */
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\Contact\ContactService $contactService */
        $contactService = $this->get('service.contact');

        /** @var \AppBundle\Repository\MembershipTypeRepository $membershipTypeRepo */
        $membershipTypeRepo = $em->getRepository('AppBundle:MembershipType');

        /** @var \AppBundle\Services\Payment\PaymentService $paymentService */
        $paymentService = $this->get('service.payment');

        $stripePaymentMethodId = $this->get('settings')->getSettingValue('stripe_payment_method');

        /** @var \AppBundle\Entity\Contact $contact */
        if ($contactId = $request->get('c')) {
            if (!$contact = $contactService->get($contactId)) {
                $this->addFlash('error', "Can't find that contact");
                return $this->redirectToRoute('fos_user_profile_show');
            }
        } else {
            $contact = $this->getUser();
        }

        // Create the form
        $form = $this->createForm(MembershipSubscribeType::class, null, [
            'em' => $em,
            'action' => $this->generateUrl('subscribe')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Inputs allow admins to update the amount paid for a subscription
            $amountPaid     = $form->get('paymentAmount')->getData();
            $price          = $form->get('price')->getData();
            $paymentMethod  = $form->get('paymentMethod')->getData();

            if (!$membershipType = $form->get('membershipType')->getData()) {
                $this->addFlash("error", "Please choose a membership type");
                return $this->redirectToRoute('choose_membership');
            }

            $membership = new Membership();
            $membership->setContact($contact);
            $membership->setCreatedBy($user);
            $membership->setMembershipType($membershipType);
            $membership->setPrice($price);

            $duration = $membership->getMembershipType()->getDuration();

            // Work out how many days left on the existing membership
            // If it's a renewal (same type) and less than 14 days to run, set end date based on end of current membership
            $calculateExpiryBasedOnCurrentExpiryDate = false;
            if ($activeMembership = $membership->getContact()->getActiveMembership()) {
                $dateDiff = $activeMembership->getExpiresAt()->diff(new \DateTime());
                if ($dateDiff->days < 14 && $activeMembership->getMembershipType() == $membership->getMembershipType()) {
                    $calculateExpiryBasedOnCurrentExpiryDate = true;
                }
            }

            // Always start from now
            // The previous will be expired so this one will start early
            $startsAt = new \DateTime();
            if ($calculateExpiryBasedOnCurrentExpiryDate == true) {
                // A renewal created before previous membership expires
                $expiresAt = $activeMembership->getExpiresAt();
            } else {
                // A new subscription
                $expiresAt = clone $startsAt;
            }
            $expiresAt->modify("+ {$duration} days");

            $membership->setStartsAt($startsAt);
            $membership->setExpiresAt($expiresAt);

            $em->persist($membership);

            // Switch the contact to this new membership
            $contact->setActiveMembership($membership);

            // If there was a previous one, expire it prematurely
            if ($activeMembership) {
                $activeMembership->setStatus(Membership::SUBS_STATUS_EXPIRED);
                $em->persist($activeMembership);
            }

            // update the contact and save everything
            $em->persist($contact);
            $em->flush();

            $note = new Note();
            $note->setContact($contact);
            $note->setCreatedBy($user);
            $note->setCreatedAt(new \DateTime());
            $note->setText("Subscribed to ".$membership->getMembershipType()->getName()." membership.");
            $em->persist($note);

            if ($price > 0) {
                // The membership fee
                $charge = new Payment();
                $charge->setAmount(-$price);
                $charge->setContact($contact);
                $charge->setCreatedBy($user);
                $charge->setMembership($membership);

                if ($contact == $this->getUser()) {
                    $charge->setNote("Membership fee (self serve).");
                } else {
                    $charge->setNote("Membership fee.");
                }

                if (!$paymentService->create($charge)) {
                    foreach ($paymentService->errors AS $error) {
                        $this->addFlash('error', $error);
                    }
                }
            }

            if ($amountPaid > 0) {
                // The payment for the charge
                $payment = new Payment();
                $payment->setCreatedBy($user);
                $payment->setPaymentMethod($paymentMethod);
                $payment->setAmount($amountPaid);
                $paymentNote = Payment::TEXT_PAYMENT_RECEIVED.'. '.$form->get('paymentNote')->getData();
                $payment->setNote($paymentNote);
                $payment->setContact($contact);
                $payment->setMembership($membership);

                if ($stripePaymentMethodId == $paymentMethod->getId()) {
                    $payment->setPspCode($request->get('chargeId'));
                }

                if (!$paymentService->create($payment)) {
                    foreach ($paymentService->errors AS $error) {
                        $this->addFlash('error', $error);
                    }
                }
            }

            $contactService->recalculateBalance($membership->getContact());

            if ($itemId = $request->get('itemId')) {
                $this->addFlash("success", "Subscribed OK");
                return $this->redirectToRoute('public_product', ['productId' => $itemId]);
            } else if ($user->hasRole("ROLE_ADMIN")) {
                $this->addFlash("success", "Subscribed OK");
                return $this->redirectToRoute('contact', ['id' => $contact->getId()]);
            } else {
                $this->addFlash("success", "Welcome! You are now a member.");
                return $this->redirectToRoute('fos_user_profile_show');
            }

        }

        // Data for the payment screen

        $membershipTypePrices = array();
        $membershipTypes = $membershipTypeRepo->findAll();
        foreach ($membershipTypes AS $type) {
            /** @var $type \AppBundle\Entity\MembershipType */
            $membershipTypePrices[] = array(
                'id' => $type->getId(),
                'price' => $type->getPrice()
            );
        }

        $contact = $contactService->loadCustomerCards($contact);

        return $this->render(
            'member_site/pages/subscribe.html.twig',
            [
                'form'    => $form->createView(),
                'user'    => $contact,
                'contact' => $contact,
                'itemId'  => $request->get('itemId'),
                'membershipTypePrices' => $membershipTypePrices
            ]
        );

    }

}
