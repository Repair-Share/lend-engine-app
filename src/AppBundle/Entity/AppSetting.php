<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AppSetting
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AppSettingRepository")
 */
class AppSetting
{

    /**
     * @var App
     *
     * @ORM\ManyToOne(targetEntity="App", inversedBy="settings")
     * @ORM\JoinColumn(name="app_id", referencedColumnName="id")
     * @ORM\Id
     */
    private $app;

    /**
     * @var string
     *
     * @ORM\Column(name="setup_key", type="string", length=128)
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
     * @return App
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @param App $app
     * @return $this
     */
    public function setApp(App $app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Set setupKey
     *
     * @param string $setupKey
     *
     * @return AppSetting
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
     * @return AppSetting
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
