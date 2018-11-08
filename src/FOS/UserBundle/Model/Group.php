<?php

namespace FOS\UserBundle\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * Group
 *
 * @ORM\Table(name="group")
 * @ORM\MappedSuperclass
 */
class Group
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=180, unique=true)
     */
    private $name;

    /**
     * @var array
     *
     * @ORM\Column(name="roles", type="array")
     */
    private $roles;


}

