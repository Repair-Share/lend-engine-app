<?php

namespace AppBundle\Services\Loan;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class LoanRowService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    public $errors = [];

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em        = $em;
        $this->container = $container;
    }

    /**
     * @param $filter
     * @return mixed
     */
    public function getLoanRows($filter)
    {
        $repository = $this->em->getRepository('AppBundle:LoanRow');

        $start  = 0;
        $length = 1000;

        $builder = $repository->createQueryBuilder('lr');

        $builder->select('lr');
        $builder->leftJoin('lr.loan', 'l');

        if (isset($filter['loan_statuses']) && is_array($filter['loan_statuses']) && count($filter['loan_statuses']) > 0) {
            $builder->andWhere('l.status IN (:loanStatuses)');
            $builder->setParameter('loanStatuses', $filter['loan_statuses']);
        }

        if (isset($filter['item_ids']) && is_array($filter['item_ids']) && count($filter['item_ids']) > 0) {
            $builder->andWhere('lr.inventoryItem IN(:itemIds)');
            $builder->setParameter('itemIds', $filter['item_ids']);
        }

        if (isset($filter['dueOutAt'])) {
            $builder->andWhere('lr.dueOutAt < :dueOutAt');
            $builder->setParameter('dueOutAt', new \DateTime($filter['dueOutAt']));
        }

        if (isset($filter['dueInAt'])) {
            $builder->andWhere('lr.dueInAt < :dueInAt');
            $builder->setParameter('dueInAt', new \DateTime($filter['dueInAt']));
        }

        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        $query = $builder->getQuery();

        return $query->getResult();
    }

    /**
     * @param array $filter
     * @return array
     */
    public function getItemsOnLoan($filter = [])
    {
        $repository = $this->em->getRepository('AppBundle:LoanRow');

        $builder = $repository->createQueryBuilder('lr');

        $builder->select("lr");

        $builder->leftJoin('lr.loan', 'l');
        $builder->leftJoin('l.contact', 'c');

        $builder->where("lr.checkedInAt IS null");
        $builder->andWhere("lr.checkedOutAt IS NOT null");

        // Include only specific items
        if (isset($filter['item_ids']) && count($filter['item_ids']) > 0) {
            $builder->andWhere('IDENTITY(lr.inventoryItem) IN (:itemIds)');
            $builder->setParameter('itemIds', $filter['item_ids']);
        }

        // Show items on loan for a given contact
        if (isset($filter['contact_ids']) && count($filter['contact_ids']) > 0) {
            $builder->andWhere('l.contact IN (:contactIds)');
            $builder->setParameter('contactIds', $filter['contact_ids']);
        }

        if (isset($filter['statuses']) && count($filter['statuses']) > 0) {
            $builder->andWhere('l.status IN (:status)');
            $builder->setParameter('status', $filter['statuses']);
        }

        $query = $builder->getQuery();

        $results = $query->getResult();

        return $results;

    }

}