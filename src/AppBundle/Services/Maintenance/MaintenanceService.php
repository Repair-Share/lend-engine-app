<?php

namespace AppBundle\Services\Maintenance;
use AppBundle\Entity\Maintenance;
use AppBundle\Services\InventoryService;
use Doctrine\ORM\EntityManager;

/**
 * Class MaintenanceService
 * @package AppBundle\Services
 */
class MaintenanceService
{
    /** @var array */
    public $errors = [];

    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /** @var \AppBundle\Repository\MaintenanceRepository */
    private $repository;

    /** @var InventoryService */
    private $inventoryService;

    public function __construct(EntityManager $em, InventoryService $inventoryService)
    {
        $this->em = $em;
        $this->repository = $this->em->getRepository('AppBundle:Maintenance');
        $this->inventoryService = $inventoryService;
    }

    /**
     * @param $id
     * @return null|Maintenance
     */
    public function get($id)
    {
        return $this->repository->find($id);
    }

    /**
     * @param Maintenance $maintenance
     * @return Maintenance
     */
    public function save(Maintenance $maintenance)
    {
        return $this->repository->save($maintenance);
    }

    /**
     * @param array $data
     * @return Maintenance|bool
     */
    public function scheduleMaintenance($data = [])
    {
        $itemId = $data['itemId'];
        $planId = $data['planId'];
        $date   = $data['date']; // DateTime

        $itemRepo = $this->em->getRepository('AppBundle:InventoryItem');
        $planRepo = $this->em->getRepository('AppBundle:MaintenancePlan');
        $locationRepo = $this->em->getRepository('AppBundle:InventoryLocation');

        if (isset($data['id']) && $data['id'] != null) {

            $id = $data['id'];
            if (!$maintenance = $this->get($id)) {
                $this->errors[] = "Cannot find maintenance with ID {$id}";
                return false;
            }

        } else {

            if (!$item = $itemRepo->find($itemId)) {
                $this->errors[] = "Cannot find item with ID {$itemId}";
                return false;
            }

            /** @var $plan \AppBundle\Entity\MaintenancePlan */
            if (!$plan = $planRepo->find($planId)) {
                $this->errors[] = "Cannot find maintenance type with ID {$planId}";
                return false;
            }

//            @todo validate the plan is OK for the item

            $maintenance = new Maintenance();
            $maintenance->setInventoryItem($item);
            $maintenance->setMaintenancePlan($plan);

            if ($provider = $plan->getProvider()) {
                $maintenance->setAssignedTo($provider);
            }

        }

        if (isset($data['locationId']) && $data['locationId']) {
            $location = $locationRepo->find($data['locationId']);
            $this->inventoryService->itemMove($item, $location);
        }

        if (isset($data['note']) && $data['note']) {
            $maintenance->setNotes($data['note']);
        }

        $today = new \DateTime();
        if ($date < $today) {
            $maintenance->setStatus(Maintenance::STATUS_OVERDUE);
        }
        
        $maintenance->setDueAt($date);

        $this->repository->save($maintenance);

        return $maintenance;
    }

    /**
     * @param $start
     * @param $length
     * @param $filter
     * @param array $sort
     * @return array
     */
    public function search($start, $length, $filter, $sort = [])
    {
        $builder = $this->repository->createQueryBuilder('m');
        $builder->select('m');
        $builder->join('m.inventoryItem', 'i');

        if (isset($filter['search']) && $filter['search']) {
            // Searching by text
            $builder->andWhere("(i.name LIKE :likeString OR i.sku LIKE :likeString OR i.serial = :searchString)");
            $builder->setParameter('likeString', '%'.trim($filter['search']).'%');
            $builder->setParameter('searchString', trim($filter['search']));
        }

        if (isset($filter['maintenancePlanId']) && $filter['maintenancePlanId']) {
            $builder->andWhere('IDENTITY(m.maintenancePlan) = '.(int)$filter['maintenancePlanId']);
        }

        if (isset($filter['assignedTo']) && $filter['assignedTo']) {
            $builder->andWhere('IDENTITY(m.assignedTo) = '.(int)$filter['assignedTo']);
        }

        if (isset($filter['statuses']) && $filter['statuses']) {
            $builder->andWhere("m.status IN(:statuses)");
            $builder->setParameter('statuses', $filter['statuses']);
        }

        // Run without pages to get total results:
        $queryTotalResults = $builder->getQuery();
        $totalResults = count($queryTotalResults->getResult());

        // Add pages:
        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        // Add order by:
        $builder->addOrderBy("m.dueAt", "DESC");

        // Get the data:
        $query = $builder->getQuery();

        return [
            'totalResults' => $totalResults,
            'data' => $query->getResult()
        ];

    }

    public function getTotalCosts($filter = [])
    {
        $builder = $this->repository->createQueryBuilder('m');
        $builder->select('SUM(m.totalCost) AS maintenanceCost, SUM(i.priceCost) AS itemCost');

        $builder->join('m.inventoryItem', 'i');

        if (isset($filter['date_from']) && $filter['date_from']) {
            $builder->andWhere('m.createdAt >= :dateFrom');
            $builder->setParameter('dateFrom', $filter['date_from'].' 00:00:00');
        }

        if (isset($filter['date_to']) && $filter['date_to']) {
            $builder->andWhere('m.createdAt <= :dateTo');
            $builder->setParameter('dateTo', $filter['date_to'].' 23:59:59');
        }

        if (isset($filter['item_id']) && $filter['item_id']) {
            $builder->andWhere('i.id = '.$filter['item_id']);
        }

        if (isset($filter['item_name']) && $filter['item_name']) {
            $builder->andWhere('i.name =  :itemName');
            $builder->setParameter('itemName', $filter['item_name']);
        }

        $query = $builder->getQuery();
        return $query->getSingleResult();
    }
}