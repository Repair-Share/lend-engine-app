<?php

namespace AppBundle\Services\Report;

use Doctrine\ORM\EntityManager;

class ReportPayments
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
     * Payments list
     * @param array $filter
     * @return array
     */
    public function run($filter = array())
    {
        $repository = $this->em->getRepository('AppBundle:Payment');

        $start = 0;
        $length = 1000;

        $builder = $repository->createQueryBuilder('p');
        $builder->select("
            CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) AS contact_name,
            c.id AS contact_id,
            pm.name AS payment_method_name,
            p.createdAt AS date,
            p.type AS type,
            p.amount AS amount,
            p.note AS note,
            p.pspCode AS pspCode,
            i.name AS itemName,
            IDENTITY(p.deposit) AS deposit_id,
            IDENTITY(p.loan) AS loan_id,
            IDENTITY(p.membership) AS membership_id
            ");

        $builder->leftJoin('p.contact', 'c');
        $builder->leftJoin('p.paymentMethod', 'pm');
        $builder->leftJoin('p.inventoryItem', 'i');

        if (isset($filter['search']) && $filter['search']) {
            $builder->andWhere('c.firstName LIKE :string');
            $builder->orWhere('c.lastName LIKE :string');
            $builder->orWhere('p.note LIKE :string');
            $builder->setParameter('string', '%'.$filter['search'].'%');
        }

        if (isset($filter['payment_method']) && $filter['payment_method']) {
            $builder->andWhere('p.paymentMethod = :payment_method');
            $builder->setParameter('payment_method', $filter['payment_method']);
        }

        if (isset($filter['payment_type']) && $filter['payment_type']) {
            switch ($filter['payment_type']) {
                case 'loan':
                    $builder->andWhere('p.loan IS NOT NULL');
                    break;
                case 'membership':
                    $builder->andWhere('p.membership IS NOT NULL');
                    break;
                case 'other':
                    $builder->andWhere("p.loan IS NULL");
                    $builder->andWhere("p.membership IS NULL");
                    break;
            }
        }

        if (isset($filter['date_from']) && $filter['date_from']) {
            $builder->andWhere('p.createdAt >= :dateFrom');
            $builder->setParameter('dateFrom', $filter['date_from'].' 00:00:00');
        }

        if (isset($filter['date_to']) && $filter['date_to']) {
            $builder->andWhere('p.createdAt <= :dateTo');
            $builder->setParameter('dateTo', $filter['date_to'].' 23:59:59');
        }

        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        $builder->addOrderBy('p.paymentDate', 'DESC');

        $query = $builder->getQuery();

        return $query->getResult();

    }

}
