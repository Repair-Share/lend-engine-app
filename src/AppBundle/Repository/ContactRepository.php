<?php

namespace AppBundle\Repository;
use AppBundle\Entity\Contact;

/**
 * Class ContactRepository
 * @package AppBundle\Repository
 */
class ContactRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @param Contact $contact
     * @return Contact|bool
     *
     *
     * Don't call this when in a loop (eg schedule) as it won't use the right entityManager
     *
     *
     */
    public function save(Contact $contact)
    {
        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->flush($contact);
        return $contact;
    }

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
