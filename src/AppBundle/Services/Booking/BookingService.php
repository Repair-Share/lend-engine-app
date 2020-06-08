<?php

namespace AppBundle\Services\Booking;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;

class BookingService
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
     * @param array $filter
     * @return array
     */
    public function getBookings($filter = [])
    {
        $repository = $this->em->getRepository('AppBundle:LoanRow');

        $start = 0;
        $length = 1000;

        $builder = $repository->createQueryBuilder('lr');

        $builder->select('lr');

        $builder->leftJoin('lr.loan', 'l');

        $builder->andWhere('l.status IN (:statuses)');

        if (isset($filter['statuses']) && is_array($filter['statuses']) && count($filter['statuses']) > 0) {
            $builder->setParameter('statuses', $filter['statuses']);
        } else {
            $builder->setParameter('statuses', ["RESERVED", "ACTIVE", "OVERDUE"]);
            $builder->andWhere("lr.checkedInAt IS NULL");
        }

        if (isset($filter['item_ids']) && is_array($filter['item_ids']) && count($filter['item_ids']) > 0) {
            $builder->andWhere('lr.inventoryItem IN(:itemIds)');
            $builder->setParameter('itemIds', $filter['item_ids']);
        }

        if (isset($filter['current']) && $filter['current'] == true) {
            $builder->andWhere('lr.dueOutAt < :dueOutAt');
            $builder->setParameter('dueOutAt', new \DateTime());
            $builder->andWhere('lr.dueInAt > :dueInAt');
            $builder->setParameter('dueInAt', new \DateTime());
        }

        if (isset($filter['excludeBookingId']) && $filter['excludeBookingId']) {
            $builder->andWhere('lr.id != '.(int)$filter['excludeBookingId']);
        }

        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        $query = $builder->getQuery();

        return $query->getResult();
    }

}