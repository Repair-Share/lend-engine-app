<?php

namespace AppBundle\Repository;

/**
 * SiteRepository
 *
 */
class SiteRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @param $siteId
     * @return bool
     */
    public function validateDelete($siteId)
    {
        if ($this->hasItemMovements($siteId)) {
            return false;
        }
        if ($this->hasEvents($siteId)) {
            return false;
        }
        return true;
    }

    /**
     * @return array
     */
    public function findOrderedByName()
    {
        $repository = $this->getEntityManager()->getRepository('AppBundle:Site');
        $builder = $repository->createQueryBuilder('s');
        $builder->add('select', 's');
        $builder->addOrderBy('s.name');
        $query = $builder->getQuery();
        return $query->getResult();
    }

    /**
     * @param $siteId
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    private function hasItemMovements($siteId)
    {
        $sql = "SELECT COUNT(id) AS cnt
          FROM item_movement
          WHERE inventory_location_id IN
          (SELECT id FROM inventory_location WHERE site = '{$siteId}')
          ";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if ( $result[0]['cnt'] > 0 ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $siteId
     * @return bool
     */
    private function hasEvents($siteId)
    {
        $sql = "SELECT COUNT(id) AS cnt
          FROM event
          WHERE site_id = '{$siteId}'
          ";
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        if ( $result[0]['cnt'] > 0 ) {
            return true;
        } else {
            return false;
        }
    }

}
