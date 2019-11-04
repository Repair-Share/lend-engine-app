<?php

namespace AppBundle\Services\Maintenance;
use AppBundle\Entity\Maintenance;
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

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repository = $this->em->getRepository('AppBundle:Maintenance');
    }

    /**
     * @param $id
     * @return null|object
     */
    public function get($id)
    {
        return $this->repository->find($id);
    }

    /**
     * @param Maintenance $maintenance
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
        $date   = $data['date'];

        $itemRepo = $this->em->getRepository('AppBundle:InventoryItem');
        $planRepo = $this->em->getRepository('AppBundle:MaintenancePlan');

        if (!$item = $itemRepo->find($itemId)) {
            $this->errors[] = "Cannot find item with ID {$itemId}";
            return false;
        }

        if (!$plan = $planRepo->find($planId)) {
            $this->errors[] = "Cannot find maintenance plan with ID {$planId}";
            return false;
        }

        $maintenance = new Maintenance();
        $maintenance->setInventoryItem($item);
        $maintenance->setDueAt($date);
        $maintenance->setMaintenancePlan($plan);

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
}