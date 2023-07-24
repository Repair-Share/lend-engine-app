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
            if (!$amountPaid = $form->get('paymentAmount')->getData()) {
                $amountPaid = 0;
            }
            if (!$price = $form->get('price')->getData()) {
                $price = 0;
            }

            if (!$membershipType = $form->get('membershipType')->getData()) {
                $this->addFlash("error", "Please choose a membership type");
                return $this->redirectToRoute('choose_membership');
            }

            $membership = new Membership();
            $membership->setContact($contact);
            $membership->setCreatedBy($user);
            $membership->setMembershipType($membershipType);
            $membership->setPrice($price);
            $membership->calculateStartAndExpiryDates();

            $em->persist($membership);

            $flashBags = $membership->subscribe(
                $em,
                $contact,
                $user,
                $paymentService,
                $price,
                $amountPaid,
                $request->get('paymentId'),
                $form->get('paymentMethod')->getData(),
                $form->get('paymentNote')->getData()
            );

            foreach ($flashBags as $flashBag) {
                $this->addFlash($flashBag['type'], $flashBag['msg']);
            }

            $this->get('session')->set('pendingPaymentType', null);
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
