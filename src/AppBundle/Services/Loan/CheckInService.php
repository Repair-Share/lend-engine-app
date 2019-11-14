<?php

namespace AppBundle\Services\Loan;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use AppBundle\Repository\SettingRepository;
use AppBundle\Services\Contact\ContactService;
use AppBundle\Services\Booking\BookingService;
use AppBundle\Services\InventoryService;
use AppBundle\Services\Maintenance\MaintenanceService;
use AppBundle\Services\SettingsService;
use AppBundle\Services\WaitingList\WaitingListService;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Security;

class CheckInService
{

    /** @var EntityManager  */
    private $em;

    /** @var ContactService  */
    private $contactService;

    /** @var WaitingListService  */
    private $waitingListService;

    /** @var InventoryService */
    private $inventoryService;

    /** @var MaintenanceService */
    private $maintenanceService;

    /** @var TokenStorageInterface  */
    private $tokenStorage;

    /** @var array  */
    public $errors = [];

    /**
     * CheckInService constructor.
     * @param EntityManager $em
     * @param ContactService $contactService
     * @param InventoryService $inventoryService
     * @param WaitingListService $waitingListService
     * @param MaintenanceService $maintenanceService
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManager $em,
        ContactService $contactService,
        InventoryService $inventoryService,
        WaitingListService $waitingListService,
        MaintenanceService $maintenanceService,
        TokenStorageInterface $tokenStorage)
    {
        $this->em        = $em;
        $this->contactService = $contactService;
        $this->inventoryService = $inventoryService;
        $this->waitingListService = $waitingListService;
        $this->maintenanceService = $maintenanceService;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param InventoryLocation $location
     * @param LoanRow $loanRow
     * @param string $userNote
     * @param int $checkInFee
     * @param null $assignToContact
     * @return bool
     */
    public function checkInRow(InventoryLocation $location,
                                 LoanRow $loanRow,
                                 $userNote = '',
                                 $checkInFee = 0,
                                 $assignToContact = null) {

        $user           = $this->tokenStorage->getToken()->getUser();
        $loan           = $loanRow->getLoan();
        $inventoryItem  = $loanRow->getInventoryItem();

        if ( $this->inventoryService->itemMove($inventoryItem, $location, $loanRow, $assignToContact, $userNote) ) {

            $noteText = 'Checked in <strong>'.$inventoryItem->getName().'</strong>';
            if ($userNote) {
                $noteText .= '<br>'.$userNote;
            }

            // Add a fee
            if ($checkInFee > 0) {
                $payment = new Payment();
                $payment->setAmount(-$checkInFee);
                $payment->setContact($loanRow->getLoan()->getContact());
                $payment->setLoan($loanRow->getLoan());
                $payment->setNote("Check-in fee for ".$inventoryItem->getName().".");
                $payment->setCreatedBy($user);
                $payment->setInventoryItem($inventoryItem);
                $this->em->persist($payment);

                try {
                    $this->em->flush();
                    $noteText .= ' (check-in fee '.number_format($checkInFee, 2).")";
                    $this->contactService->recalculateBalance($loanRow->getLoan()->getContact());
                } catch (\Exception $generalException) {

                }
            }

            // Add a note to the loan and contact
            $note = new Note();
            $note->setCreatedBy($user);
            $note->setLoan($loan);
            $note->setContact($loanRow->getLoan()->getContact());
            $note->setText($noteText);
            $this->em->persist($note);
            try {
                $this->em->flush();
            } catch (\Exception $generalException) {
                $this->errors[] = "There was an error checking in item: " . $inventoryItem->getName();
            }

            // If maintenance is required, schedule it now
            /** @var \AppBundle\Entity\MaintenancePlan $plan */
            $maintenanceTime = new \DateTime();
            foreach ($inventoryItem->getMaintenancePlans() AS $plan) {
                if ($plan->getAfterEachLoan() == true) {
                    $data = [
                        'itemId' => $inventoryItem->getId(),
                        'planId' => $plan->getId(),
                        'date' => $maintenanceTime->modify("-1 hour") // so that it's created overdue
                    ];
                    $this->maintenanceService->scheduleMaintenance($data);
                }
            }

            // Process items that may be on the waiting list
            $this->waitingListService->process($inventoryItem);

            return true;
        }

        return false;

    }


}