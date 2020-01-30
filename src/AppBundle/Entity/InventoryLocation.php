<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * InventoryLocation
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}, uniqueConstraints={@ORM\UniqueConstraint(name="name_site_unique", columns={"name", "site"})})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InventoryLocationRepository")
 */
class InventoryLocation
{
    /**
     * @var integer
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
     * @ORM\Column(name="name", type="string", length=32)
     * @Groups({"basket"})
     */
    private $name;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="inventoryLocations")
     * @ORM\JoinColumn(name="site", referencedColumnName="id", nullable=true)
     * @Groups({"basket"})
     */
    private $site;

    /**
     * @var string
     *
     * @ORM\Column(name="barcode", type="string", length=32, nullable=true)
     */
    private $barcode;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_available", type="boolean")
     */
    private $isAvailable = true;

    /**
     * @ORM\OneToMany(targetEntity="InventoryItem", mappedBy="inventoryLocation")
     */
    protected $inventoryItems;

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
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return InventoryLocation
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
     * @return string
     */
    public function getNameWithSite()
    {
        return $this->getSite()->getName().' : '.$this->name;
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param \AppBundle\Entity\Site $site
     * @return $this
     */
    public function setSite($site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Set isAvailable
     *
     * @param boolean $isAvailable
     *
     * @return InventoryLocation
     */
    public function setIsAvailable($isAvailable)
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    /**
     * Get isAvailable
     *
     * @return boolean
     */
    public function getIsAvailable()
    {
        return $this->isAvailable;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->inventoryItems = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set barcode
     *
     * @param string $barcode
     *
     * @return InventoryLocation
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;

        return $this;
    }

    /**
     * Get barcode
     *
     * @return string
     */
    public function getBarcode()
    {
        return $this->barcode;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return InventoryLocation
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
     * Add itemMovement
     *
     * @param \AppBundle\Entity\ItemMovement $itemMovement
     *
     * @return InventoryLocation
     */
    public function addItemMovement(\AppBundle\Entity\ItemMovement $itemMovement)
    {
        $this->itemMovements[] = $itemMovement;

        return $this;
    }

    /**
     * Remove itemMovement
     *
     * @param \AppBundle\Entity\ItemMovement $itemMovement
     */
    public function removeItemMovement(\AppBundle\Entity\ItemMovement $itemMovement)
    {
        $this->itemMovements->removeElement($itemMovement);
    }

    /**
     * Get itemMovements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItemMovements()
    {
        return $this->itemMovements;
    }

    /**
     * Add inventoryItem
     *
     * @param \AppBundle\Entity\InventoryItem $inventoryItem
     *
     * @return InventoryLocation
     */
    public function addInventoryItem(\AppBundle\Entity\InventoryItem $inventoryItem)
    {
        $this->inventoryItems[] = $inventoryItem;

        return $this;
    }

    /**
     * Remove inventoryItem
     *
     * @param \AppBundle\Entity\InventoryItem $inventoryItem
     */
    public function removeInventoryItem(\AppBundle\Entity\InventoryItem $inventoryItem)
    {
        $this->inventoryItems->removeElement($inventoryItem);
    }

    /**
     * Get inventoryItems
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getInventoryItems()
    {
        return $this->inventoryItems;
    }
}
