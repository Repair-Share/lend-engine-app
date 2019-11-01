<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * KitComponent
 *
 * @ORM\Table(
 *     name="kit_component",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}
 *     )
 * @ORM\Entity(repositoryClass="AppBundle\Repository\KitComponentRepository")
 */
class KitComponent
{

    /**
     * @var InventoryItem
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="InventoryItem", inversedBy="components")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=true)
     */
    private $inventoryItem;

    /**
     * @var InventoryItem
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="InventoryItem")
     * @ORM\JoinColumn(name="component_id", referencedColumnName="id", nullable=true)
     */
    private $component;

    /**
     * @var string
     *
     * @ORM\Column(name="component_quantity", type="integer")
     */
    private $quantity = 1;

    /**
     * Get quantity
     *
     * @return string
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param $qty int
     * @return $this
     */
    public function setQuantity($qty)
    {
        $this->quantity = $qty;

        return $this;
    }

    /**
     * Set inventoryItem
     *
     * @param InventoryItem $inventoryItem
     *
     * @return KitComponent
     */
    public function setInventoryItem($inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;

        return $this;
    }

    /**
     * Get item
     *
     * @return InventoryItem
     */
    public function getInventoryItem()
    {
        return $this->inventoryItem;
    }

    /**
     * Set component
     *
     * @param InventoryItem $component
     *
     * @return KitComponent
     */
    public function setComponent($component)
    {
        $this->component = $component;

        return $this;
    }

    /**
     * Get item
     *
     * @return InventoryItem
     */
    public function getComponent()
    {
        return $this->component;
    }
}
