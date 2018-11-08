<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductFieldValue
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductFieldValueRepository")
 */
class ProductFieldValue
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
     * @var ProductField
     *
     * @ORM\ManyToOne(targetEntity="ProductField", inversedBy="fieldValues")
     * @ORM\JoinColumn(name="product_field_id", referencedColumnName="id")
     */
    private $productField;

    /**
     * @var InventoryItem
     *
     * @ORM\ManyToOne(targetEntity="InventoryItem", inversedBy="fieldValues")
     * @ORM\JoinColumn(name="inventory_item_id", referencedColumnName="id")
     */
    private $inventoryItem;

    /**
     * @var string
     *
     * @ORM\Column(name="field_value", type="string", length=255, nullable=true)
     */
    private $fieldValue;

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
     * Set inventoryItem
     *
     * @param InventoryItem $inventoryItem
     *
     * @return ProductFieldValue
     */
    public function setInventoryItem($inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;

        return $this;
    }

    /**
     * Get product
     *
     * @return InventoryItem
     */
    public function getInventoryItem()
    {
        return $this->inventoryItem;
    }

    /**
     * Set productField
     *
     * @param ProductField $productField
     *
     * @return ProductFieldValue
     */
    public function setProductField($productField)
    {
        $this->productField = $productField;

        return $this;
    }

    /**
     * Get productField
     *
     * @return productField
     */
    public function getProductField()
    {
        return $this->productField;
    }

    /**
     * Set fieldValue
     *
     * @param string $fieldValue
     *
     * @return ProductFieldValue
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;

        return $this;
    }

    /**
     * Get fieldValue
     *
     * @return string
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }
}
