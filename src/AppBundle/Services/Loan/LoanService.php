<?php

namespace AppBundle\Services\Loan;

use AppBundle\Entity\Loan;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class LoanService
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
     * @param string $status
     * @param \DateTime $dateTo
     * @return int
     */
    public function countLoans($status = '', \DateTime $dateTo = null)
    {
        $repository = $this->em->getRepository('AppBundle:Loan');
        $builder = $repository->createQueryBuilder('l');
        $builder->add('select', 'COUNT(l) AS qty');
        if ($status) {
            $builder->andWhere('l.status LIKE :status');
            $builder->setParameter('status', '%'.$status.'%');
        }
        if ($dateTo) {
            $builder->andWhere("l.createdAt < :dateTo");
            $builder->setParameter('dateTo', $dateTo->format("Y-m-01"));
        }
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
     * @throws \Doctrine\DBAL\DBALException
     */
    public function loansAddedByMonth()
    {
        $sql = "SELECT DATE(l.created_at) AS d,
                  count(*) AS c
                  FROM loan l
                  GROUP BY DATE(l.created_at)";

        $stmt = $this->em->getConnection()->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        // key by "Y-m"
        $data = [];
        foreach ($results AS $result) {
            $key = substr($result['d'], 0, 7);
            if (!isset($data[$key])) {
                $data[$key] = 0;
            }
            $data[$key] += $result['c'];
        }
        return $data;
    }

}