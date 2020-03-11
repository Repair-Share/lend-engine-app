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

}