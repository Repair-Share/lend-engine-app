<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Maintenance;

class MaintenanceRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @param Maintenance $maintenance
     */
    public function save(Maintenance $maintenance)
    {
        $d = new \DateTime();
        $today = $d->format("Y-m-d");
        if ($maintenance->getStatus() == Maintenance::STATUS_PLANNED && $maintenance->getDueAt()->format("Y-m-d") < $today) {
            $maintenance->setStatus(Maintenance::STATUS_OVERDUE);
        }

        $this->getEntityManager()->persist($maintenance);
        $this->getEntityManager()->flush($maintenance);
    }

    /**
     * @return bool|mixed
     */
    public function getOverdueByDate()
    {
        $d = new \DateTime();

        $repository = $this->getEntityManager()->getRepository('AppBundle:Maintenance');
        $qb = $repository->createQueryBuilder('m');

        $qb->select('m')
            ->where('m.dueAt < :date')
            ->andWhere('m.status = :status')
            ->setParameter('date', $d->format("Y-m-d"))
            ->setParameter('status', 'planned');

        $query = $qb->getQuery();

        if ( $results = $query->getResult() ) {
            return $results;
        } else {
            return false;
        }
    }

}
