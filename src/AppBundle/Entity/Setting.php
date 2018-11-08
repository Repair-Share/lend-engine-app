<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Setting
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SettingRepository")
 */
class Setting
{

    /**
     * @var string
     *
     * @ORM\Column(name="setup_key", type="string", length=128, unique=true)
     * @ORM\Id
     */
    private $setupKey;

    /**
     * @var string
     *
     * @ORM\Column(name="setup_value", type="string", length=2056)
     */
    private $setupValue;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set setupKey
     *
     * @param string $setupKey
     *
     * @return Setting
     */
    public function setSetupKey($setupKey)
    {
        $this->setupKey = $setupKey;

        return $this;
    }

    /**
     * Get setupKey
     *
     * @return string
     */
    public function getSetupKey()
    {
        return $this->setupKey;
    }

    /**
     * Set setupValue
     *
     * @param string $setupValue
     *
     * @return Setting
     */
    public function setSetupValue($setupValue)
    {
        $this->setupValue = $setupValue;

        return $this;
    }

    /**
     * Get setupValue
     *
     * @return string
     */
    public function getSetupValue()
    {
        return $this->setupValue;
    }
}
