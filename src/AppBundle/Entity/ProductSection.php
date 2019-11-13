<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ProductSection
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProductSectionRepository")
 */
class ProductSection
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
     * @ORM\OneToMany(targetEntity="ProductTag", mappedBy="section")
     */
    protected $categories;

    /**
     *
     */
    public function __construct()
    {
        $this->categories = new ArrayCollection();
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
     * @return ProductSection
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
     * @param ProductTag $category
     */
    public function addCategory(ProductTag $category)
    {
        $this->categories[] = $category;
    }

    /**
     * @return ArrayCollection
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     *
     * @param \AppBundle\Entity\ProductTag $category
     */
    public function removeInventoryItem(ProductTag $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * Set showOnWebsite
     *
     * @param boolean $showOnWebsite
     *
     * @return ProductSection
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
     * @return ProductSection
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
