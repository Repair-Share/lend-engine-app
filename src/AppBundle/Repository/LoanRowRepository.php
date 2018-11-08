<?php

namespace AppBundle\Repository;

/**
 * LoanRowRepository
 *
 */
class LoanRowRepository extends \Doctrine\ORM\EntityRepository
{

    /**
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
            ->where('lr.dueInAt > :dateStart')
            ->andWhere('lr.dueInAt < :dateEnd')
            ->andWhere('l.status = :statusActive')
            ->setParameter('dateStart', $tomorrow->format("Y-m-d 00:00:00"))
            ->setParameter('dateEnd', $tomorrow->format("Y-m-d 23:59:59"))
            ->setParameter('statusActive', 'ACTIVE');

        $query = $qb->getQuery();

        if ( $results = $query->getResult() ) {
            return $results;
        } else {
            return false;
        }
    }

    /**
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
            ->where('lr.dueInAt > :dateStart')
            ->andWhere('lr.dueInAt < :dateEnd')
            ->andWhere('lr.checkedInAt IS NULL')
//            ->andWhere('l.status = :statusActive')
            ->setParameter('dateStart', $dueIn->format("Y-m-d 00:00:00"))
            ->setParameter('dateEnd', $dueIn->format("Y-m-d 23:59:59"))
//            ->setParameter('statusActive', 'ACTIVE')
        ;

        $query = $qb->getQuery();

        if ( $results = $query->getResult() ) {
            return $results;
        } else {
            return false;
        }
    }

}
