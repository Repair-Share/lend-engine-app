<?php

namespace AppBundle\Services\Membership;

use AppBundle\Entity\Contact;
use AppBundle\Entity\Membership;
use AppBundle\Entity\MembershipType;
use AppBundle\Entity\Note;
use AppBundle\Services\Contact\ContactService;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;

class MembershipService
{

    /** @var EntityManager */
    private $em;

    /** @var ContactService */
    private $contactService;

    /** @var array */
    public $errors = [];

    public function __construct(EntityManager $em, ContactService $contactService)
    {
        $this->em        = $em;
        $this->contactService = $contactService;
    }

    /**
     * @return array
     * @throws DBALException
     */
    // +++ KB-MAN 2024/02/25 only count active memberships
    //public function membershipsAddedByMonth()
    public function membershipsAddedByMonth($where = array())
    // --- KB-MAN 2024/02/25 only count active memberships
    {
        // +++ KB-MAN 2024/02/25 only count active memberships
        // $sql = "SELECT DATE(m.created_at) AS d,
        //           count(*) AS c
        //           FROM membership m
        //           GROUP BY DATE(m.created_at)";
        $sql = "SELECT DATE(m.created_at) AS d,
                     count(*) AS c
                     FROM membership m";
        if (isset($where['status'])) {
          $sql .= "    WHERE status = '" . $where['status'] . "'";
        }
        $sql .= "    GROUP BY DATE(m.created_at)";
        // --- KB-MAN 2024/02/25 only count active memberships

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
        $builder->where("m.status = 'ACTIVE'");
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