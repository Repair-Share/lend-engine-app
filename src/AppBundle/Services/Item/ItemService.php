<?php

namespace AppBundle\Services\Item;

use AppBundle\Entity\Contact;
use AppBundle\Entity\InventoryItem;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class ItemService
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
     * @var array
     */
    public $errors = [];

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em        = $em;
        $this->container = $container;
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

        $repo = $this->em->getRepository('AppBundle:InventoryItem');

        /** @var \AppBundle\Entity\InventoryItem $item */
        if (!$item = $repo->find($id)) {
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
        $repository = $this->em->getRepository('AppBundle:InventoryItem');
        $builder = $repository->createQueryBuilder('i');
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

}