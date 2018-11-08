<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContactFieldValue
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContactFieldValueRepository")
 */
class ContactFieldValue
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
     * @ORM\ManyToOne(targetEntity="ContactField", inversedBy="fieldValues")
     * @ORM\JoinColumn(name="contact_field_id", referencedColumnName="id")
     */
    private $contactField;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="fieldValues")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     */
    private $contact;

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
     * Set contact
     *
     * @param Contact $contact
     *
     * @return ContactFieldValue
     */
    public function setContact($contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
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
     * Set fieldValue
     *
     * @param string $fieldValue
     *
     * @return ContactFieldValue
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
