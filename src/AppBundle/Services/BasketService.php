<?php

namespace AppBundle\Services;

use AppBundle\Entity\Contact;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Serializer\Denormalizer\ContactDenormalizer;
use AppBundle\Serializer\Denormalizer\InventoryItemDenormalizer;
use AppBundle\Serializer\Denormalizer\LoanDenormalizer;
use AppBundle\Serializer\Denormalizer\LoanRowDenormalizer;
use AppBundle\Services\Contact\ContactService;
use AppBundle\Services\Loan\LoanService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Translation\Translator;
use Twig\Environment;

class BasketService
{
    /** @var EntityManagerInterface  */
    private $em;

    /** @var SettingsService */
    private $settings;

    /** @var Session */
    private $session;

    /** @var ContactService */
    private $contactService;

    /** @var Serializer */
    private $serializer;

    /** @var TokenStorageInterface  */
    private $tokenStorage;

    /** @var Contact */
    private $user;

    /** @var EmailService */
    private $emailService;

    /** @var TenantService */
    private $tenantService;

    /** @var LoanService */
    private $loanService;

    /** @var Translator */
    private $translator;

    /** @var Environment  */
    private $twig;

    /** @var array */
    public $errors = [];

    /** @var array */
    public $messages = [];

    /**
     * BasketService constructor.
     * @param EntityManagerInterface $em
     * @param SettingsService $settings
     * @param Session $session
     */
    public function __construct(EntityManagerInterface $em,
                                SettingsService $settings,
                                Session $session,
                                ContactService $contactService,
                                Serializer $serializer,
                                TokenStorageInterface $tokenStorage,
                                EmailService $emailService,
                                TenantService $tenantService,
                                LoanService $loanService,
                                Translator $translator,
                                Environment $twig)
    {
        $this->em = $em;
        $this->settings = $settings;
        $this->session = $session;
        $this->contactService = $contactService;
        $this->serializer = $serializer;
        $this->tokenStorage = $tokenStorage;
        $this->emailService = $emailService;
        $this->tenantService = $tenantService;
        $this->loanService = $loanService;
        $this->translator = $translator;
        $this->twig = $twig;

        $this->user = $this->tokenStorage->getToken()->getUser();
    }

    /**
     * @param null $basketContactId
     * @return Loan|bool
     */
    public function createBasket($basketContactId = null)
    {
        // Validation
        if (!$contact = $this->contactService->get($basketContactId)) {
            $this->errors[] = "Couldn't find a contact with ID {$basketContactId}.";
            return false;
        }

        if (!$contact->getActiveMembership()) {
            $this->errors[] = "This member doesn't have an active membership.";
            return false;
        }

        if (!$this->user->hasRole('ROLE_ADMIN')) {
            // We're not admin, we can't create baskets for other people
            if ($contact->getId() != $this->user->getId()) {
                $this->errors[] = "You can't create baskets for other people.";
                return false;
            }
        }

        // Create a basket
        $basket = new Loan();
        $basket->setContact($contact);
        $basket->setCreatedBy($this->user);
        $this->setSessionUser($basketContactId);

        // Only add reservation fee if not admin
        if (!$this->user->hasRole('ROLE_ADMIN')) {
            $bookingFee = $this->settings->getSettingValue('reservation_fee');
            $basket->setReservationFee($bookingFee);
        }

        $this->setSessionUser($basketContactId);

        // Stick it in the session
        $this->setBasket($basket);

        return $basket;
    }

    /**
     * Takes the Basket entity and turns it into JSON for storing in the session
     * @param Loan $basket
     */
    public function setBasket(Loan $basket)
    {
        // ----- Change times from local to UTC ----- //
        if (!$tz = $this->settings->getSettingValue('org_timezone')) {
            $tz = 'Europe/London';
        }
        $timeZone = new \DateTimeZone($tz);
        $utc = new \DateTime('now', new \DateTimeZone("UTC"));
        $offSet = -$timeZone->getOffset($utc)/3600;

        foreach ($basket->getLoanRows() AS $row) {

            /** @var $row \AppBundle\Entity\LoanRow */
            if (in_array($row->getInventoryItem()->getItemType(), [InventoryItem::TYPE_STOCK, InventoryItem::TYPE_SERVICE])) {
                // No dates for stock or service items
                continue;
            }

            $i = clone $row->getDueInAt();
            $i->modify("{$offSet} hours");
            $row->setDueInAt($i);

            $o = clone $row->getDueOutAt();
            $o->modify("{$offSet} hours");
            $row->setDueOutAt($o);

        }
        // ----- Change times from local to UTC ----- //

        $json = $this->serializer->normalize($basket, null, ['groups' => ['basket']]);
        $this->session->set('basket', $json);
    }

    /**
     * @return Loan|null
     */
    public function getBasket()
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
        if ($data = $this->session->get('basket')) {

            if (!isset($data['shippingFee'])) {
                $data['shippingFee'] = 0;
            }
            if (!isset($data['collectFrom'])) {
                $data['collectFrom'] = '';
            }

            $basket = $serializer->denormalize($data, Loan::class, 'json');

            // ----- Change times from UTC to local ----- //
            if (!$tz = $this->settings->getSettingValue('org_timezone')) {
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
     * Turn the basket into a loan on the database
     * @param $action
     * @param $rowFees
     * @return Loan|bool|null
     */
    public function confirmBasket($action, $rowFees)
    {
        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $this->em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Repository\ContactRepository $contactRepo */
        $contactRepo = $this->em->getRepository('AppBundle:Contact');

        /** @var \AppBundle\Repository\SiteRepository $siteRepo */
        $siteRepo = $this->em->getRepository('AppBundle:Site');

        /** @var \AppBundle\Repository\InventoryLocationRepository $locationRepo */
        $locationRepo = $this->em->getRepository('AppBundle:InventoryLocation');

        if (!$user = $this->user) {
            $this->errors[] = "You're not logged in. Please log in and try again.";
            return false;
        }

        // GET THE BASKET
        if (!$basket = $this->getBasket()) {
            $this->errors[] = "Your basket has expired. Please try again.";
            return false;
        }

        if ($action == 'checkout') {
            $basket->setStatus(Loan::STATUS_PENDING);
        } else {
            $action = 'reserve';
            $basket->setStatus(Loan::STATUS_RESERVED);
        }

        // Connect the entities with the DB IDs
        // The basket itself just stores the contact ID
        $contactId = $basket->getContact()->getId();

        $contact = $contactRepo->find($contactId);

        if (!$contact->getActiveMembership()) {
            $this->errors[] = "You don't have an active membership.";
            return false;
        }

        $basket->setContact($contact);
        $basket->setCreatedBy($this->user);

        // ----- Change times from local to UTC ----- //
        if (!$tz = $this->settings->getSettingValue('org_timezone')) {
            $tz = 'Europe/London';
        }
        $timeZone = new \DateTimeZone($tz);
        $utc = new \DateTime('now', new \DateTimeZone("UTC"));
        $offSet = -$timeZone->getOffset($utc)/3600;
        // ----- Change times from local to UTC ----- //

        // Add a row for the shipping fee
        $postalFee = $this->calculateShippingFee($basket);
        $shippingItemId = $this->settings->getSettingValue('postal_shipping_item');

        if ($postalFee > 0 && is_numeric($shippingItemId) && $basket->getCollectFrom() == "post") {
            if ($shippingItem = $itemRepo->find($shippingItemId)) {
                $shippingRow = new LoanRow();
                $shippingRow->setInventoryItem($shippingItem);
                $shippingRow->setProductQuantity(1);
                $shippingRow->setFee($postalFee);
                $shippingRow->setLoan($basket);
                $shippingRow->setDueInAt(new \DateTime()); // due to schema requirements
                $basket->addLoanRow($shippingRow);
                $rowFees[$shippingItemId] = $postalFee;
            }
        }

        foreach ($basket->getLoanRows() AS $row) {
            /** @var $row \AppBundle\Entity\LoanRow */

            // Update time zone
            if ($row->getDueInAt() && $row->getDueOutAt() ) {
                $i = $row->getDueInAt()->modify("{$offSet} hours");
                $row->setDueInAt($i);
                $o = $row->getDueOutAt()->modify("{$offSet} hours");
                $row->setDueOutAt($o);
            }

            // Get the DB entity
            $itemId = $row->getInventoryItem()->getId();
            $item = $itemRepo->find($itemId);
            $row->setInventoryItem($item);

            if ($row->getSiteFrom() && $row->getSiteFrom()->getId()) {
                $siteFromId = $row->getSiteFrom()->getId();
                $siteFrom = $siteRepo->find($siteFromId);
                $row->setSiteFrom($siteFrom);
            } else {
                $row->setSiteFrom(null);
            }

            if ($row->getSiteTo() && $row->getSiteTo()->getId()) {
                $siteToId = $row->getSiteTo()->getId();
                $siteTo = $siteRepo->find($siteToId);
                $row->setSiteTo($siteTo);
            } else {
                $row->setSiteTo(null);
            }

            // Stock items are given the current location on the loan row so we can remove stock
            if ($row->getItemLocation()) {
                $itemLocationId = $row->getItemLocation()->getId();
                $itemLocation = $locationRepo->find($itemLocationId);
                $row->setItemLocation($itemLocation);
            } else {
                $row->setItemLocation(null);
            }

            $row->setProductQuantity($row->getProductQuantity());

            if ($this->user->hasRole('ROLE_ADMIN')) {
                // Allow admins to edit the row fees in the basket UI
                $rowFee = $rowFees[$itemId];
                $row->setFee($rowFee);
            } else {
                // Fee will have been set when creating the basket
            }

            // Connect the detached rows [from basket] to the real loan row
            $row->setLoan($basket);
            $this->em->persist($row);

            // Update the out time of the reservation
            $basket->setTimeOut($row->getDueOutAt());

            // Also add the item fees if settings require it
            // Only when reserving items, not when checking out
            if ($this->settings->getSettingValue('charge_daily_fee') == 1
                && $row->getFee() > 0
                && $action == 'reserve') {
                $fee = new Payment();
                $fee->setCreatedBy($user);
                $fee->setAmount(-$row->getFee());
                $fee->setContact($basket->getContact());
                $fee->setLoan($basket);
                $fee->setInventoryItem($item);
                $fee->setType(Payment::PAYMENT_TYPE_FEE);
                $this->em->persist($fee);
            }
        }

        if ($action == 'checkout') {
            $noteText = 'Loan created by '.$basket->getCreatedBy()->getName();
        } else {
            $noteText = 'Reservation created by '.$basket->getCreatedBy()->getName();
        }

        // Reservation fee
        $bookingFee = $basket->getReservationFee();
        if ($bookingFee > 0 && $action == 'reserve') {
            $noteText .= "<br>Charged reservation fee of ".number_format($bookingFee, 2).".";
            $fee = new Payment();
            $fee->setCreatedBy($user);
            $fee->setAmount(-$bookingFee);
            $fee->setContact($basket->getContact());
            $fee->setLoan($basket);
            $feeNote = $this->translator->trans('note_reservation_fee', [], 'member_site');
            $fee->setNote($feeNote);
            $this->em->persist($fee);
        }

        $note = new Note();
        $note->setCreatedBy($user);
        $note->setLoan($basket);
        $note->setText($noteText);
        $this->em->persist($note);

        $basket->setTotalFee();
        $basket->setReturnDate();

        $this->em->persist($basket);

        try {
            $this->em->flush();
            $this->session->set('basket', null);
            if ($action == 'checkout') {
                $this->messages[] = "Loan created OK. Now time to check out ...";
            } else {
                $msg = $this->translator->trans('msg_success.reservation_create', [], 'member_site');
                $this->messages[] = $msg;
                $this->sendReservationConfirmEmail($basket->getId());
            }
        } catch (\Exception $generalException) {
            $msg = $this->translator->trans('msg_fail.reservation_create', [], 'member_site');
            $this->errors[] = $msg;
            $this->errors[] = $generalException->getMessage();
            return false;
        }

        try {
            $this->contactService->recalculateBalance($basket->getContact());
        } catch (\Exception $generalException) {
            $this->errors[] = $generalException->getMessage();
            return false;
        }

        return $basket;

    }

    /**
     * @param $contactId
     */
    public function setSessionUser($contactId) {
        $this->session->set('sessionUserId', $contactId);
    }

    /**
     * @param $loanId
     * @return bool
     */
    private function sendReservationConfirmEmail($loanId) {

        /** @var $loan \AppBundle\Entity\Loan */
        if (!$loan = $this->em->getRepository('AppBundle:Loan')->find($loanId)) {
            $this->errors[] = "Could not find loan ID {$loanId}";
            return false;
        }

        $contact = $loan->getContact();
        $token = $this->contactService->generateAccessToken($contact);

        $loginUri = $this->tenantService->getTenant(false)->getDomain(true);
        $loginUri .= '/access?t='.$token.'&e='.urlencode($contact->getEmail());
        $loginUri .= '&r=/loan/'.$loan->getId();

        $locale = $loan->getContact()->getLocale();

        // Send email confirmation to the member
        if ($toEmail = $contact->getEmail()) {

            $toName = $loan->getContact()->getName();

            if (!$subject = $this->settings->getSettingValue('email_reserve_confirmation_subject')) {
                $subject = $this->translator->trans('le_email.reservation_confirm.subject', [], 'emails', $locale);
            }

            // Save and switch locale for sending the email (it should be the same as the UI anyway)
            $sessionLocale = $this->translator->getLocale();
            $this->translator->setLocale($locale);

            // Build the message content
            $message = $this->twig->render(
                'emails/reservation_confirm.html.twig',
                [
                    'loan' => $loan,
                    'loanRows' => $loan->getLoanRows(),
                    'loginUri' => $loginUri,
                    'includeButton' => true
                ]
            );

            // Send the email
            $this->emailService->send($toEmail, $toName, $subject." (Ref ".$loan->getId().")", $message, true);

            // Revert locale for the UI
            $this->translator->setLocale($sessionLocale);
        }

        return true;
    }

    /**
     * @param Loan $loan
     * @return string
     */
    public function calculateShippingFee(Loan $loan)
    {
        $loanFee = (float)$this->settings->getSettingValue('postal_loan_fee');
        $itemFee = (float)$this->settings->getSettingValue('postal_item_fee');

        $shipping = $loanFee;
        /** @var \AppBundle\Entity\LoanRow $loanRow */
        foreach ($loan->getLoanRows() AS $loanRow) {
            if ($itemFee > 0
                && $loanRow->getInventoryItem()->getItemType() != 'service'
                && $loanRow->getInventoryItem()->getItemType() != 'kit') {
                $shipping += $itemFee;
            }
        }

        return $shipping;
    }

}