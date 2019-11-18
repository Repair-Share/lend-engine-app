<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ProductTag
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductTagRepository")
 */
class ProductTag
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
     * @ORM\Column(name="name", type="string", length=32)
     */
    private $name;

    /**
     * @var ProductSection
     * @ORM\ManyToOne(targetEntity="ProductSection", inversedBy="categories")
     */
    private $section;

    /**
     * @var integer
     *
     * @ORM\Column(name="show_on_website", type="boolean", options={"default" = true})
     */
    private $showOnWebsite = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer")
     */
    private $sort = 0;

    /**
     * @ORM\ManyToMany(targetEntity="InventoryItem", mappedBy="tags")
     */
    protected $inventoryItems;

    /**
     *
     */
    public function __construct()
    {
        $this->inventoryItems = new ArrayCollection();
    }

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
     * @return ProductTag
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
     * @param ProductSection $section
     * @return $this
     */
    public function setSection($section)
    {
        $this->section = $section;

        return $this;
    }

    /**
     * @return ProductSection
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * @param InventoryItem $inventoryItems
     */
    public function addInventoryItem(InventoryItem $inventoryItems)
    {
        $this->inventoryItems[] = $inventoryItems;
    }

    /**
     * Get products
     *
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
     * Set showOnWebsite
     *
     * @param boolean $showOnWebsite
     *
     * @return ProductTag
     */
    public function setShowOnWebsite($showOnWebsite)
    {
        $this->showOnWebsite = $showOnWebsite;

        return $this;
    }

    /**
     * Get showOnWebsite
     *
     * @return boolean
     */
    public function getShowOnWebsite()
    {
        return $this->showOnWebsite;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return ProductTag
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    public function getNameWithSection()
    {
        if ($this->section) {
            return $this->section->getName().' - '.$this->name;
        } else {
            return $this->name;
        }
    }
}
