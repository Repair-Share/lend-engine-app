<?php

namespace AppBundle\Services\Event;

use AppBundle\Entity\Event;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;

class EventService
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
     * @param $id
     * @return null|object
     */
    public function get($id)
    {
        return $this->em->getRepository('AppBundle:Event')->find($id);
    }

    /**
     * @param int $start
     * @param int $length
     * @param array $filter
     * @return array
     * @throws \Exception
     */
    public function eventSearch($start = 0, $length = 1000, $filter = [])
    {
        $repository = $this->em->getRepository('AppBundle:Event');

        $builder = $repository->createQueryBuilder('event');

        if (!is_numeric($start)) {
            throw new \Exception("Start parameter must be numeric");
        }

        if (!is_numeric($length)) {
            throw new \Exception("Length parameter must be numeric");
        }

        // Add filters:
        if (isset($filter['search']) && $filter['search']) {
            $builder->andWhere('event.title LIKE :string');
            $builder->setParameter('string', '%'.trim($filter['search']).'%');
        }

        if (isset($filter['siteId']) && $filter['siteId']) {
            $builder->andWhere('loc.site = :siteId');
            $builder->setParameter('siteId', $filter['siteId']);
        }

        if (isset($filter['from'])) {
            $builder->andWhere("event.date >= '".$filter['from']."'");
        }

        if (isset($filter['to'])) {
            $builder->andWhere("event.date <= '".$filter['to']."'");
        }

        // Array of statuses eg [PUBLISHED, DRAFT]
        if (isset($filter['status']) && is_array($filter['status'])) {
            foreach ($filter['status'] AS $k => $v) {
                $filter['status'][$k] = "'".$v."'";
            }
            $builder->andWhere('event.status IN ('.implode(',',$filter['status']).')');
        }

        // First get the total count:
        $queryTotalResults = $builder->getQuery();
        $totalResults = count($queryTotalResults->getResult());

        // Add limit:
        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        // Add order by:
        if (isset($filter['sortBy']) && isset($filter['sortDir'])) {
            $builder->addOrderBy($filter['sortBy'], $filter['sortDir']);
        } else {
            $builder->addOrderBy("event.date");
        }

        $query = $builder->getQuery();

        return [
            'totalResults' => $totalResults,
            'data' => $query->getResult()
        ];
    }

    /**
     * Fired each time someone logs in via UpdateController
     */
    public function removePastEvents()
    {
        $today = new \DateTime();
        $today->modify("-30 day");
        $filter = ['to' => $today->format("Y-m-d")];
        $data = $this->eventSearch(0, 100, $filter);
        foreach ($data['data'] AS $s) {
            $this->em->remove($s);
        }
        $this->em->flush();
    }

    /**
     * Matched with limit in BillingService when publishing events
     * @return int
     */
    public function countLiveEvents()
    {
        $filter = ['status' => [Event::STATUS_PUBLISHED]];
        $results = $this->eventSearch(0, 100, $filter);
        return (int)$results['totalResults'];
    }
}
