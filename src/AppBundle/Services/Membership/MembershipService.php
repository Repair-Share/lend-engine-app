<?php

namespace AppBundle\Services\Membership;

use AppBundle\Entity\Contact;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class MembershipService
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
     * @return array
     * @throws DBALException
     */
    public function membershipsAddedByMonth()
    {
        $sql = "SELECT DATE(m.created_at) AS d,
                  count(*) AS c
                  FROM membership m
                  GROUP BY DATE(m.created_at)";

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

    /**
     * @param \DateTime $dateTo
     * @return int
     */
    public function countMemberships(\DateTime $dateTo = null)
    {
        $repository = $this->em->getRepository('AppBundle:Membership');
        $builder = $repository->createQueryBuilder('m');
        $builder->add('select', 'COUNT(m) AS qty');
//        $builder->where('m.isActive = 1');
        if ($dateTo) {
            $builder->andWhere("m.createdAt < :dateTo");
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

}