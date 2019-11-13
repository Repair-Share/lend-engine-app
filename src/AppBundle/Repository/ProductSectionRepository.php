<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class ProductSectionRepository extends EntityRepository
{

    /**
     * @return array
     */
    public function findAllOrderedBySort()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT s FROM AppBundle:ProductSection s ORDER BY s.sort, s.name ASC')
            ->getResult();
    }


}
