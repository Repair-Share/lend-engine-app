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

}
