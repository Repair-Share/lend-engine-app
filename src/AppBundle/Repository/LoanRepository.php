<?php

namespace AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LoanRepository
 *
 */
class LoanRepository extends EntityRepository
{

    /**
     * This function gets the data required to feed the DataTable via AJAX
     * @param $filter
     * @param $start
     * @param $length
     * @param $sort
     * @return array
     */
    public function findLoans($start, $length, $filter = [], $sort = [])
    {
        $repository = $this->getEntityManager()->getRepository('AppBundle:Loan');

        $builder = $repository->createQueryBuilder('l');
        $builder->select('l, c');
        $builder->leftJoin('l.contact', 'c');
        $builder->leftJoin('l.loanRows', 'lr');
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

        if (isset($filter['status']) && $filter['status'] != '' && $filter['status'] != 'ALL') {
            $builder->andWhere('l.status = :status');
            $builder->setParameter('status', $filter['status']);
        }

        if (isset($filter['date_from']) && $filter['date_from']) {
            if (isset($filter['date_type']) && $filter['date_type'] == 'date_out') {
                $builder->andWhere('l.timeOut >= :date_from');
            } else {
                $builder->andWhere('l.timeIn >= :date_from');
            }
            $builder->setParameter('date_from', $filter['date_from']);
        }

        if (isset($filter['date_to']) && $filter['date_to']) {
            if (isset($filter['date_type']) && $filter['date_type'] == 'date_out') {
                $builder->andWhere('l.timeOut <= :date_to');
            } else {
                $builder->andWhere('l.timeIn <= :date_to');
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
            $builder->addOrderBy("l.id", "DESC");
        }

        // Get the data
        $query = $builder->getQuery();

        return [
            'totalResults' => $totalResults,
            'data' => $query->getResult()
        ];

//        return $query->getResult();
    }

    /**
     * This is in the default (dashboard) controller to update loans on each log in, for now
     */
    public function setLoansOverdue()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $q = $qb->update('AppBundle:Loan', 'l')
            ->set('l.status', ':statusOverdue')
            ->where('l.timeIn < :date')
            ->andWhere('l.status = :statusActive')
            ->setParameter('date', new \DateTime())
            ->setParameter('statusOverdue', 'OVERDUE')
            ->setParameter('statusActive', 'ACTIVE')
            ->getQuery();
        $q->execute();
    }

    /**
     * @param int $days
     * @return array|bool
     * For scheduled reservation reminders
     */
    public function getReservationsDue($days = 1)
    {
        $tomorrow = new \DateTime();
        $tomorrow->modify("+{$days} days");

        $repository = $this->getEntityManager()->getRepository('AppBundle:Loan');
        $qb = $repository->createQueryBuilder('l');
        $qb->select('l')
            ->where('l.timeOut > :dateStart')
            ->andWhere('l.timeOut < :dateEnd')
            ->andWhere('l.status = :statusReserved')
            ->setParameter('dateStart', $tomorrow->format("Y-m-d 00:00:00"))
            ->setParameter('dateEnd', $tomorrow->format("Y-m-d 23:59:59"))
            ->setParameter('statusReserved', 'RESERVED');

        $query = $qb->getQuery();

        if ( $results = $query->getResult() ) {
            return $results;
        } else {
            return false;
        }
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
