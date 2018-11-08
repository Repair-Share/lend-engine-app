<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContactFieldSelectOption
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContactFieldSelectOptionRepository")
 */
class ContactFieldSelectOption
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
     * @var ContactField
     *
     * @ORM\ManyToOne(targetEntity="ContactField", inversedBy="choices")
     * @ORM\JoinColumn(name="contact_field_id", referencedColumnName="id")
     */
    private $contactField;

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
     * Set contactField
     *
     * @param ContactField $contactField
     *
     * @return ContactFieldValue
     */
    public function setContactField($contactField)
    {
        $this->contactField = $contactField;

        return $this;
    }

    /**
     * Get contactField
     *
     * @return contactField
     */
    public function getContactField()
    {
        return $this->contactField;
    }

    /**
     * Set optionName
     *
     * @param string $optionName
     *
     * @return ContactFieldSelectOption
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

}
