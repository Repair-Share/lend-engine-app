<?php

namespace AppBundle\Repository;

/**
 * ItemConditionRepository
 *
 */
class ItemConditionRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @return array
     */
    public function findAllOrderedBySort()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT ic
                FROM AppBundle:ItemCondition ic
                ORDER BY ic.sort, ic.name ASC')
            ->getResult();
    }

}
