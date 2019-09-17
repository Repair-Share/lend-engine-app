<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MembershipType
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MembershipTypeRepository")
 * @ORM\HasLifecycleCallbacks
 */
class MembershipType
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
     * @var float
     *
     * @ORM\Column(name="price", type="decimal", scale=2)
     */
    private $price = 0.00;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer")
     */
    private $duration = 1;

    /**
     * @var float
     *
     * @ORM\Column(name="discount", type="decimal", scale=2, nullable=true)
     */
    private $discount = 0.00;

    /**
     * @var float
     *
     * @ORM\Column(name="credit_limit", type="decimal", scale=2, nullable=true)
     */
    private $creditLimit = 0.00;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_items", type="integer", nullable=true)
     */
    private $maxItems = 0;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * @var bool
     * @ORM\Column(name="self_serve", type="integer")
     */
    private $isSelfServe = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive = true;

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
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt(new \DateTime("now"));
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return MembershipType
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
     * Set price
     *
     * @param string $price
     *
     * @return MembershipType
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     *
     * @return MembershipType
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        if ($this->duration == 0) {
            $this->duration = 1;
        }
        return $this->duration;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     *
     * @return MembershipType
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return integer
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return MembershipType
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdBy
     *
     * @param \AppBundle\Entity\Contact $createdBy
     *
     * @return MembershipType
     */
    public function setCreatedBy(\AppBundle\Entity\Contact $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \AppBundle\Entity\Contact
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Shows in the select menu when choosing a membership type to create a membership
     * @return string
     */
    public function getFullName()
    {
        $name = $this->getName().' ('.$this->getDuration().' days, '.$this->getPrice().')';
        return $name;
    }

    /**
     * @return bool
     */
    public function getIsSelfServe()
    {
        return $this->isSelfServe;
    }

    /**
     * @param $selfServe bool
     * @return $this
     */
    public function setIsSelfServe($selfServe)
    {
        $this->isSelfServe = $selfServe;

        return $this;
    }

    /**
     * @param $creditLimit
     * @return $this
     */
    public function setCreditLimit($creditLimit)
    {
        $this->creditLimit = $creditLimit;

        return $this;
    }

    /**
     * @return float
     */
    public function getCreditLimit()
    {
        return $this->creditLimit;
    }

    /**
     * @param $maxItems
     * @return $this
     */
    public function setMaxItems($maxItems)
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    /**
     * @return int
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return PaymentMethod
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
}
