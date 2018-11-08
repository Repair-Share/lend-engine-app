<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * CheckOutPrompt
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CheckOutPromptRepository")
 */
class CheckOutPrompt
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, unique=true)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(name="sort", type="integer", nullable=true)
     */
    private $sort;

    /**
     * @var boolean
     *
     * @ORM\Column(name="default_on", type="boolean", nullable=true)
     */
    private $defaultOn = false;

    /**
     * @ORM\ManyToMany(targetEntity="InventoryItem", mappedBy="checkOutPrompts")
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
     * @return CheckOutPrompt
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
     * Set sort
     *
     * @param integer $sort
     *
     * @return CheckOutPrompt
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set defaultOn
     *
     * @param boolean $defaultOn
     *
     * @return CheckOutPrompt
     */
    public function setDefaultOn($defaultOn)
    {
        $this->defaultOn = $defaultOn;

        return $this;
    }

    /**
     * Get defaultOn
     *
     * @return boolean
     */
    public function getDefaultOn()
    {
        return $this->defaultOn;
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
}

