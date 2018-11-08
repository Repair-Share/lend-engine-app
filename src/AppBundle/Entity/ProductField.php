<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ProductField
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductFieldRepository")
 */
class ProductField
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
     * @ORM\Column(name="name", type="string", length=32)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=32)
     */
    private $type;

    /**
     * @var integer
     *
     * @ORM\Column(name="required", type="boolean")
     */
    private $required = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="show_on_item_list", type="boolean", options={"default" = false})
     */
    private $showOnItemList = false;

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
     * @ORM\OneToMany(targetEntity="ProductFieldValue", mappedBy="productField", cascade={"persist", "remove"})
     */
    protected $fieldValues;

    /**
     * @ORM\OneToMany(targetEntity="ProductFieldSelectOption", mappedBy="productField", cascade={"persist", "remove"})
     */
    protected $choices;

    /**
     *
     */
    public function __construct()
    {

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
     * @return ProductField
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
     * Set type
     *
     * @param string $type
     *
     * @return ProductField
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set required
     *
     * @param integer $required
     *
     * @return ProductField
     */
    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Get required
     *
     * @return integer
     */
    public function getRequired()
    {
        return $this->required;
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

    /**
     * @return mixed
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * Add fieldValue
     *
     * @param \AppBundle\Entity\ProductFieldValue $fieldValue
     *
     * @return ProductField
     */
    public function addFieldValue(\AppBundle\Entity\ProductFieldValue $fieldValue)
    {
        $this->fieldValues[] = $fieldValue;

        return $this;
    }

    /**
     * Remove fieldValue
     *
     * @param \AppBundle\Entity\ProductFieldValue $fieldValue
     */
    public function removeFieldValue(\AppBundle\Entity\ProductFieldValue $fieldValue)
    {
        $this->fieldValues->removeElement($fieldValue);
    }

    /**
     * Get fieldValues
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFieldValues()
    {
        return $this->fieldValues;
    }

    /**
     * Add choice
     *
     * @param \AppBundle\Entity\ProductFieldSelectOption $choice
     *
     * @return ProductField
     */
    public function addChoice(\AppBundle\Entity\ProductFieldSelectOption $choice)
    {
        $this->choices[] = $choice;

        return $this;
    }

    /**
     * Remove choice
     *
     * @param \AppBundle\Entity\ProductFieldSelectOption $choice
     */
    public function removeChoice(\AppBundle\Entity\ProductFieldSelectOption $choice)
    {
        $this->choices->removeElement($choice);
    }

    /**
     * Set showOnItemList
     *
     * @param boolean $showOnItemList
     *
     * @return ProductField
     */
    public function setShowOnItemList($showOnItemList)
    {
        $this->showOnItemList = $showOnItemList;

        return $this;
    }

    /**
     * Get showOnItemList
     *
     * @return boolean
     */
    public function getShowOnItemList()
    {
        return $this->showOnItemList;
    }

    /**
     * Set showOnWebsite
     *
     * @param boolean $showOnWebsite
     *
     * @return ProductField
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
}
