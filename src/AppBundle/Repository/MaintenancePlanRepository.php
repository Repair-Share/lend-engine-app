<?php

namespace AppBundle\Repository;

class MaintenancePlanRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param bool $includeAll
     * @return mixed
     */
    public function findAllOrderedByName($includeAll = false)
    {
        if ($includeAll == true) {
            $sql = 'SELECT mp FROM AppBundle:MaintenancePlan mp ORDER BY mp.name ASC';
        } else {
            $sql = 'SELECT mp FROM AppBundle:MaintenancePlan mp WHERE mp.isActive = 1 ORDER BY mp.name ASC';
        }

        return $this->getEntityManager()
            ->createQuery($sql)
            ->getResult();
    }

    /**
     * @param $id
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function countProducts($id)
    {
        $sql = "SELECT COUNT(i2t.inventory_item_id) AS cnt
              FROM inventory_item_maintenance_plan i2t
              LEFT JOIN inventory_item i ON (i2t.inventory_item_id = i.id)
              WHERE i2t.maintenance_plan_id = '{$id}'
              AND i.is_active = 1 ";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
