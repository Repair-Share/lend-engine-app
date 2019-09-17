<?php

namespace AppBundle\Repository;

class MembershipTypeRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @return array
     */
    public function findAllOrderedByName()
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT mt FROM AppBundle:MembershipType mt WHERE mt.isActive = 1 ORDER BY mt.name ASC'
            )
            ->getResult();
    }
}
