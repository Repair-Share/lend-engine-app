<?php

/**
 * Deal with reports and inventory management functions
 *
 */

namespace AppBundle\Services;

use AppBundle\Entity\Contact;
use AppBundle\Entity\InventoryItem;
use AppBundle\Entity\InventoryLocation;
use AppBundle\Entity\ItemMovement;
use AppBundle\Entity\Loan;
use AppBundle\Entity\LoanRow;
use AppBundle\Entity\Note;
use AppBundle\Entity\Payment;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
     * Item list
     * @param int $start
     * @param int $length
     * @param array $filter
     * @return array
     */
    public function itemSearch($start = 0, $length = 1000, $filter = array())
    {
        $repository = $this->em->getRepository('AppBundle:InventoryItem');

        $builder = $repository->createQueryBuilder('item');

        $builder->select('item');

        if (isset($filter['tagIds']) && count($filter['tagIds']) > 0) {
            $builder->innerJoin('item.tags', 't', 'WITH', 't.id IN (:productTagIds)');
            $builder->setParameter('productTagIds', $filter['tagIds']);
        }

        // Add filters:

        if (isset($filter['isActive']) && $filter['isActive'] == false) {
            $builder->where('item.isActive = 0');
        } else {
            $builder->where('item.isActive = 1');
        }

        if (isset($filter['showOnline']) && $filter['showOnline'] == true) {
            $builder->andWhere('item.showOnWebsite = 1');
        }

        if (isset($filter['search']) && $filter['search']) {
            $builder->andWhere('item.name LIKE :string
                        OR item.sku LIKE :string
                        OR item.serial LIKE :string
                        OR item.brand LIKE :string
                        OR item.keywords LIKE :string');
            $builder->setParameter('string', '%'.trim($filter['search']).'%');
        }

        if (isset($filter['serial']) && $filter['serial']) {
            $builder->andWhere('item.serial = :serial');
            $builder->setParameter('serial', $filter['serial']);
        }

        if (isset($filter['itemId']) && $filter['itemId']) {
            $builder->andWhere('item.id = :itemId');
            $builder->setParameter('itemId', $filter['itemId']);
        }

        if (isset($filter['locationId']) && $filter['locationId']) {
            $builder->leftJoin('item.inventoryLocation', 'loc');
            $builder->andWhere('loc.id = :locationId');
            $builder->setParameter('locationId', $filter['locationId']);
        }

        if (isset($filter['siteId']) && $filter['siteId']) {
            $builder->leftJoin('item.inventoryLocation', 'loc');
            $builder->andWhere('loc.site = :siteId');
            $builder->setParameter('siteId', $filter['siteId']);
        }

        if (isset($filter['assignedTo']) && $filter['assignedTo']) {
            $builder->andWhere('item.assignedTo = :assignedTo');
            $builder->setParameter('assignedTo', $filter['assignedTo']);
        }

        if (isset($filter['itemCondition']) && $filter['itemCondition']) {
            $builder->andWhere('item.condition = :itemCondition');
            $builder->setParameter('itemCondition', $filter['itemCondition']);
        }

        if (isset($filter['filter']) && $filter['filter'] == 'available') {
            $builder->leftJoin('item.inventoryLocation', 'loc');
            $builder->andWhere('loc.isAvailable = 1');
        }

        // First get the total count:

        $queryTotalResults = $builder->getQuery();
        $totalResults = count($queryTotalResults->getResult());

        // Add limit:

        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        // Add order by:

        if (isset($filter['sortBy']) && isset($filter['sortDir'])) {
            $builder->addOrderBy($filter['sortBy'], $filter['sortDir']);
        } else {
            $builder->addOrderBy("item.name, item.inventoryLocation");
        }

        $query = $builder->getQuery();

        return [
            'totalResults' => $totalResults,
            'data' => $query->getResult()
        ];
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
     * @param Contact $contact
     * @param $userNote
     * @param $cost
     * @return bool
     */
    public function itemMove(InventoryItem $inventoryItem, InventoryLocation $toLocation, LoanRow $loanRow = null, Contact $contact = null, $userNote = '', $cost = 0)
    {

        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        $currencySymbol  = $this->container->get('settings')->getSettingValue('org_currency');

        $transactionRow = new ItemMovement();
        $transactionRow->setInventoryLocation($toLocation);
        $transactionRow->setInventoryItem($inventoryItem);
        $transactionRow->setCreatedBy($user);

        $oldLocation = $inventoryItem->getInventoryLocation();

        // If it's for a loan row, set the row as returned
        if ($loanRow) {
            $loanRow->setCheckedInAt(new \DateTime("now"));
            $this->em->persist($loanRow);
            $noteText = 'Checked in to <strong>'.$toLocation->getSite()->getName().' / '.$toLocation->getName().'</strong> from loan '.$loanRow->getLoan()->getId().'. ';
        } else if ($oldLocation == $toLocation) {
            // not moving
            $noteText = '';
        } else {
            $noteText = 'Moved to <strong>'.$toLocation->getSite()->getName().' / '.$toLocation->getName().'</strong>. ';
        }

        if ($contact) {
            $transactionRow->setAssignedTo($contact);
            $inventoryItem->setAssignedTo($contact);
            $noteText .= 'Assigned to <strong>'.$contact->getName().'</strong>. ';
        } else {
            $transactionRow->setAssignedTo(null);
            $inventoryItem->setAssignedTo(null);
        }

        // Update the item itself
        $inventoryItem->setInventoryLocation($toLocation);

        $note = new Note();
        $note->setCreatedBy($user);
        $note->setInventoryItem($inventoryItem);
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
     * @param $fromLocation
     * @return bool
     */
    public function itemRemove(InventoryItem $inventoryItem, InventoryLocation $fromLocation, $userNote = '')
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        $transactionRow = new ItemMovement();
        $transactionRow->setInventoryLocation($fromLocation);
        $transactionRow->setInventoryItem($inventoryItem);
        $transactionRow->setCreatedBy($user);

        // Deactivate the item
        $inventoryItem->setIsActive(false);
        $inventoryItem->setAssignedTo(null);

        $note = new Note();
        $note->setCreatedBy($user);
        $note->setInventoryItem($inventoryItem);
        $noteText = 'Removed from "'.$fromLocation->getSite()->getName().' / '.$fromLocation->getName().'"';
        if ($userNote != '') {
            $noteText .= " with note:\n".$userNote;
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
