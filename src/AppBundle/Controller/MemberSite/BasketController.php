<?php

namespace AppBundle\Controller\MemberSite;

use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Serializer\Denormalizer\LoanDenormalizer;
use AppBundle\Serializer\Denormalizer\ContactDenormalizer;
use AppBundle\Serializer\Denormalizer\InventoryItemDenormalizer;
use AppBundle\Serializer\Denormalizer\LoanRowDenormalizer;
use Postmark\PostmarkClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package AppBundle\Controller\MemberSite
 */
class BasketController extends Controller
{
    /**
     * @Route("basket", name="basket_show")
     */
    public function showBasket()
    {
        $em = $this->getDoctrine()->getManager();

        if (!$basket = $this->getBasket()) {
            $this->addFlash('error', "No basket found.");
            return $this->redirectToRoute('home');
        }

        $contactId = $basket->getContact()->getId();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        $contact = $contactRepo->find($contactId);
        $contactBalance = $contact->getBalance();

        return $this->render('public/pages/basket.html.twig', [
            'user' => $contact,
            'reservationFee' => $this->get('settings')->getSettingValue('reservation_fee'),
            'contactBalance' => $contactBalance
        ]);
    }

    /**
     * @return Response
     * @Route("switch-to/{contactId}", requirements={"contactId": "\d+"}, name="switch_contact")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function switchContactAction(Request $request, $contactId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        if (!$contact = $contactRepo->find($contactId)) {
            $this->addFlash('error', "Couldn't find a contact with ID {$contactId}.");
            return $this->redirectToRoute('home');
        }

        // If we have a basket, also switch the user
        if ($basket = $this->getBasket()) {

            if (!$contact->getActiveMembership()) {
                $this->addFlash('error', "This member doesn't have an active membership.");
                return $this->redirectToRoute('basket_show');
            }

            $basket->setContact($contact);
            $this->addFlash('success', "Changed user to <strong>".$contact->getName().'</strong>');

            $this->setBasket($basket);
        }

        $this->setSessionUser($contactId);

        if ($request->get('go') == 'basket') {
            return $this->redirectToRoute('basket_show');
        } else if ($itemId = $request->get('itemId')) {
            return $this->redirectToRoute('public_product', ['productId' => $itemId]);
        } else if ($request->get('new') == 'loan' || $request->get('new') == 'reservation')  {
            // Redundant now I think
            return $this->redirectToRoute('basket_create', ['contactId' => $contactId]);
        } else {
            return $this->redirectToRoute('home');
        }

    }

    /**
     * Clear any current basket and create one for the requested member
     * @param $contactId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/create/{contactId}", requirements={"contactId": "\d+"}, name="basket_create")
     */
    public function createBasketAction($contactId)
    {
        if (!$user = $this->getUser()) {
            $this->addFlash('error', "Please log in first.");
            return $this->redirectToRoute('home');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        /** @var $contact \AppBundle\Entity\Contact */
        if (!$contact = $contactRepo->find($contactId)) {
            $this->addFlash('error', "Couldn't find a contact with ID {$contactId}.");
            return $this->redirectToRoute('home');
        }

        if (!$contact->getActiveMembership()) {
            $this->addFlash('error', "This member doesn't have an active membership.");
            return $this->redirectToRoute('home');
        }

        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            // We're not admin, we can't create baskets for other people
            if ($contact->getId() != $user->getId()) {
                $this->addFlash('error', "You can't create baskets for other people.");
                return $this->redirectToRoute('home');
            }
        }

        $basket = $this->createBasket($contactId);

        $this->setSessionUser($contactId);

        // Stick it in the session
        $this->setBasket($basket);

        return $this->redirectToRoute('basket_show');
    }

    /**
     * @Route("basket/set-contact/{contactId}", requirements={"contactId": "\d+"}, name="basket_set_contact")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function basketChangeContact($contactId)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        if (!$basket = $this->getBasket()) {
            $basket = $this->createBasket($contactId);
        }

        if ($contact = $contactRepo->find($contactId)) {

            if (!$contact->getActiveMembership()) {
                $this->addFlash('error', "This member doesn't have an active membership.");
                return $this->redirectToRoute('basket_show');
            }

            $basket->setContact($contact);
            $this->addFlash('success', "This basket is now for <strong>".$contact->getName().'</strong>');
        }

        $this->setBasket($basket);
        $this->setSessionUser($contactId);

        return $this->redirectToRoute('basket_show');
    }

    /**
     * @Route("basket/add/{itemId}", requirements={"itemId": "\d+"}, name="basket_add_item")
     */
    public function basketAddItem($itemId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $em->getRepository('AppBundle:Site');

        // FIND THE ITEM
        $product = $itemRepo->find($itemId);

        // Validate sites
        if (!$request->get('from_site') || !$request->get('to_site')) {
            $this->addFlash('error', "There was an error trying to find the site you chose. Please log out/in and try again.");
            return $this->redirectToRoute('home');
        }

        if (!$request->get('date_from') || !$request->get('date_to')) {
            $this->addFlash('error', "Sorry, we couldn't determine loan dates. Please log out/in and try again.");
            return $this->redirectToRoute('home');
        }

        if (!$basket = $this->getBasket()) {
            if ($request->get('contactId')) {
                $basketContactId = $request->get('contactId');
            } else if ($this->get('session')->get('sessionUserId')) {
                $basketContactId = $this->get('session')->get('sessionUserId');
            } else {
                $basketContactId = $this->getUser()->getId();
            }
            $basket = $this->createBasket($basketContactId);
        }

        if (!$basket) {
            $this->addFlash('error', "There was an error trying to create you a basket, sorry. Please check you have an active membership.");
            return $this->redirectToRoute('home');
        }

        foreach ($basket->getLoanRows() AS $row) {
            if ($row->getInventoryItem()->getId() == $itemId) {
                $msg = $this->get('translator')->trans('msg_success.basket_item_exists', [], 'member_site');
                $this->addFlash('success', $product->getName().' '.$msg);
                return $this->redirectToRoute('basket_show');
            }
        }

        $fee = $request->get('item_fee');

        if (!$siteFrom = $siteRepo->find($request->get('from_site'))) {
            throw new \Exception("Cannot find site ".$request->get('from_site'));
        }

        if (!$siteTo   = $siteRepo->find($request->get('to_site'))) {
            throw new \Exception("Cannot find site ".$request->get('to_site'));
        }

        $dFrom = new \DateTime($request->get('date_from'));
        $dTo   = new \DateTime($request->get('date_to'));

        $row = new LoanRow();
        $row->setLoan($basket);
        $row->setInventoryItem($product);
        $row->setSiteFrom($siteFrom);
        $row->setSiteTo($siteTo);
        $row->setDueOutAt($dFrom);
        $row->setDueInAt($dTo);
        $row->setFee($fee);

        $basket->addLoanRow($row);
        $msg = $this->get('translator')->trans('msg_success.basket_item_added', [], 'member_site');
        $this->addFlash('success', $product->getName().' '.$msg);

        $this->setBasket($basket);

        return $this->redirectToRoute('basket_show');

    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/cancel", name="basket_cancel")
     */
    public function basketCancel()
    {
        $this->get('session')->set('basket', null);
        return $this->redirectToRoute('home');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/{itemId}/remove", requirements={"itemId": "\d+"}, name="basket_item_remove")
     */
    public function basketItemRemove($itemId)
    {
        if (!$basket = $this->getBasket()) {
            $this->addFlash('error', "No basket found.");
            return $this->redirectToRoute('home');
        }

        foreach ($basket->getLoanRows() AS $row) {
            if ($row->getInventoryItem()->getId() == $itemId) {
                $basket->removeLoanRow($row);
            }
        }

        $msg = $this->get('translator')->trans('msg_success.basket_item_removed', [], 'member_site');
        $this->addFlash('success', $msg);

        if (count($basket->getLoanRows()) == 0) {
            $this->get('session')->set('basket', null);
            return $this->redirectToRoute('public_products', ['show' => 'recent']);
        } else {
            $this->setBasket($basket);
            return $this->redirectToRoute('basket_show');
        }

    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/confirm", name="basket_confirm")
     */
    public function basketConfirmAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $em->getRepository('AppBundle:Site');

        $user = $this->getUser();

        // GET THE BASKET
        /** @var $loan \AppBundle\Entity\Loan */
        if (!$loan = $this->getBasket()) {
            $this->addFlash('error', "Your basket has expired. Please try again.");
            return $this->redirectToRoute('home');
        }

        if ($request->request->get('action') == 'checkout') {
            $loan->setStatus(Loan::STATUS_PENDING);
        } else {
            $loan->setStatus(Loan::STATUS_RESERVED);
        }

        // Connect the entities with the DB IDs
        $contactId = $loan->getContact()->getId();
        $contact = $contactRepo->find($contactId);

        if (!$contact->getActiveMembership()) {
            $this->addFlash('error', "You don't have an active membership.");
            return $this->redirectToRoute('home');
        }

        $loan->setContact($contact);

        $loan->setCreatedBy($this->getUser());

        $rowFees = $request->request->get('row_fee');

        // ----- Change times from local to UTC ----- //
        if (!$tz = $this->get('settings')->getSettingValue('org_timezone')) {
            $tz = 'Europe/London';
        }
        $timeZone = new \DateTimeZone($tz);
        $utc = new \DateTime('now', new \DateTimeZone("UTC"));
        $offSet = -$timeZone->getOffset($utc)/3600;
        // ----- Change times from local to UTC ----- //

        foreach ($loan->getLoanRows() AS $row) {
            /** @var $row \AppBundle\Entity\LoanRow */

            // Update time zone
            $i = $row->getDueInAt()->modify("{$offSet} hours");
            $row->setDueInAt($i);
            $o = $row->getDueOutAt()->modify("{$offSet} hours");
            $row->setDueOutAt($o);

            // Get the DB entity
            $itemId = $row->getInventoryItem()->getId();
            $item = $itemRepo->find($itemId);
            $row->setInventoryItem($item);

            $siteFromId = $row->getSiteFrom()->getId();
            $siteFrom = $siteRepo->find($siteFromId);
            $row->setSiteFrom($siteFrom);

            $siteToId = $row->getSiteTo()->getId();
            $siteTo = $siteRepo->find($siteToId);
            $row->setSiteTo($siteTo);

            $row->setProductQuantity(1);

            if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
                // Allow admins to edit the row fees in the basket UI
                $rowFee = $rowFees[$itemId];
                $row->setFee($rowFee);
            } else {
                // Fee will have been set when creating the basket
            }

            // Connect the detached rows from basket
            $row->setLoan($loan);
            $em->persist($row);

            // Update the out time of the reservation
            $loan->setTimeOut($row->getDueOutAt());

            // Also add the item fees if settings require it
            if ($this->get('settings')->getSettingValue('charge_daily_fee') == 1 && $row->getFee() > 0) {
                $fee = new Payment();
                $fee->setCreatedBy($user);
                $fee->setAmount(-$row->getFee());
                $fee->setContact($loan->getContact());
                $fee->setLoan($loan);
                $fee->setInventoryItem($item);
                $fee->setType(Payment::PAYMENT_TYPE_FEE);
                $em->persist($fee);
            }
        }

        $bookingFee = $loan->getReservationFee();

        if ($request->request->get('action') == 'checkout') {
            $word = 'Pending loan';
        } else {
            $word = 'Reservation';
        }
        $noteText = $word.' created by '.$loan->getCreatedBy()->getName();

        if ($bookingFee > 0) {
            $noteText .= "<br>Charged reservation fee of ".number_format($bookingFee, 2).".";

            $fee = new Payment();
            $fee->setCreatedBy($user);
            $fee->setAmount(-$bookingFee);
            $fee->setContact($loan->getContact());
            $fee->setLoan($loan);

            $feeNote = $this->get('translator')->trans('note_reservation_fee', [], 'member_site');
            $fee->setNote($feeNote);

            $em->persist($fee);
        }

        $note = new Note();
        $note->setCreatedBy($user);
        $note->setLoan($loan);
        $note->setText($noteText);
        $em->persist($note);

        $loan->setTotalFee();
        $loan->setReturnDate();

        $em->persist($loan);

        try {

            $em->flush();
            $this->get('session')->set('basket', null);

            if ($request->request->get('action') == 'checkout') {
                // Admin only
                $this->addFlash('success', "Loan created OK. Now time to check out ...");
            } else {
                $msg = $this->get('translator')->trans('msg_success.reservation_create', [], 'member_site');
                $this->addFlash('success', $msg);
                $this->sendReservationConfirmEmail($loan->getId());
            }

        } catch (\Exception $generalException) {

            $msg = $this->get('translator')->trans('msg_fail.reservation_create', [], 'member_site');
            $this->addFlash('error', $msg);
            $this->addFlash('error', $generalException->getMessage());

        }

        try {

            /** @var \AppBundle\Services\Contact\ContactService $contactService */
            $contactService = $this->get('service.contact');
            $contactService->recalculateBalance($loan->getContact());

        } catch (\Exception $generalException) {

            $this->addFlash('error', $generalException->getMessage());

        }

        return $this->redirectToRoute('public_loan', ['loanId' => $loan->getId()]);

    }

    /**
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("member/booking/{id}/cancel", requirements={"id": "\d+"}, name="reservation_cancel")
     */
    public function reservationCancelAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var $loanRepo \AppBundle\Repository\LoanRepository */
        $loanRepo = $this->getDoctrine()->getRepository('AppBundle:Loan');

        if (!$loan = $loanRepo->find($id)) {
            $this->addFlash('error', 'We could not find that reservation.');
        }

        $loan->setStatus(Loan::STATUS_CANCELLED);
        $em->persist($loan);

        try {
            $em->flush();
            $msg = $this->get('translator')->trans('msg_success.reservation_cancel', [], 'member_site');
            $this->addFlash('success', $msg);
        } catch (\Exception $generalException) {
            $msg = $this->get('translator')->trans('msg_fail.reservation_cancel', [], 'member_site');
            $this->addFlash('error', $msg);
        }

        return $this->redirectToRoute('loans');
    }

    /**
     * Update prices
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("basket/save", name="basket_save")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function basketSaveAction(Request $request)
    {
        /** @var $basket \AppBundle\Entity\Loan */
        if (!$basket = $this->getBasket()) {
            $this->addFlash('error', "Basket not found. Perhaps your session has timed out.");
            return $this->redirectToRoute('home');
        }

        $rowFees = $request->request->get('row_fee');

        foreach ($basket->getLoanRows() AS $row) {
            /** @var $row \AppBundle\Entity\LoanRow */
            $itemId = $row->getInventoryItem()->getId();
            $rowFee = $rowFees[$itemId];
            $row->setFee($rowFee);
        }

        $reservationFee = $request->request->get('booking_fee');
        $basket->setReservationFee($reservationFee);

        $this->setBasket($basket);

        $this->addFlash('success', "Saved");
        return $this->redirectToRoute('basket_show');
    }

    /**
     * A similar method is at \AppBundle\Controller\Loan\ReservationConfirmEmailController::reservationConfirmEmail
     * @param $loanId
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function sendReservationConfirmEmail($loanId)
    {
        /** @var $loan \AppBundle\Entity\Loan */
        if (!$loan = $this->getDoctrine()->getRepository('AppBundle:Loan')->find($loanId)) {
            $this->addFlash('error', "Could not find loan ID {$loanId}");
            return $this->redirectToRoute('loan_list');
        }

        $senderName  = $this->get('tenant_information')->getCompanyName();
        $senderEmail = $this->get('tenant_information')->getCompanyEmail();
        $locale = $loan->getContact()->getLocale();

        $client = new PostmarkClient($this->getParameter('postmark_api_key'));

        // Send email confirmation
        if ($toEmail = $loan->getContact()->getEmail()) {

            if (!$subject = $this->get('settings')->getSettingValue('email_reserve_confirmation_subject')) {
                $subject = $this->get('translator')->trans('le_email.reservation_confirm.subject', [], 'emails', $locale);
            }

            try {

                // Save and switch locale for sending the email (it should be the same as the UI anyway)
                $sessionLocale = $this->get('translator')->getLocale();
                $this->get('translator')->setLocale($locale);

                $message = $this->renderView(
                    'emails/reservation_confirm.html.twig',
                    array(
                        'loanRows' => $loan->getLoanRows()
                    )
                );

                $client->sendEmail(
                    "{$senderName} <hello@lend-engine.com>",
                    $toEmail,
                    $subject." (Ref ".$loan->getId().")",
                    $message,
                    null,
                    null,
                    true,
                    $senderEmail
                );

                // Revert locale for the UI
                $this->get('translator')->setLocale($sessionLocale);

            } catch (\Exception $generalException) {

            }

        }

        // Also send an email to company admin
        if ($senderEmail != 'email@demo.com') {
            try {

                if ($toEmail) {
                    $toName = $loan->getContact()->getName();
                    $msg = "This is a copy of the email sent to {$toName} ({$toEmail}).";
                } else {
                    $msg = "The member does not have an email address.";
                }

                $message = $this->renderView(
                    'emails/reservation_confirm.html.twig',
                    array(
                        'loanRows' => $loan->getLoanRows(),
                        'message' => $msg
                    )
                );

                $client->sendEmail(
                    "{$senderName} <hello@lend-engine.com>",
                    $senderEmail,
                    "A new reservation has been placed : ".$loan->getId()."",
                    $message,
                    null,
                    null,
                    true,
                    $senderEmail
                );

            } catch (\Exception $generalException) {

            }
        }

        return true;

    }

    /**
     * Takes the JSON basket from session and turns it into Entities
     * @return Loan
     */
    private function getBasket()
    {
        $serializer = new \Symfony\Component\Serializer\Serializer(
            [
                new LoanDenormalizer(),
                new ContactDenormalizer(),
                new LoanRowDenormalizer(),
                new InventoryItemDenormalizer(),
            ], [
                new \Symfony\Component\Serializer\Encoder\JsonDecode()
            ]
        );

        /** @var $basket \AppBundle\Entity\Loan */
        if ($data = $this->get('session')->get('basket')) {

            $basket = $serializer->denormalize($data, Loan::class, 'json');

            // ----- Change times from UTC to local ----- //
            if (!$tz = $this->get('settings')->getSettingValue('org_timezone')) {
                $tz = 'Europe/London';
            }
            $timeZone = new \DateTimeZone($tz);
            $utc = new \DateTime('now', new \DateTimeZone("UTC"));
            $offSet = $timeZone->getOffset($utc)/3600;
            foreach ($basket->getLoanRows() AS $r => $row) {
                /** @var $row \AppBundle\Entity\LoanRow */
                $i = $row->getDueInAt()->modify("{$offSet} hours");
                $row->setDueInAt($i);
                $o = $row->getDueOutAt()->modify("{$offSet} hours");
                $row->setDueOutAt($o);
            }
            // ----- Change times from UTC to local ----- //

            return $basket;
        } else {
            return null;
        }
    }

    /**
     * Takes the Basket entity and turns it into JSON for storing in the session
     * @param Loan $basket
     */
    private function setBasket(Loan $basket)
    {
        // ----- Change times from local to UTC ----- //
        if (!$tz = $this->get('settings')->getSettingValue('org_timezone')) {
            $tz = 'Europe/London';
        }
        $timeZone = new \DateTimeZone($tz);
        $utc = new \DateTime('now', new \DateTimeZone("UTC"));
        $offSet = -$timeZone->getOffset($utc)/3600;
        foreach ($basket->getLoanRows() AS $row) {
            /** @var $row \AppBundle\Entity\LoanRow */
            $i = $row->getDueInAt()->modify("{$offSet} hours");
            $row->setDueInAt($i);
            $o = $row->getDueOutAt()->modify("{$offSet} hours");
            $row->setDueOutAt($o);
        }
        // ----- Change times from local to UTC ----- //

        $serializer = $this->get('serializer');
        $json = $serializer->normalize($basket, null, ['groups' => ['basket']]);
        $this->get('session')->set('basket', $json);
    }

    /**
     * @param null $basketContactId
     * @return Loan|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function createBasket($basketContactId = null)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $em->getRepository('AppBundle:Contact');

        $basket = new Loan();

        // Get the contact
        if (!$basketContactId) {
            $basketContactId = $this->getUser()->getId();
        }

        $contact = $contactRepo->find($basketContactId);

        if (!$contact->getActiveMembership()) {
            return false;
        }

        $basket->setContact($contact);
        $basket->setCreatedBy($this->getUser());

        $this->setSessionUser($basketContactId);

        // Only add reservation fee if not admin
        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $bookingFee = $this->get('settings')->getSettingValue('reservation_fee');
            $basket->setReservationFee($bookingFee);
        }

        return $basket;
    }

    /**
     * @param $contactId
     */
    private function setSessionUser($contactId) {
        $this->get('session')->set('sessionUserId', $contactId);
    }

}
