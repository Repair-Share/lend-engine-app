<?php

namespace AppBundle\Services\Contact;

use AppBundle\Entity\Contact;
use AppBundle\Entity\Membership;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class ContactService
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
     * @var \PDO
     */
    private $dbConnection;

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
     * This function gets the data required to feed the contact list DataTable via AJAX
     * @param $start
     * @param $length
     * @param $filter
     *
     * @return array
     */
    public function contactSearch($start, $length, $filter)
    {
        $repository = $this->em->getRepository('AppBundle:Contact');

        $builder = $repository->createQueryBuilder('c');
        $builder->select('c');
        $builder->where('c.id > 1');
        $builder->andWhere('c.isActive = 1');

        if (isset($filter['search']) && is_numeric($filter['search'])) {
            // Searching by member number
            $builder->andWhere("c.membershipNumber = :string");
            $builder->setParameter('string', trim($filter['search']));
        } else if (isset($filter['search']) && $filter['search']) {
            // Searching by text
            $builder->andWhere("c.firstName LIKE :likeString
                OR c.lastName LIKE :likeString
                OR c.email LIKE :likeString
                OR CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) LIKE :likeString
                ");
            $builder->setParameter('likeString', '%'.trim($filter['search']).'%');
        }

        if (isset($filter['hasMembership']) && $filter['hasMembership'] == 1) {
            $builder->leftJoin('c.memberships', 'm');
            $builder->andWhere('m.expiresAt > :date');
            $builder->andWhere('m.status != :statusCancelled');
            $builder->setParameter('date', date("Y-m-d H:i:s"));
            $builder->setParameter('statusCancelled', Membership::SUBS_STATUS_CANCELLED);
        }

        if (isset($filter['date_from']) && $filter['date_from']) {
            $builder->andWhere('c.createdAt >= :date_from');
            $builder->setParameter('date_from', $filter['date_from']);
        }

        if (isset($filter['date_to']) && $filter['date_to']) {
            $builder->andWhere('c.createdAt <= :date_to');
            $builder->setParameter('date_to', $filter['date_to'].' 23:59:59');
        }

        // Run without pages to get total results:
        $queryTotalResults = $builder->getQuery();
        $totalResults = count($queryTotalResults->getResult());

        // Add pages:
        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        // Add order by:
        $builder->addOrderBy("c.firstName");

        // Get the data:
        $query = $builder->getQuery();

        return [
            'totalResults' => $totalResults,
            'data' => $query->getResult()
        ];

    }

    /**
     * @param \DateTime $dateTo
     * @return int
     */
    public function countAllContacts(\DateTime $dateTo = null)
    {
        $repository = $this->em->getRepository('AppBundle:Contact');
        $builder = $repository->createQueryBuilder('c');
        $builder->add('select', 'COUNT(c) AS qty');
        $builder->where('c.id > 1');
        if ($dateTo) {
            $builder->andWhere("c.createdAt < :dateTo");
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
     * @throws DBALException
     */
    public function contactsAddedByMonth()
    {
        $sql = "SELECT DATE(c.created_at) AS d,
                  count(*) AS c
                  FROM contact c
                  WHERE c.id > 1
                  GROUP BY DATE(c.created_at)";

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
     * @param Contact $contact
     * @return bool
     */
    public function recalculateBalance(Contact $contact)
    {
        $repository = $this->em->getRepository('AppBundle:Payment');
        $builder = $repository->createQueryBuilder('p');
        $builder->add('select', "SUM(CASE WHEN p.type = 'PAYMENT' THEN p.amount ELSE -p.amount END) AS balance");
        $builder->where('p.contact = :contact');

        $builder->andWhere("p.deposit IS NULL");

        $builder->setParameter('contact', $contact->getId());
        $query = $builder->getQuery();
        $results = $query->getResult();
        $balance = $results[0]['balance'];

        $contact->setBalance($balance);
        $this->em->persist($contact);

        try {
            $this->em->flush($contact);
        } catch (\Exception $generalException) {

        }

        return true;
    }

}