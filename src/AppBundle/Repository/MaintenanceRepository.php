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
        $this->getEntityManager()->persist($maintenance);
        $this->getEntityManager()->flush($maintenance);
    }

}
