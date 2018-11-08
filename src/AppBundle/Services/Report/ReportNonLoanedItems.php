<?php

namespace AppBundle\Services\Report;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;

class ReportNonLoanedItems
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
        $repository = $this->em->getRepository('AppBundle:InventoryItem');

        $start = 0;
        $length = 1000;

        $builder = $repository->createQueryBuilder('i');

        $dateFromYmd = '2000-01-01';
        $today = new \DateTime();
        $dateFrom  = clone($today);

        if (isset($filter['time']) && $filter['time']) {

            switch ($filter['time']) {
                case "all":
                    // no time limit
                    break;
                case "1mth":
                    $dateFrom->modify("-1 month");
                    $dateFromYmd = $dateFrom->format("Y-m-d");
                    break;
                case "6mth":
                    $dateFrom->modify("-6 months");
                    $dateFromYmd = $dateFrom->format("Y-m-d");
                    break;
                case "12mth":
                    $dateFrom->modify("-12 months");
                    $dateFromYmd = $dateFrom->format("Y-m-d");
                    break;
            }

        }

        $builder->select("
            i.id AS id, i.name AS name, i.sku AS sku, i.serial AS serial,
            SUM(CASE WHEN
            (lr.dueOutAt > '".$dateFromYmd."')
            THEN COALESCE(lr.productQuantity, 0) ELSE 0 END) AS qty
            ");
        $builder->leftJoin('AppBundle\Entity\LoanRow', 'lr', 'WITH', 'lr.inventoryItem = i.id');

        if (isset($filter['search']) && $filter['search']) {
            $builder->andWhere('i.name LIKE :string OR i.sku LIKE :string');
            $builder->setParameter('string', '%'.$filter['search'].'%');
        }

        $builder->andWhere('i.isActive = 1');
        $builder->addGroupBy("i.id");
        $builder->having("qty = 0");

        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        $query = $builder->getQuery();

        return $query->getResult();

    }

}
