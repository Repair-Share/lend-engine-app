<?php

/**
 * Deal with reports and inventory management functions
 *
 */

namespace AppBundle\Services\Report;

use AppBundle\Entity\Loan;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;

class ReportLoanRows
{

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Best loaned items report
     * @param array $filter
     * @return array
     */
    public function run($filter = [])
    {
        $repository = $this->em->getRepository('AppBundle:LoanRow');

        $start = 0;
        $length = 1000;

        $builder = $repository->createQueryBuilder('lr');

        $builder->select('lr, i');

        $builder->leftJoin('lr.inventoryItem', 'i');
        $builder->leftJoin('lr.loan', 'l');

        if (isset($filter['search']) && $filter['search']) {
            $builder->andWhere('i.name LIKE :string OR i.sku LIKE :string');
            $builder->setParameter('string', '%'.$filter['search'].'%');
        }

        if (isset($filter['tagIds']) && count($filter['tagIds']) > 0) {
            $builder->innerJoin('i.tags', 't', 'WITH', 't.id IN (:tagIds)');
            $builder->setParameter('tagIds', $filter['tagIds']);
        }

        if (isset($filter['statuses']) && count($filter['statuses']) > 0) {
            $builder->andWhere('l.status IN (:status)');
            $builder->setParameter('status', $filter['statuses']);
        }

        if (isset($filter['item_ids']) && is_array($filter['item_ids']) && count($filter['item_ids']) > 0) {
            $builder->andWhere('lr.inventoryItem IN(:itemIds)');
            $builder->setParameter('itemIds', $filter['item_ids']);
        }

        if (isset($filter['date_from']) && $filter['date_from']) {
            $builder->andWhere('l.createdAt >= :dateFrom');
            $builder->setParameter('dateFrom', $filter['date_from'].' 00:00:00');
        }

        if (isset($filter['date_to']) && $filter['date_to']) {
            $builder->andWhere('l.createdAt <= :dateTo');
            $builder->setParameter('dateTo', $filter['date_to'].' 23:59:59');
        }

        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        $builder->addOrderBy('lr.checkedOutAt', 'ASC');

        $query = $builder->getQuery();

        return $query->getResult();

    }

}
