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
use AppBundle\Services\Item\ItemService;
use AppBundle\Services\SettingsService;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;

class CheckOutService
{

    /** @var EntityManager  */
    private $em;

    /** @var Container  */
    private $container;

    /** @var ContactService  */
    private $contactService;

    /** @var BookingService  */
    private $reservationService;

    /** @var ItemService  */
    private $itemService;

    /** @var SettingsService  */
    private $settings;

    /** @var array  */
    public $errors = [];

    /**
     * CheckOutService constructor.
     * @param EntityManager $em
     * @param Container $container
     * @param ContactService $contactService
     * @param BookingService $reservationService
     * @param ItemService $itemService
     * @param SettingsService $settings
     */
    public function __construct(
        EntityManager $em,
        Container $container,
        ContactService $contactService,
        BookingService $reservationService,
        ItemService $itemService,
        SettingsService $settings)
    {
        $this->em        = $em;
        $this->container = $container;
        $this->contactService = $contactService;
        $this->reservationService = $reservationService;
        $this->itemService = $itemService;
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

        $loanContainsLoanItems = false;

        foreach ($loan->getLoanRows() AS $row) {

            // Move item to on-loan
            /** @var \AppBundle\Entity\InventoryLocation $location */
            if (!$location = $locationRepo->find(1)) {
                $this->errors[] = "Location with ID 1 not found. Please contact support.";
                return false;
            }

            /** @var $row \AppBundle\Entity\LoanRow */
            $inventoryItem = $row->getInventoryItem();

            if ($inventoryItem->getItemType() == InventoryItem::TYPE_LOAN) {
                // Kits and stock items are not checked out in this way
                // Set row as checked out
                $row->setCheckedOutAt(new \DateTime());
                $this->em->persist($row);

                $inventoryItem->setInventoryLocation($location);
                $this->em->persist($inventoryItem);

                $transactionRow = new ItemMovement();
                $transactionRow->setInventoryLocation($location);
                $transactionRow->setCreatedBy($user);
                $transactionRow->setInventoryItem($inventoryItem);
                $transactionRow->setLoanRow($row);
                $this->em->persist($transactionRow);

            } else if ($inventoryItem->getItemType() == InventoryItem::TYPE_STOCK) {

                // Check out the row to mark as sold
                $row->setCheckedOutAt(new \DateTime());
                $this->em->persist($row);

                // Create a negative stock movement for the items sold from this location
                $transactionRow = new ItemMovement();
                $transactionRow->setInventoryLocation($row->getItemLocation());
                $transactionRow->setCreatedBy($user);
                $transactionRow->setInventoryItem($inventoryItem);
                $transactionRow->setLoanRow($row);
                $transactionRow->setQuantity(-$row->getProductQuantity());
                $this->em->persist($transactionRow);

            }

            // Add some item history
            if ($inventoryItem->getItemType() == InventoryItem::TYPE_STOCK) {
                $note = new Note();
                $note->setInventoryItem($inventoryItem);
                $note->setCreatedBy($user);
                $locationName = $row->getItemLocation()->getSite()->getName().' / '.$row->getItemLocation()->getName();
                $note->setText("Sold ".$row->getProductQuantity()." from <strong>".$locationName."</strong> to <strong>".$loan->getContact()->getName().'</strong> on loan <strong>'.$loan->getId().'</strong>');
                $this->em->persist($note);
            } else {
                $loanContainsLoanItems = true;
                $note = new Note();
                $note->setInventoryItem($inventoryItem);
                $note->setCreatedBy($user);
                $note->setLoan($loan);
                $note->setText("Loaned to <strong>".$loan->getContact()->getName().'</strong>');
                $this->em->persist($note);
            }

            $itemFee = $row->getFee();

            // We've already charged part of this row (perhaps when reserving self-serve)
            if (isset($charges[$inventoryItem->getId()])) {
                $itemFee = $itemFee - $charges[$inventoryItem->getId()];
            }

            if ($itemFee > 0) {
                $totalRowFee = round($itemFee * $row->getProductQuantity(), 2);

                $fee = new Payment();
                $fee->setCreatedBy($user);
                $fee->setAmount($totalRowFee);
                $fee->setType(Payment::PAYMENT_TYPE_FEE);
                $fee->setContact($loan->getContact());
                $fee->setLoan($loan);
                $fee->setInventoryItem($inventoryItem);
                $this->em->persist($fee);
            }

        }

        // Mark the loan as checked out
        if ($loanContainsLoanItems == true) {
            $loan->setStatus(Loan::STATUS_ACTIVE);
            $loan->setTimeOut(new \DateTime());
            $loan->setReturnDate();
            $checkoutNoteText = "Checked out loan. ";
        } else {
            // Only contains stock items
            $loan->setStatus(Loan::STATUS_CLOSED);
            $checkoutNoteText = "Completed sale. ";
        }

        $loan->setTotalFee();

        // Save any changes to the loan
        $this->em->persist($loan);

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

            if ($item->getItemType() == InventoryItem::TYPE_STOCK) {

                // validate that the qty requested is actually in stock
                $inventory = $this->itemService->getInventory($item);
                foreach ($inventory AS $i) {
                    if ($i['locationId'] == $loanRow->getItemLocation()->getId()) {
                        if ($i['qty'] < $loanRow->getProductQuantity()) {
                            $this->errors[] = 'Not enough stock of "'.$item->getName().'" in '.$i['locationName'];
                            return false;
                        }
                    }
                }

            } else {

                if ($this->isItemReserved($item, $from, $to, $loanId)) {
                    return false;
                }

                if ($item->getInventoryLocation()) {
                    // Kits don't have a location
                    if ($item->getInventoryLocation()->getId() == 1) {
                        $this->errors[] = 'Item "'.$item->getName().'" is already on loan.';
                        return false;
                    }
                    if ($item->getInventoryLocation()->getIsAvailable() != true) {
                        $this->errors[] = 'Item "'.$item->getName().'" is in a reserved location ('.$item->getInventoryLocation()->getName().').';
                        return false;
                    }
                }

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