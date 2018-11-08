<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ProductFieldSelectOption
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductFieldSelectOptionRepository")
 */
class ProductFieldSelectOption
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
     * @ORM\ManyToOne(targetEntity="ProductField", inversedBy="choices")
     * @ORM\JoinColumn(name="product_field_id", referencedColumnName="id")
     */
    private $productField;

    /**
     * @var string
     *
     * @ORM\Column(name="option_name", type="string", length=255)
     */
    private $optionName;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer")
     */
    private $sort = 0;

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
     * Set optionName
     *
     * @param string $optionName
     *
     * @return ProductFieldSelectOption
     */
    public function setOptionName($optionName)
    {
        $this->optionName = $optionName;

        return $this;
    }

    /**
     * Get optionName
     *
     * @return string
     */
    public function getOptionName()
    {
        return $this->optionName;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return ProductField
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

}
