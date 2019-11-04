<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Image
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FileAttachmentRepository")
 */
class FileAttachment
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
     * @ORM\Column(name="file_name", type="string", length=128)
     */
    private $fileName;

    /**
     * @var string
     *
     * @ORM\Column(name="file_size", type="integer")
     */
    private $fileSize = 0;

    /**
     * @var InventoryItem
     *
     * @ORM\ManyToOne(targetEntity="InventoryItem", inversedBy="fileAttachments")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=true)
     */
    private $inventoryItem;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="fileAttachments")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id", nullable=true)
     */
    private $contact;

    /**
     * @var Maintenance
     *
     * @ORM\ManyToOne(targetEntity="Maintenance", inversedBy="fileAttachments")
     * @ORM\JoinColumn(name="maintenance_id", referencedColumnName="id", nullable=true)
     */
    private $maintenance;

    /**
     * @var bool
     *
     * @ORM\Column(name="send_to_member", type="integer", length=1)
     */
    private $sendToMemberOnCheckout = false;

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
     * Set fileName
     *
     * @param string $fileName
     *
     * @return FileAttachment
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get fileSize
     *
     * @return string
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * Set fileSize
     *
     * @param string $fileSize
     *
     * @return FileAttachment
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    /**
     * Get fileName
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Set inventoryItem
     *
     * @param InventoryItem $inventoryItem
     *
     * @return FileAttachment
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
     * Set contact
     *
     * @param Contact $contact
     *
     * @return FileAttachment
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
     * Set sendToMemberOnCheckout
     *
     * @param integer $sendToMemberOnCheckout
     *
     * @return FileAttachment
     */
    public function setSendToMemberOnCheckout($sendToMemberOnCheckout)
    {
        $this->sendToMemberOnCheckout = $sendToMemberOnCheckout;

        return $this;
    }

    /**
     * Get sendToMemberOnCheckout
     *
     * @return integer
     */
    public function getSendToMemberOnCheckout()
    {
        return $this->sendToMemberOnCheckout;
    }

    /**
     * @return Maintenance
     */
    public function getMaintenance()
    {
        return $this->maintenance;
    }

    /**
     * @param Maintenance $maintenance
     * @return $this
     */
    public function setMaintenance(Maintenance $maintenance)
    {
        $this->maintenance = $maintenance;

        return $this;
    }

    /**
     * Removes the uniqid() added when uploading
     * @return mixed
     */
    public function getFriendlyName()
    {
        return preg_replace('/[0-9a-z]{13}-/', '', $this->fileName);
    }

}
