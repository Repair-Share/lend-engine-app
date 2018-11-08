<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ContactField
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContactFieldRepository")
 */
class ContactField
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
     * @ORM\Column(name="show_on_contact_list", type="boolean", options={"default" = false})
     */
    private $showOnContactList = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer")
     */
    private $sort = 0;

    /**
     * @ORM\OneToMany(targetEntity="ContactFieldValue", mappedBy="contactField", cascade={"persist", "remove"})
     */
    protected $fieldValues;

    /**
     * @ORM\OneToMany(targetEntity="ContactFieldSelectOption", mappedBy="contactField", cascade={"persist", "remove"})
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
     * @return ContactField
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
     * @return ContactField
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
     * @return ContactField
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
     * @return ContactField
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
     * @param \AppBundle\Entity\ContactFieldValue $fieldValue
     *
     * @return ContactField
     */
    public function addFieldValue(\AppBundle\Entity\ContactFieldValue $fieldValue)
    {
        $this->fieldValues[] = $fieldValue;

        return $this;
    }

    /**
     * Remove fieldValue
     *
     * @param \AppBundle\Entity\ContactFieldValue $fieldValue
     */
    public function removeFieldValue(\AppBundle\Entity\ContactFieldValue $fieldValue)
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
     * @param \AppBundle\Entity\ContactFieldSelectOption $choice
     *
     * @return ContactField
     */
    public function addChoice(\AppBundle\Entity\ContactFieldSelectOption $choice)
    {
        $this->choices[] = $choice;

        return $this;
    }

    /**
     * Remove choice
     *
     * @param \AppBundle\Entity\ContactFieldSelectOption $choice
     */
    public function removeChoice(\AppBundle\Entity\ContactFieldSelectOption $choice)
    {
        $this->choices->removeElement($choice);
    }

    /**
     * Set showOnContactList
     *
     * @param boolean $showOnContactList
     *
     * @return ContactField
     */
    public function setShowOnContactList($showOnContactList)
    {
        $this->showOnContactList = $showOnContactList;

        return $this;
    }

    /**
     * Get showOnContactList
     *
     * @return boolean
     */
    public function getShowOnContactList()
    {
        return $this->showOnContactList;
    }
}
