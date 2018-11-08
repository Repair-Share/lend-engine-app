<?php

namespace AppBundle\Repository;

/**
 * PaymentMethodRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PaymentMethodRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param bool $includeInactive
     * @return array
     */
    public function findAllOrderedByName($includeInactive = false)
    {
        if ($includeInactive) {
            $q = 'SELECT pm FROM AppBundle:PaymentMethod pm ORDER BY pm.name ASC';
        } else {
            $q = 'SELECT pm FROM AppBundle:PaymentMethod pm WHERE pm.isActive = true ORDER BY pm.name ASC';
        }

        return $this->getEntityManager()
            ->createQuery($q)
            ->getResult();
    }
}
