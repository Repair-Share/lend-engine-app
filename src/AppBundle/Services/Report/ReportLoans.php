<?php

namespace AppBundle\Services\Report;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;

class ReportLoans
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
     * Loans report
     * @param array $filter
     * @return array
     */
    public function run($filter = array())
    {
        $repository = $this->em->getRepository('AppBundle:Loan');

        $start = 0;
        $length = 1000;

        if (!isset($filter['group_by'])) {
            $filter['group_by'] = 'status';
        }

        $builder = $repository->createQueryBuilder('l');

        $builder->select('COUNT(l) AS qty');

        $builder->leftJoin('l.contact', 'c');

        if (isset($filter['search']) && $filter['search']) {
            $builder->andWhere('c.firstName LIKE :string OR c.lastName LIKE :string');
            $builder->setParameter('string', '%'.$filter['search'].'%');
        }

        if (isset($filter['statuses']) && count($filter['statuses']) > 0) {
            $builder->andWhere('l.status IN (:status)');
            $builder->setParameter('status', $filter['statuses']);
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
            case "status":
                $builder->addSelect('l.status AS group_name');
                $builder->addGroupBy("l.status");
                break;
            case "member":
                $builder->addSelect("CONCAT(c.firstName, ' ', c.lastName) AS group_name");
                $builder->addGroupBy("l.contact");
                break;
            default:
                if (preg_match("/customField([0-9]+)/", $filter['group_by'], $matches)) {
                    $builder->join('AppBundle:ContactFieldValue', 'cfv', Expr\Join::WITH, 'cfv.contact = l.contact');

                    $builder->join('AppBundle:ContactField', 'cf', Expr\Join::WITH, 'cfv.contactField = cf.id');
                    $builder->addSelect('cf.type AS field_type');

                    $fieldId = $matches[1];
                    $builder->andWhere('cfv.contactField = :fieldId');
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
