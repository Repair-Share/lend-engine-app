<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * App
 *
 * @ORM\Table(name="app")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AppRepository")
 * @ORM\HasLifecycleCallbacks
 */
class App
{
    
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\OneToMany(targetEntity="AppSetting", mappedBy="app", cascade={"persist", "remove"})
     */
    private $settings;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="installed_at", type="datetime")
     */
    private $installedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="uninstalled_at", type="datetime", nullable=true)
     */
    private $unInstalledAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=16)
     */
    private $code;

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setInstalledAt(new \DateTime("now"));
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set installedAt.
     *
     * @param \DateTime $installedAt
     *
     * @return App
     */
    public function setInstalledAt($installedAt)
    {
        $this->installedAt = $installedAt;

        return $this;
    }

    /**
     * Get installedAt.
     *
     * @return \DateTime
     */
    public function getInstalledAt()
    {
        return $this->installedAt;
    }

    /**
     * @param $unInstalledAt
     * @return $this
     */
    public function setUnInstalledAt($unInstalledAt)
    {
        $this->unInstalledAt = $unInstalledAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUnInstalledAt()
    {
        return $this->unInstalledAt;
    }

    /**
     * Set isActive.
     *
     * @param bool $isActive
     *
     * @return App
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set type.
     *
     * @param string $code
     *
     * @return App
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param $settings
     * @return $this
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSettings()
    {
        return $this->settings;
    }

}
