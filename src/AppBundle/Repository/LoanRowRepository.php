<?php

namespace AppBundle\Repository;

/**
 * LoanRowRepository
 *
 */
class LoanRowRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * For the nightly scheduled reminders
     * @return array|bool
     * For scheduled loan reminders
     */
    public function getLoanRowsDueTomorrow()
    {
        $tomorrow = new \DateTime();
        $tomorrow->modify("+1 day");

        $repository = $this->getEntityManager()->getRepository('AppBundle:LoanRow');
        $qb = $repository->createQueryBuilder('lr');
        $qb->select('lr')
            ->leftJoin('lr.loan', 'l')
            ->leftJoin('lr.inventoryItem', 'i')
            ->where('lr.dueInAt > :dateStart')
            ->andWhere('lr.dueInAt < :dateEnd')
            ->andWhere('l.status = :statusActive')
            ->andWhere('i.itemType = :itemType')
            ->andWhere('lr.checkedInAt IS NULL')
            ->setParameter('dateStart', $tomorrow->format("Y-m-d 00:00:00"))
            ->setParameter('dateEnd', $tomorrow->format("Y-m-d 23:59:59"))
            ->setParameter('itemType', 'loan')
            ->setParameter('statusActive', 'ACTIVE');

        $query = $qb->getQuery();

        if ( $results = $query->getResult() ) {
            return $results;
        } else {
            return false;
        }
    }

    /**
     * For the nightly scheduled reminders
     * @param $daysOverdue
     * @return bool|mixed
     */
    public function getOverdueItems($daysOverdue)
    {
        $dueIn = new \DateTime();
        $dueIn->modify("-{$daysOverdue} days");

        $repository = $this->getEntityManager()->getRepository('AppBundle:LoanRow');
        $qb = $repository->createQueryBuilder('lr');
        $qb->select('lr')
            ->leftJoin('lr.loan', 'l')
            ->leftJoin('lr.inventoryItem', 'i')
            ->where('lr.dueInAt > :dateStart')
            ->andWhere('lr.dueInAt < :dateEnd')
            ->andWhere('lr.checkedInAt IS NULL')
            ->andWhere('lr.checkedOutAt IS NOT NULL')
            ->andWhere('l.status != :statusReserved')
            ->andWhere('i.itemType = :itemType')
            ->setParameter('dateStart', $dueIn->format("Y-m-d 00:00:00"))
            ->setParameter('dateEnd', $dueIn->format("Y-m-d 23:59:59"))
            ->setParameter('itemType', 'loan')
            ->setParameter('statusReserved', 'RESERVED')
        ;

        $query = $qb->getQuery();

        if ( $results = $query->getResult() ) {
            return $results;
        } else {
            return false;
        }
    }


    public function search($start, $length, $filter = [], $sort = [])
    {
        $repository = $this->getEntityManager()->getRepository('AppBundle:LoanRow');

        $builder = $repository->createQueryBuilder('lr');
        $builder->select('lr');
        $builder->leftJoin('lr.loan', 'l');
        $builder->leftJoin('l.contact', 'c');
        $builder->leftJoin('lr.inventoryItem', 'i');

        if (isset($filter['search']) && $filter['search']) {
            // names
            $builder->andWhere('c.firstName LIKE :string');
            $builder->orWhere('c.lastName LIKE :string');

            $builder->orWhere("CONCAT(c.firstName,' ',c.lastName) LIKE :string");

            // ref / id
            $builder->orWhere('l.reference LIKE :string');
            $builder->orWhere('l.id LIKE :string');

            // status
            if ($filter['search'] == 'on loan') {
                $filter['search'] = 'active';
            }
            $builder->orWhere('l.status LIKE :string');

            // item
            $builder->orWhere('i.name LIKE :string');
            $builder->orWhere('i.sku LIKE :string');

            $builder->setParameter('string', '%'.$filter['search'].'%');
        }

        if (isset($filter['excludeStockItems']) && $filter['excludeStockItems'] == true) {
            $builder->andWhere("i.itemType != 'stock'");
        }

        if (isset($filter['status']) && $filter['status'] != '' && $filter['status'] != 'ALL') {
            $builder->andWhere('l.status = :status');
            $builder->setParameter('status', $filter['status']);
        }

        if (isset($filter['current_site']) && $filter['current_site']) {
            $builder->join('i.inventoryLocation', 'location');
            $builder->join('location.site', 'site');
            $builder->andWhere('site = :current_site');
            $builder->setParameter('current_site', $filter['current_site']);
            $builder->andWhere('location != 1'); // exclude items on loan
        }

        if (isset($filter['from_site']) && $filter['from_site']) {
            $builder->andWhere('lr.siteFrom = :from_site');
            $builder->setParameter('from_site', $filter['from_site']);
        }

        if (isset($filter['to_site']) && $filter['to_site']) {
            $builder->andWhere('lr.siteTo = :to_site');
            $builder->setParameter('to_site', $filter['to_site']);
        }

        if (isset($filter['date_from']) && $filter['date_from']) {
            if (isset($filter['date_type']) && $filter['date_type'] == 'date_out') {
                $builder->andWhere('lr.dueOutAt >= :date_from');
            } else {
                $builder->andWhere('lr.dueInAt >= :date_from');
            }
            $builder->setParameter('date_from', $filter['date_from'].' 00:00:00');
        }

        if (isset($filter['date_to']) && $filter['date_to']) {
            if (isset($filter['date_type']) && $filter['date_type'] == 'date_out') {
                $builder->andWhere('lr.dueOutAt <= :date_to');
            } else {
                $builder->andWhere('lr.dueInAt <= :date_to');
            }
            $builder->setParameter('date_to', $filter['date_to'].' 23:59:59');
        }

        // Run without pages to get total results:
        $queryTotalResults = $builder->getQuery();
        $totalResults = count($queryTotalResults->getResult());

        // Add pages:
        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        // Add order by:
        if (is_array($sort) && count($sort) > 0 && $this->validateSort($sort)) {
            $builder->addOrderBy("l.".$sort['column'], $sort['direction']);
        } else {
            $builder->addOrderBy("lr.id", "DESC");
        }

        // Get the data
        $query = $builder->getQuery();

        return [
            'totalResults' => $totalResults,
            'data' => $query->getResult()
        ];
    }

    /**
     * @param array $sort
     * @return bool
     */
    private function validateSort($sort = [])
    {
        return true;
    }
}
