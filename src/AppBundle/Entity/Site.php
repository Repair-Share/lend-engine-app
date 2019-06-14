<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Site
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SiteRepository")
 */
class Site
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"basket"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=64, unique=true)
     * @Groups({"basket"})
     */
    private $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive = true;

    /**
     * Is listed in the Lend Engine directory
     * @var boolean
     *
     * @ORM\Column(name="is_listed", type="boolean")
     */
    private $isListed = true;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=2)
     */
    private $country;
    
    /**
     * @var string
     *
     * @ORM\Column(name="post_code", type="string", length=16, nullable=true)
     */
    private $postCode;
    
    /**
     * @var InventoryLocation
     *
     * @ORM\OneToOne(targetEntity="InventoryLocation")
     * @ORM\JoinColumn(name="default_check_in_location", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    private $defaultCheckInLocation;

    /**
     * @var string
     *
     * @ORM\Column(name="colour", type="string", length=7, nullable=true)
     */
    private $colour;

    /**
     *
     * @ORM\OneToMany(targetEntity="InventoryLocation", mappedBy="site", cascade={"persist", "remove"})
     * @ORM\OrderBy({"name" = "ASC"})
     */
    protected $inventoryLocations;

    /**
     * @ORM\ManyToMany(targetEntity="InventoryItem", mappedBy="sites")
     */
    protected $inventoryItems;

    /**
     * @ORM\OneToMany(targetEntity="SiteOpening", mappedBy="site", cascade={"persist", "remove"})
     */
    protected $siteOpenings;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="site", cascade={"persist", "remove"})
     */
    protected $openingTimeExceptions;

    /**
     *
     */
    public function __construct()
    {
        $this->siteOpenings = new ArrayCollection();
    }

    /**
     * @param $id int
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get id
     *
     * @return int
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
     * @return Site
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
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Site
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
     * Set isListed
     *
     * @param boolean $isListed
     *
     * @return Site
     */
    public function setIsListed($isListed)
    {
        $this->isListed = $isListed;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsListed()
    {
        return $this->isListed;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return Site
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return Site
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set defaultCheckInLocation
     *
     * @param InventoryLocation $defaultCheckInLocation
     *
     * @return Site
     */
    public function setDefaultCheckInLocation($defaultCheckInLocation)
    {
        $this->defaultCheckInLocation = $defaultCheckInLocation;

        return $this;
    }

    /**
     * Get defaultCheckInLocation
     *
     * @return InventoryLocation
     */
    public function getDefaultCheckInLocation()
    {
        return $this->defaultCheckInLocation;
    }

    /**
     * Set postCode
     *
     * @param string $postCode
     *
     * @return Site
     */
    public function setPostCode($postCode)
    {
        $this->postCode = $postCode;

        return $this;
    }

    /**
     * Get postCode
     *
     * @return string
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

    /**
     * @param InventoryItem $inventoryItems
     */
    public function addInventoryItem(InventoryItem $inventoryItems)
    {
        $this->inventoryItems[] = $inventoryItems;
    }

    /**
     * @return InventoryItem[]
     */
    public function getInventoryItems()
    {
        return $this->inventoryItems;
    }

    /**
     * Remove inventory item
     *
     * @param \AppBundle\Entity\InventoryItem $inventoryItem
     */
    public function removeInventoryItem(InventoryItem $inventoryItem)
    {
        $this->inventoryItems->removeElement($inventoryItem);
    }

    /**
     * @param InventoryLocation $inventoryLocation
     */
    public function addInventoryLocation(InventoryLocation $inventoryLocation)
    {
        $this->inventoryLocations[] = $inventoryLocation;
    }

    /**
     * @return ArrayCollection
     */
    public function getInventoryLocations()
    {
        return $this->inventoryLocations;
    }

    /**
     * @param $siteOpenings
     * @return $this
     */
    public function setSiteOpenings($siteOpenings)
    {
        $this->siteOpenings = $siteOpenings;

        return $this;
    }

    /**
     * Remove site opening
     *
     * @param SiteOpening $siteOpening
     */
    public function removeSiteOpening(SiteOpening $siteOpening)
    {
        $this->siteOpenings->removeElement($siteOpening);
    }

    /**
     * @param SiteOpening $siteOpening
     */
    public function addSiteOpening(SiteOpening $siteOpening)
    {
        $siteOpening->setSite($this);
        $this->siteOpenings[] = $siteOpening;
    }

    /**
     * @return ArrayCollection
     */
    public function getSiteOpenings()
    {
        // Put the opening hours into a sensible sequence
        if (is_array($this->siteOpenings) && count($this->siteOpenings) > 0) {
            $keyed = [];
            foreach ($this->siteOpenings AS $o) {
                /** @var $o \AppBundle\Entity\SiteOpening */
                $keyed[$o->getWeekDay().$o->getTimeFrom()] = $o;
            }
            ksort($keyed);

            $this->siteOpenings = new ArrayCollection();
            foreach ($keyed AS $o) {
                $this->addSiteOpening($o);
            }
        }

        return $this->siteOpenings;
    }

    /**
     * @return mixed
     */
    public function getEvents()
    {
        // Re-key by date
        $sorted = [];
        foreach ($this->openingTimeExceptions AS $ote) {
            /** @var $ote \AppBundle\Entity\Event */
            $d = $ote->getDate()->format("Y-m-d");
            $sorted[$d] = $ote;
        }
        ksort($sorted);
        return array_values($sorted);
    }

    public function getClosedTimes()
    {
        $closed = [];
        foreach ($this->getEvents() AS $ote) {
            /** @var $ote \AppBundle\Entity\Event */
            if ($ote->getType() == 'c') {
                $closed[] = $ote;
            }
        }
        return $closed;
    }

    public function getOpenTimes()
    {
        $open = [];
        foreach ($this->getEvents() AS $ote) {
            /** @var $ote \AppBundle\Entity\Event */
            if ($ote->getType() == 'o') {
                $open[] = $ote;
            }
        }
        return $open;
    }

    /**
     * @param $colour string
     * @return $this
     */
    public function setColour($colour)
    {
        $this->colour = $colour;

        return $this;
    }

    /**
     * @return string
     */
    public function getColour()
    {
        return $this->colour;
    }
}

