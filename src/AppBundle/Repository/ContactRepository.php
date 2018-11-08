<?php

namespace AppBundle\Repository;
use AppBundle\Entity\Contact;

/**
 * ContactRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ContactRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @return integer
     */
    public function countAllMembers()
    {
        $repository = $this->getEntityManager()->getRepository('AppBundle:Contact');
        $builder = $repository->createQueryBuilder('c');
        $builder->add('select', 'COUNT(c) AS qty');
        $builder->where('c.id > 1');
        $builder->andWhere('c.activeMembership > 0');
        $query = $builder->getQuery();
        if ( $results = $query->getResult() ) {
            $total = $results[0]['qty'];
        } else {
            $total = 0;
        }
        return $total;
    }

    /**
     * @return integer
     */
    public function countActiveContacts()
    {
        $repository = $this->getEntityManager()->getRepository('AppBundle:Contact');
        $builder = $repository->createQueryBuilder('c');
        $builder->add('select', 'COUNT(c) AS qty');
        $builder->where('c.id > 1');
        $builder->andWhere('c.isActive > 0');
        $query = $builder->getQuery();
        if ( $results = $query->getResult() ) {
            $total = $results[0]['qty'];
        } else {
            $total = 0;
        }
        return $total;
    }

    /**
     * @return array
     */
    public function findAllStaff()
    {
        $repository = $this->getEntityManager()->getRepository('AppBundle:Contact');

        $builder = $repository->createQueryBuilder('c');
        $builder->select('c');
        $builder->where('c.roles LIKE :role');
        $builder->andWhere('c.id > 1');
        $builder->setParameter('role', '%ADMIN%');

        $builder->addOrderBy("c.firstName");
        $query = $builder->getQuery();

        return $query->getResult();
    }

}
