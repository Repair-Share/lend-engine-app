<?php

/**
 * Deal with reports and inventory management functions
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

    /**
     * @var EntityManager
     */
    private $em;

    private $container;

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em        = $em;
        $this->container = $container;
    }

    /**
     * @return int
     *
     * @deprecated
     *
     */
    public function countAllInventory()
    {
        $repository = $this->em->getRepository('AppBundle:ItemMovement');

        $builder = $repository->createQueryBuilder('i');
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

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $currencySymbol  = $this->container->get('settings')->getSettingValue('org_currency');

        $transactionRow = new ItemMovement();
        $transactionRow->setInventoryLocation($toLocation);
        $transactionRow->setInventoryItem($inventoryItem);
        $transactionRow->setCreatedBy($user);

        $oldLocation = $inventoryItem->getInventoryLocation();

        $note = new Note();
        $note->setCreatedBy($user);
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
            $payment->setCreatedBy($user);
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
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        // Deactivate the item
        $inventoryItem->setIsActive(false);
        $inventoryItem->setAssignedTo(null);
        $inventoryItem->setInventoryLocation(null);

        $note = new Note();
        $note->setCreatedBy($user);
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
     * @param array $filter
     * @return array
     */
    public function getItemsOnLoan($filter = [])
    {
        $repository = $this->em->getRepository('AppBundle:LoanRow');

        $builder = $repository->createQueryBuilder('lr');

        $builder->select("lr");

        $builder->leftJoin('lr.loan', 'l');
        $builder->leftJoin('l.contact', 'c');

        $builder->where("lr.checkedInAt IS null");
        $builder->andWhere("lr.checkedOutAt IS NOT null");

        // Include only specific items
        if (isset($filter['item_ids']) && count($filter['item_ids']) > 0) {
            $builder->andWhere('IDENTITY(lr.inventoryItem) IN (:itemIds)');
            $builder->setParameter('itemIds', $filter['item_ids']);
        }

        // Show items on loan for a given contact
        if (isset($filter['contact_ids']) && count($filter['contact_ids']) > 0) {
            $builder->andWhere('l.contact IN (:contactIds)');
            $builder->setParameter('contactIds', $filter['contact_ids']);
        }

        if (isset($filter['statuses']) && count($filter['statuses']) > 0) {
            $builder->andWhere('l.status IN (:status)');
            $builder->setParameter('status', $filter['statuses']);
        }

        $query = $builder->getQuery();

        $results = $query->getResult();

        return $results;

    }

}
