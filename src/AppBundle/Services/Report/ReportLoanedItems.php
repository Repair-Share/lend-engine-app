<?php

/**
 * Deal with reports and inventory management functions
 *
 */

namespace AppBundle\Services\Report;

use AppBundle\Entity\Loan;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;

class ReportLoanedItems
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

        if (!isset($filter['group_by'])) {
            $filter['group_by'] = 'product';
        }

        $builder = $repository->createQueryBuilder('lr');

        $builder->select('SUM(lr.productQuantity) AS qty');
        $builder->addSelect('SUM(lr.fee) AS fees');

        $builder->leftJoin('lr.inventoryItem', 'i');
        $builder->leftJoin('lr.loan', 'l');

        $builder->andWhere('l.status NOT IN (:reservedStatus, :cancelledStatus)');
        $builder->setParameter('reservedStatus', Loan::STATUS_RESERVED);
        $builder->setParameter('cancelledStatus', Loan::STATUS_CANCELLED);

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
            $builder->andWhere('l.timeOut >= :dateFrom');
            $builder->setParameter('dateFrom', $filter['date_from'].' 00:00:00');
        }

        if (isset($filter['date_to']) && $filter['date_to']) {
            $builder->andWhere('l.timeOut <= :dateTo');
            $builder->setParameter('dateTo', $filter['date_to'].' 23:59:59');
        }

        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        switch ($filter['group_by']) {
            case "product":
                $builder->addSelect('i.name AS group_name');
                $builder->addGroupBy("i.name");
                break;
            case "category":
                $builder->leftJoin('i.category', 'c');
                $builder->addSelect('c.name AS group_name');
                $builder->addGroupBy("i.category");
                break;
            default:
                if (preg_match("/customField([0-9]+)/", $filter['group_by'], $matches)) {
                    $builder->join('AppBundle:ProductFieldValue', 'cfv', Expr\Join::WITH, 'cfv.inventoryItem = lr.inventoryItem');

                    $builder->join('AppBundle:ProductField', 'cf', Expr\Join::WITH, 'cfv.productField = cf.id');
                    $builder->addSelect('cf.type AS field_type');

                    $fieldId = $matches[1];
                    $builder->andWhere('cfv.productField = :fieldId');
                    $builder->setParameter('fieldId', $fieldId);

                    $builder->addSelect('cfv.fieldValue AS group_name');
                    $builder->addGroupBy("cfv.fieldValue");
                }
        }

        $builder->addOrderBy('qty', 'DESC');

        $query = $builder->getQuery();

        return $query->getResult();

    }

}
