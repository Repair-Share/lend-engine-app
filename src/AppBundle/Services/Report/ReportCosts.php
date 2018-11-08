<?php

namespace AppBundle\Services\Report;

use Doctrine\ORM\EntityManager;

class ReportCosts
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
            i.name AS item_name,
            i.sku AS item_sku,
            i.serial AS item_serial
            ");

        $builder->leftJoin('p.inventoryItem', 'i');

        $builder->where("p.type = 'COST'");

        if (isset($filter['search']) && $filter['search']) {
            $builder->andWhere('i.name LIKE :string OR i.sku LIKE :string');
            $builder->setParameter('string', '%'.$filter['search'].'%');
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

        if ($filter['group_by'] == 'item') {
            $builder->addSelect("SUM(p.amount) AS amount");
            $builder->addOrderBy('i.name', 'ASC');
            $builder->addGroupBy("i.name, i.sku, i.serial");
        } else {
            $builder->addSelect("p.paymentDate AS date");
            $builder->addSelect("p.note AS note");
            $builder->addSelect("p.amount AS amount");
            $builder->addOrderBy('date', 'DESC');
        }

        $query = $builder->getQuery();

        return $query->getResult();

    }

}
