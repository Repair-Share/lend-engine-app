<?php

namespace AppBundle\Repository;

class MembershipTypeRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param bool $includeAll
     * @return mixed
     */
    public function findAllOrderedByName($includeAll = false)
    {
        if ($includeAll == true) {
            $sql = 'SELECT mt FROM AppBundle:MembershipType mt ORDER BY mt.name ASC';
        } else {
            $sql = 'SELECT mt FROM AppBundle:MembershipType mt WHERE mt.isActive = 1 ORDER BY mt.name ASC';
        }

        return $this->getEntityManager()
            ->createQuery($sql)
            ->getResult();
    }
}
