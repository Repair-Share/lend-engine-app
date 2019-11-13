<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MaintenancePlan
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MaintenancePlanRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MaintenancePlan
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=1024, nullable=true)
     */
    private $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="interval_months", type="integer", nullable=true)
     */
    private $interval = null;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="after_each_loan", type="boolean")
     */
    private $afterEachLoan = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="prevent_borrows", type="boolean")
     */
    private $preventBorrowsIfOverdue = false;

    /**
     * @var Contact
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="provider", referencedColumnName="id", nullable=true)
     */
    private $provider;

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
     * Set name
     *
     * @param string $name
     *
     * @return MaintenancePlan
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set interval
     *
     * @param integer $interval
     *
     * @return MaintenancePlan
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

    /**
     * Get interval
     *
     * @return integer
     */
    public function getInterval()
    {
        return $this->interval;
    }
    
    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return MaintenancePlan
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param $afterEachLoan
     * @return $this
     */
    public function setAfterEachLoan($afterEachLoan)
    {
        $this->afterEachLoan = $afterEachLoan;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAfterEachLoan()
    {
        return $this->afterEachLoan;
    }

    /**
     * @param Contact $provider
     * @return $this
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @return bool
     */
    public function getPreventBorrowsIfOverdue()
    {
        return $this->preventBorrowsIfOverdue;
    }

    /**
     * @param bool $val
     * @return $this
     */
    public function setPreventBorrowsIfOverdue($val)
    {
        $this->preventBorrowsIfOverdue = $val;

        return $this;
    }

    public function getFullName()
    {
        if ($this->interval > 0) {
            return $this->name.' (every '.$this->interval.' months)';
        } else if ($this->afterEachLoan == true) {
            return $this->name.' (after each check-in)';
        } else {
            return $this->name.' (ad-hoc)';
        }
    }
}
