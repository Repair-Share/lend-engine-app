<?php

namespace AppBundle\Services\Report;

use Doctrine\ORM\EntityManager;

class ReportChildren
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
     * Payments list
     * @param array $filter
     * @return array
     */
    public function run($filter = array())
    {
        $repository = $this->em->getRepository('AppBundle:Child');

        $start = 0;
        $length = 1000;

        $builder = $repository->createQueryBuilder('c');
        $builder->select("COUNT(c) AS qty");

        $builder->leftJoin('c.contact', 'contact');

        $builder->where('contact.isActive = 1');

        if (isset($filter['has_membership'])) {
            if ($filter['has_membership'] == 'yes') {
                $builder->andWhere('contact.activeMembership > 0');
            } else if ($filter['has_membership'] == 'no') {
                $builder->andWhere('contact.activeMembership IS NULL');
            }
        }

        $builder->setFirstResult($start);
        $builder->setMaxResults($length);

        if ($filter['group_by'] == 'gender' || 1) {
            $builder->addSelect('c.gender AS grouping_name');
            $builder->addGroupBy("c.gender");
        } else {
            $builder->addSelect('c.dateOfBirth AS grouping_name');
            $builder->addGroupBy("c.dateOfBirth");
        }

        $query = $builder->getQuery();

        return $query->getResult();

    }

}
