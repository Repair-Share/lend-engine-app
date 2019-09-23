<?php

namespace AppBundle\Services\Loan;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Repository\SettingRepository;
use AppBundle\Services\Contact\ContactService;
use AppBundle\Services\Booking\BookingService;
use AppBundle\Services\SettingsService;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

class CheckoutService
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var ContactService
     */
    private $contactService;

    /**
     * @var BookingService
     */
    private $reservationService;

    /** @var SettingsService  */
    private $settings;

    /**
     * @var array
     */
    public $errors = [];

    /**
     * @param EntityManager $em
     * @param Container $container
     * @param ContactService $contactService
     * @param BookingService $reservationService
     */
    public function __construct(
        EntityManager $em,
        Container $container,
        ContactService $contactService,
        BookingService $reservationService,
        SettingsService $settings)
    {
        $this->em        = $em;
        $this->container = $container;
        $this->contactService = $contactService;
        $this->reservationService = $reservationService;
        $this->settings = $settings;
    }

    /**
     * @param Loan $loan
     * @return bool
     */
    public function loanCheckOut(Loan $loan)
    {
        /** @var $locationRepo \AppBundle\Repository\InventoryLocationRepository */
        $locationRepo = $this->em->getRepository('AppBundle:InventoryLocation');

        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        // Check that all items can be checked out
        if (!$this->validateCheckout($loan)) {
            return false;
        }

        // Get charges so as to apply the right charges per row
        $charges = [];
        foreach ($loan->getPayments() AS $payment) {
            /** @var \AppBundle\Entity\InventoryItem $item */
            if ($item = $payment->getInventoryItem()) {
                $charges[$item->getId()] = $payment->getAmount();
            }
        }

        foreach ($loan->getLoanRows() AS $row) {

            // set row as checked out
            $row->setCheckedOutAt(new \DateTime());
            $this->em->persist($row);

            // Move item to on-loan
            /** @var \AppBundle\Entity\InventoryLocation $location */
            if (!$location = $locationRepo->find(1)) {
                $this->errors[] = "Location with ID 1 not found. Please contact support.";
                return false;
            }

            /** @var $row \AppBundle\Entity\LoanRow */
            $inventoryItem = $row->getInventoryItem();
            $inventoryItem->setInventoryLocation($location);
            $this->em->persist($inventoryItem);

            $transactionRow = new ItemMovement();
            $transactionRow->setInventoryLocation($location);
            $transactionRow->setCreatedBy($user);
            $transactionRow->setInventoryItem($inventoryItem);
            $transactionRow->setLoanRow($row);
            $this->em->persist($transactionRow);

            // Add some item history
            $note = new Note();
            $note->setInventoryItem($inventoryItem);
            $note->setCreatedBy($user);
            $note->setText("Loaned to <strong>".$loan->getContact()->getName().'</strong> on loan <strong>'.$loan->getId().'</strong>');
            $this->em->persist($note);

            $itemFee = $row->getFee();

            // We've already charged part of this row (perhaps when reserving self-serve)
            if (isset($charges[$inventoryItem->getId()])) {
                $itemFee = $itemFee - $charges[$inventoryItem->getId()];
            }

            if ($itemFee > 0) {
                $fee = new Payment();
                $fee->setCreatedBy($user);
                $fee->setAmount($itemFee);
                $fee->setType(Payment::PAYMENT_TYPE_FEE);
                $fee->setContact($loan->getContact());
                $fee->setLoan($loan);
                $fee->setInventoryItem($inventoryItem);
                $this->em->persist($fee);
            }

        }

        // Mark the loan as checked out
        $loan->setStatus(Loan::STATUS_ACTIVE);

        $loan->setTimeOut(new \DateTime());
        $loan->setTotalFee();
        $loan->setReturnDate();

        // Save any changes to the loan
        $this->em->persist($loan);

        $checkoutNoteText = "Checked out loan. ";

        // Add a note
        $note = new Note();
        $note->setCreatedBy($user);
        $note->setLoan($loan);
        $note->setContact($loan->getContact());
        $note->setText($checkoutNoteText);
        $this->em->persist($note);

        try {
            $this->em->flush();

            // Now that we've applied a fee, recalculate the balance
            if (!$this->contactService->recalculateBalance($loan->getContact())) {
                foreach ($this->contactService->errors AS $error) {
                    $this->errors[] = $error;
                }
            }

            return true;
        } catch (\Exception $generalException) {
            $this->errors[] = $generalException->getMessage();
            return false;
        }

    }

    /**
     * @param Loan $loan
     * @return bool
     */
    public function validateCheckout(Loan $loan)
    {
        if (count($loan->getLoanRows()) == 0) {
            $this->errors[] = "No items on the loan.";
            return false;
        }
        foreach ($loan->getLoanRows() AS $loanRow) {
            /** @var $loanRow \AppBundle\Entity\LoanRow */
            $item   = $loanRow->getInventoryItem();
            $from   = $loanRow->getDueOutAt();
            $to     = $loanRow->getDueInAt();
            $loanId = $loan->getId();
            if ($this->isItemReserved($item, $from, $to, $loanId)) {
                return false;
            }
            if ($loanRow->getInventoryItem()->getInventoryLocation()->getId() == 1) {
                $this->errors[] = 'Item "'.$loanRow->getInventoryItem()->getName().'" is already on loan.';
                return false;
            }
            if ($loanRow->getInventoryItem()->getInventoryLocation()->getIsAvailable() != true) {
                $this->errors[] = 'Item "'.$loanRow->getInventoryItem()->getName().'" is in a reserved location ('.$loanRow->getInventoryItem()->getInventoryLocation()->getName().').';
                return false;
            }
        }
        return true;
    }

    /**
     * @param InventoryItem $item
     * @param $from \DateTime
     * @param $to \DateTime
     * @param $loanId
     * @return bool
     */
    public function isItemReserved(InventoryItem $item, $from, $to, $loanId = null)
    {
        $timeZone = $this->settings->getSettingValue('org_timezone');
        $tz = new \DateTimeZone($timeZone);

        $itemId = $item->getId();

        // Check if this item is reserved on another loan, but not yet collected
        $filter = [
            'item_ids' => [$itemId]
        ];

        $reservationLoanRows = $this->reservationService->getBookings($filter);

        // Find ALL reservations for this item
        foreach ($reservationLoanRows AS $reservation) {

            /** @var $reservation \AppBundle\Entity\LoanRow */
            if ($loanId == $reservation->getLoan()->getId()) {
                // Skip if it's the loan we're adding to / validating against
                continue;
            }

            $dueOutAt = $reservation->getDueOutAt()->setTimezone($tz);
            $dueInAt  = $reservation->getDueInAt()->setTimezone($tz);

            // Formatted for easier logic comparison
            $dueOutAt_f = $dueOutAt->format("Y-m-d H:i:s");
            $dueInAt_f = $dueInAt->format("Y-m-d H:i:s");
            $requestFrom = $from->format("Y-m-d H:i:s");
            $requestTo = $to->format("Y-m-d H:i:s");

            $reservedItemId = $reservation->getInventoryItem()->getId();
            $errorMsg = '"'.$reservation->getInventoryItem()->getName().'" (#'.$reservedItemId.') is reserved by '.$reservation->getLoan()->getContact()->getName();
            $errorMsg .= ' (ref '.$reservation->getLoan()->getId().', '.$dueOutAt->format("d M H:i").' - '.$dueInAt->format("d M H:i").')';

            // The requested START date is during another reservation
            if ($requestFrom >= $dueOutAt_f && $requestFrom < $dueInAt_f) {
                $this->errors[] = $errorMsg;
                $this->errors[] = "Requested {$from->format("d M H:i")} - {$to->format("d M H:i")} (STARTS)";
                return true;
            }

            // The requested END date is during or matches the end of another reservation
            if ($requestTo > $dueOutAt_f && $requestTo <= $dueInAt_f) {
                $this->errors[] = $errorMsg;
                $this->errors[] = "Requested {$from->format("d M H:i")} - {$to->format("d M H:i")} (ENDS)";
                return true;
            }

            // The requested date period CONTAINS a reservation
            if ($requestFrom < $dueOutAt_f && $requestTo > $dueInAt_f) {
                $this->errors[] = $errorMsg;
                $this->errors[] = "Requested {$from->format("d M H:i")} - {$to->format("d M H:i")} (CONTAINS)";
                return true;
            }

        }
        return false;
    }

}