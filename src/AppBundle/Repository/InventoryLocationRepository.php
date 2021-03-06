<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * InventoryLocationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class InventoryLocationRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @param string $locationType
     * @param integer $siteId
     * @return array
     */
    public function findOrderedByName($locationType = '', $siteId = null)
    {
        $repository = $this->getEntityManager()->getRepository('AppBundle:InventoryLocation');
        $builder = $repository->createQueryBuilder('l');
        $builder->add('select', 'l');
        if ($locationType == 'reserved') {
            $builder->where('l.isAvailable = 0');
            $builder->andWhere('l.isActive = 1');
        } else if ($locationType == 'available') {
            $builder->where('l.isAvailable = 1');
            $builder->andWhere('l.isActive = 1');
        } else if ($locationType == 'notOnLoan') {
            $builder->where('l.id != 1');
            $builder->andWhere('l.isActive = 1');
        } else if ($locationType == 'websiteFilter') {
            $builder->where('l.id != 1');
            $builder->andWhere('l.isAvailable = 1');
        }
        if ($siteId) {
            $builder->andWhere('l.site = '.$siteId);
        }
        $builder->addOrderBy('l.site, l.isAvailable, l.name');
        $query = $builder->getQuery();
        return $query->getResult();

    }

    /**
     * Validated that we can delete the location
     * @param $id
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    public function validateDelete($id)
    {
        if ($this->hasInventoryMovements($id)) {
            return false;
        }
        if ($this->isSetAsDefaultCheckInLocation($id)) {
            return false;
        }
        return true;
    }

    /**
     * @param $id
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    private function hasInventoryMovements($id)
    {
        $sql = "SELECT COUNT(id) AS cnt FROM item_movement WHERE inventory_location_id = '{$id}'";
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
     * @throws \Doctrine\DBAL\DBALException
     */
    private function isSetAsDefaultCheckInLocation($siteId)
    {
        $sql = "SELECT COUNT(id) AS cnt
          FROM site
          WHERE default_check_in_location = '{$siteId}'
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
