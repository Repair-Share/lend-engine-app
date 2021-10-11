<?php

namespace AppBundle\Services\Item;

use AppBundle\Entity\Contact;
use AppBundle\Entity\InventoryItem;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class ItemService
{
    /** @var EntityManager  */
    private $em;

    /** @var Container  */
    private $container;

    /** @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository  */
    private $repo;

    /**
     * @var array
     */
    public $errors = [];

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em        = $em;
        $this->container = $container;

        $this->repo = $this->em->getRepository('AppBundle:InventoryItem');
    }

    /**
     * @param $id
     * @return null|object
     */
    public function find($id)
    {
        return $this->repo->find($id);
    }

    /**
     * @param $criteria
     * @return array|\object[]
     */
    public function findBy($criteria)
    {
        return $this->repo->findBy($criteria);
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
        $builder = $this->repo->createQueryBuilder('item');
        $builder->leftJoin('item.inventoryLocation', 'loc');

        if (isset($filter['grouped']) && $filter['grouped'] == true) {
            $builder->select('item.id, item.name, IDENTITY(item.inventoryLocation) AS location, loc.isAvailable');
        } else {
            $builder->select('item');
        }

        // Add filters:

        if (isset($filter['tagIds']) && count($filter['tagIds']) > 0) {
            $builder->innerJoin('item.tags', 't', 'WITH', 't.id IN (:productTagIds)');
            $builder->setParameter('productTagIds', $filter['tagIds']);
        }

        if (isset($filter['isActive']) && $filter['isActive'] == false) {
            $builder->where('item.isActive = 0');
        } else {
            $builder->where('item.isActive = 1');
        }

        if (isset($filter['idSet']) && count($filter['idSet']) > 0) {
            $builder->andWhere('item.id IN ('.implode(',', $filter['idSet']).')');
        }

        if (isset($filter['type']) && in_array($filter['type'], [
                InventoryItem::TYPE_SERVICE,
                InventoryItem::TYPE_LOAN,
                InventoryItem::TYPE_KIT,
                InventoryItem::TYPE_STOCK
            ])) {
            $builder->andWhere(" item.itemType = :itemType ");
            $builder->setParameter('itemType', $filter['type']);
        }

        if (isset($filter['showOnline']) && $filter['showOnline'] == true) {
            $builder->andWhere('item.showOnWebsite = 1');
        }

        if (isset($filter['exactNameMatch']) && $filter['exactNameMatch']) {
            $builder->andWhere('item.name = :string');
            $builder->setParameter('string', trim($filter['search']));
        } else if (isset($filter['search']) && $filter['search']) {
            $builder->andWhere('item.name LIKE :string
                    OR item.sku LIKE :string
                    OR item.id = :exact
                    OR item.serial LIKE :string
                    OR item.brand LIKE :string
                    OR item.keywords LIKE :string');
            $builder->setParameter('string', '%'.trim($filter['search']).'%');
            $builder->setParameter('exact', trim($filter['search']));
        }

        if (isset($filter['barcode']) && $filter['barcode']) {
            $builder->andWhere('item.sku = :number
                        OR item.serial = :number
                        OR item.id = :number');
            $builder->setParameter('number', trim($filter['barcode']));
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
            $builder->andWhere('loc.id = :locationId');
            $builder->setParameter('locationId', $filter['locationId']);
        }

        if (isset($filter['siteId']) && $filter['siteId']) {
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

        // Only find items which can be loaned between these dates
        if (isset($filter['from']) && isset($filter['to'])) {
            $from = $filter['from'];
            $to   = $filter['to'];
            $condition1 = "lr.inventoryItem = item";
            $condition2 = "lr.dueOutAt BETWEEN '{$from} 08:59:00' AND '{$to} 21:00:01'"; // loan starts during period
            $condition3 = "lr.dueInAt BETWEEN '{$from} 08:59:00' AND '{$to} 21:00:01'"; // loan ends during period
            $condition4 = "lr.dueOutAt < '{$from} 08:59:00' AND lr.dueInAt > '{$from} 21:00:01'"; // loan starts before period and ends after
            $builder->leftJoin('AppBundle:LoanRow', 'lr', 'WITH', "{$condition1} AND ({$condition2} OR {$condition3} OR {$condition4}) ");
            $builder->andWhere('lr.inventoryItem IS NULL');
        }

        if (isset($filter['filter']) && $filter['filter'] == 'available') {
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
            if (in_array($filter['sortBy'], ['item.name', 'item.sku', 'item.createdAt'])
                && in_array($filter['sortDir'], ['ASC', 'DESC'])) {
                $builder->addOrderBy($filter['sortBy'], $filter['sortDir']);
            }
        } else {
            $builder->addOrderBy("item.name");
        }

        $query = $builder->getQuery();

        return [
            'totalResults' => $totalResults,
            'data' => $query->getResult()
        ];
    }


    /**
     * @param $id
     * @return bool
     */
    public function deleteItem($id)
    {
        if (!$this->em->isOpen()) {
            $this->em = $this->em->create(
                $this->em->getConnection(),
                $this->em->getConfiguration()
            );
        }

        /** @var \AppBundle\Entity\InventoryItem $item */
        if (!$item = $this->repo->find($id)) {
            $this->errors[] = "Could not find item with ID ".$id;
            return false;
        }

        if ($item->getInventoryLocation()->getId() == 1) {
            $this->errors[] = "You can't delete items on loan : {$id}.";
            return false;
        }

        $this->em->remove($item);

        try {
            $this->em->flush();
        } catch(\Exception $generalException) {
            $this->errors[] = 'Item failed to delete.';
            $this->errors[] = $generalException->getMessage();
            return false;
        }

        return true;
    }

    /**
     * @param InventoryItem $inventoryItem
     * @param Contact $contact
     * @return float
     */
    public function determineItemFee(InventoryItem $inventoryItem, $contact = null) {

        if ($inventoryItem->getLoanFee() !== null) {
            $fee = $inventoryItem->getLoanFee();
        } else {
            $fee = $this->container->get('settings')->getSettingValue('default_loan_fee');
        }

        if ($contact && $contact->getActiveMembership()) {
            $discount = $contact->getActiveMembership()->getMembershipType()->getDiscount();
            if ($discount != 0) {
                $fee = $fee - round($fee * $discount/100,2);
            }
        }

        return $fee;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function itemsAddedByMonth()
    {
        $sql = "SELECT DATE(i.created_at) AS d,
                  count(*) AS c
                  FROM inventory_item i
                  WHERE is_active = 1
                  GROUP BY DATE(i.created_at)";

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        // key by "Y-m"
        $data = [];
        foreach ($results AS $result) {
            $key = substr($result['d'], 0, 7);
            if (!isset($data[$key])) {
                $data[$key] = 0;
            }
            $data[$key] += $result['c'];
        }
        return $data;
    }

    /**
     * @param \DateTime $dateTo
     * @return int
     */
    public function countAllItems(\DateTime $dateTo = null)
    {
        $builder = $this->repo->createQueryBuilder('i');
        $builder->add('select', 'COUNT(i) AS qty');
        $builder->where('i.isActive = 1');
        if ($dateTo) {
            $builder->andWhere("i.createdAt < :dateTo");
            $builder->setParameter('dateTo', $dateTo->format("Y-m-01"));
        }
        $query = $builder->getQuery();
        if ( $results = $query->getResult() ) {
            $total = $results[0]['qty'];
        } else {
            $total = 0;
        }
        return $total;
    }

    /**
     * @param $filter
     * @return mixed
     */
    public function getCosts($filter = [])
    {
        $builder = $this->repo->createQueryBuilder('i');
        $builder->select('SUM(i.priceCost) AS cost, SUM(i.priceSell) AS value');

        if (isset($filter['item_id']) && $filter['item_id']) {
            $builder->andWhere('i.id = '.$filter['item_id']);
        }

        if (isset($filter['item_name']) && $filter['item_name']) {
            $builder->andWhere('i.name =  :itemName');
            $builder->setParameter('itemName', $filter['item_name']);
        }

        $builder->groupBy('i.name');

        $query = $builder->getQuery();
        return $query->getSingleResult();
    }

    /**
     * @param InventoryItem $inventoryItem
     * @return array|int|mixed
     */
    public function getInventory(InventoryItem $inventoryItem)
    {
        // Loan items and kits just have 1 since they are unique items
        if ($inventoryItem->getItemType() != 'stock') {
            return [];
        }

        $repo = $this->em->getRepository('AppBundle:ItemMovement');
        $builder = $repo->createQueryBuilder('im');

        $builder->join('im.inventoryItem', 'i');
        $builder->join('im.inventoryLocation', 'l');
        $builder->join('l.site', 's');

        $builder->add('select', 's.id AS siteId, s.name AS siteName, l.id AS locationId, l.name AS locationName, SUM(im.quantity) AS qty');

        $builder->where('i.id = '.$inventoryItem->getId());
        $builder->having('qty > 0');

        $builder->groupBy('im.inventoryLocation');

        $query = $builder->getQuery();

        if ( $results = $query->getResult() ) {
            return $results;
        } else {
            return [];
        }

    }

    /**
     * This won't work at high throughput; it's not transactional
     * Also assumes that user has got all 4-digit SKUs; will break with 3 digits
     * unless we add the REGEX doctrine extension to only search for latest 4-digit code
     * @param $stub
     * @return string
     */
    public function generateAutoSku($stub)
    {
        $lastSku = $stub;

        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $this->em->getRepository('AppBundle:InventoryItem');
        $builder = $itemRepo->createQueryBuilder('i');
        $builder->add('select', 'MAX(i.sku) AS sku');
        $builder->where("i.sku like '{$stub}%' AND i.isActive = 1");
        $query = $builder->getQuery();
        if ( $results = $query->getResult() ) {
            $lastSku = $results[0]['sku'];
        }
        $id = (int)str_replace($stub, '', $lastSku);
        $id++;
        $newSku = $stub.str_pad($id, 4, '0', STR_PAD_LEFT);
        return $newSku;
    }

    /**
     * @param InventoryItem $inventoryItem
     * @param $newType
     * @return bool
     */
    public function changeItemType(InventoryItem $inventoryItem, $newType)
    {
        /** @var \AppBundle\Services\InventoryService $inventoryService */
        $inventoryService = $this->container->get('service.inventory');

        if ($newType == $inventoryItem->getItemType()) {
            return false;
        }

        switch ($newType) {
            case InventoryItem::TYPE_LOAN:

                // Check if there are any open loans containing the item
                $loanRowRepo = $this->em->getRepository('AppBundle:LoanRow');
                $builder = $loanRowRepo->createQueryBuilder('lr');
                $builder->add('select', 'lr.id');
                $builder->where('IDENTITY(lr.inventoryItem) = '.$inventoryItem->getId());
                $builder->andWhere('lr.checkedOutAt IS NOT NULL');
                $builder->andWhere('lr.checkedInAt IS NULL');
                $query = $builder->getQuery();
                $results = $query->getResult();

                if (count($results)) {
                    $this->errors[] = "Item is on open loans. Please close all loans for this item first.";
                    return false;
                }

                // Loan items need a location
                $locationRepo = $this->em->getRepository('AppBundle:InventoryLocation');
                $location = $locationRepo->findOneBy(['isAvailable' => true]);
                $inventoryService->itemMove($inventoryItem, $location);

                break;
        }

        $inventoryItem->setItemType($newType);
        $this->em->persist($inventoryItem);

        try {
            $this->em->flush();
        } catch(\Exception $generalException) {
            $this->errors[] = 'Failed to change item type.';
            $this->errors[] = $generalException->getMessage();
            return false;
        }

        return true;
    }

}