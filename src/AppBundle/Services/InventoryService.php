<?php

/**
 * Service to deal with moving inventory around
 *
 */

namespace AppBundle\Services;

use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class InventoryService
{
    /** @var array */
    public $errors = [];

    /** @var EntityManager */
    private $em;

    /** @var Container */
    private $container;

    /** @var \AppBundle\Repository\InventoryItemRepository $inventoryItemRepo */
    private $inventoryItemRepo;

    /** @var \AppBundle\Repository\InventoryLocationRepository $inventoryLocationRepo */
    private $inventoryLocationRepo;

    /** @var \AppBundle\Repository\ItemMovementRepository $itemMovementRepo  */
    private $itemMovementRepo;

    /** @var \AppBundle\Entity\Contact */
    private $user;

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em        = $em;
        $this->container = $container;

        $this->inventoryItemRepo = $em->getRepository('AppBundle:InventoryItem');
        $this->inventoryLocationRepo = $em->getRepository('AppBundle:InventoryLocation');
        $this->itemMovementRepo = $em->getRepository('AppBundle:ItemMovement');

        $this->user = $this->container->get('security.token_storage')->getToken()->getUser();
    }

    /**
     * @return int
     *
     * @deprecated
     *
     */
    public function countAllInventory()
    {
        $builder = $this->itemMovementRepo->createQueryBuilder('i');
        $builder->select('COUNT(*) AS qty');
        $builder->addGroupBy("i.inventoryLocation, i.inventoryItem");
        $query = $builder->getQuery();
        if ( $results = $query->getResult() ) {
            $total = count($results);
        } else {
            $total = 0;
        }
        return $total;
    }

    /**
     * @param InventoryItem $inventoryItem
     * @param InventoryLocation $toLocation
     * @param LoanRow $loanRow
     * @param $userNote
     * @param $cost
     * @return bool
     */
    public function itemMove(InventoryItem $inventoryItem,
                             InventoryLocation $toLocation,
                             LoanRow $loanRow = null,
                             $userNote = '', $cost = 0)
    {

        $currencySymbol  = $this->container->get('settings')->getSettingValue('org_currency');

        $transactionRow = new ItemMovement();
        $transactionRow->setInventoryLocation($toLocation);
        $transactionRow->setInventoryItem($inventoryItem);
        $transactionRow->setCreatedBy($this->user);

        $oldLocation = $inventoryItem->getInventoryLocation();

        $note = new Note();
        $note->setCreatedBy($this->user);
        $note->setInventoryItem($inventoryItem);

        // If it's for a loan row, set the row as returned
        if ($loanRow) {
            $loanRow->setCheckedInAt(new \DateTime("now"));
            $this->em->persist($loanRow);
            $noteText = 'Checked in to <strong>'.$toLocation->getSite()->getName().' / '.$toLocation->getName().'</strong>.';
            $note->setLoan($loanRow->getLoan());
        } else if ($oldLocation == $toLocation) {
            // not moving
            $noteText = '';
        } else {
            $noteText = 'Moved to <strong>'.$toLocation->getSite()->getName().' / '.$toLocation->getName().'</strong>. ';
        }

        // Update the item itself
        $inventoryItem->setInventoryLocation($toLocation);

        if ($userNote != '') {
            if ($noteText != '') {
                $noteText .= "\n";
            }
            $noteText .= $userNote;
        }

        if ($cost != 0) {
            $payment = new Payment();
            $payment->setCreatedBy($this->user);
            $payment->setAmount($cost);
            $payment->setNote($userNote);
            $payment->setType(Payment::PAYMENT_TYPE_COST);
            $payment->setInventoryItem($inventoryItem);
            $this->em->persist($payment);
            $noteText .= "\nCost: {$currencySymbol} ".number_format($cost, 2);
        }

        $note->setText($noteText);

        $this->em->persist($note);
        $this->em->persist($inventoryItem);
        $this->em->persist($transactionRow);

        try {
            $this->em->flush();
            return true;
        } catch (DBALException $e) {
            return false;
        }

    }


    /**
     * @param $inventoryItem
     * @return bool
     */
    public function itemRemove(InventoryItem $inventoryItem, $userNote = '')
    {
        // Deactivate the item
        $inventoryItem->setIsActive(false);
        $inventoryItem->setAssignedTo(null);
        $inventoryItem->setInventoryLocation(null);

        $note = new Note();
        $note->setCreatedBy($this->user);
        $note->setInventoryItem($inventoryItem);
        $noteText = 'Archived.';
        if ($userNote != '') {
            $noteText .= " with note:\n".$userNote;
        }
        $note->setText($noteText);

        $this->em->persist($note);
        $this->em->persist($inventoryItem);

        try {
            $this->em->flush();
            return true;
        } catch (DBALException $e) {
            return false;
        }
    }

    /**
     * @param $itemId
     * @param $qty
     * @param $locationId
     * @param string $noteText
     * @return bool
     */
    public function addInventory($itemId, $qty, $locationId, $noteText = '')
    {
        if (!$location = $this->inventoryLocationRepo->find($locationId)) {
            $this->errors[] = "No location: {$locationId}";
            return false;
        }

        if (!$item = $this->inventoryItemRepo->find($itemId)) {
            $this->errors[] = "No item: {$itemId}";
            return false;
        }

        if ($qty < 1) {
            $this->errors[] = "Qty {$qty} cannot be negative";
            return false;
        }

        if ($item->getItemType() != InventoryItem::TYPE_STOCK) {
            $this->errors[] = "Must be a stock item";
            return false;
        }

        $movement = new ItemMovement();
        $movement->setInventoryItem($item);
        $movement->setCreatedBy($this->user);
        $movement->setQuantity($qty);
        $movement->setInventoryLocation($location);
        $this->em->persist($movement);

        $note = new Note();
        $note->setCreatedBy($this->user);
        $note->setInventoryItem($item);
        $note->setText('Added ' . $qty . ' to <strong>' . $location->getSite()->getName() . ' / ' . $location->getName() . '</strong>. ' . $noteText);
        $this->em->persist($note);

        try {
            $this->em->flush();
            return true;
        } catch (DBALException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $itemId
     * @param $qty
     * @param $locationId
     * @param string $noteText
     * @return bool
     */
    public function removeInventory($itemId, $qty, $locationId, $noteText = '')
    {
        if (!$location = $this->inventoryLocationRepo->find($locationId)) {
            $this->errors[] = "No location: {$locationId}";
            return false;
        }

        if (!$item = $this->inventoryItemRepo->find($itemId)) {
            $this->errors[] = "No item: {$itemId}";
            return false;
        }

        if ($qty < 1) {
            $this->errors[] = "Qty {$qty} cannot be negative";
            return false;
        }

        if ($item->getItemType() != InventoryItem::TYPE_STOCK) {
            $this->errors[] = "Must be a stock item";
            return false;
        }

        $movement = new ItemMovement();
        $movement->setInventoryItem($item);
        $movement->setCreatedBy($this->user);
        $movement->setQuantity(-$qty);
        $movement->setInventoryLocation($location);
        $this->em->persist($movement);

        $note = new Note();
        $note->setCreatedBy($this->user);
        $note->setInventoryItem($item);
        $note->setText('Removed ' . $qty . ' from <strong>' . $location->getSite()->getName() . ' / ' . $location->getName() . '</strong>. ' . $noteText);
        $this->em->persist($note);

        try {
            $this->em->flush();
            return true;
        } catch (DBALException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

}
