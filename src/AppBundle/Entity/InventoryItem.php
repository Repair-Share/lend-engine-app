<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
//use Gedmo\Mapping\Annotation as Gedmo;
//use Gedmo\Translatable\Translatable;

/**
 * InventoryItem
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\InventoryItemRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\HasLifecycleCallbacks
 */
class InventoryItem
{

    CONST TYPE_LOAN  = 'loan';
    CONST TYPE_KIT   = 'kit';
    CONST TYPE_STOCK = 'stock';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"basket"})
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="item_type", type="string", length=16, nullable=false)
     * @Groups({"basket"})
     */
    private $itemType = 'loan';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="donatedItems")
     * @ORM\JoinColumn(name="donated_by", referencedColumnName="id")
     */
    private $donatedBy;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="owned_by", referencedColumnName="id")
     */
    private $ownedBy;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="assigned_to", referencedColumnName="id", nullable=true)
     */
    private $assignedTo;

    /**
     * @var string
     *
     * @ORM\Column(name="sku", type="string", length=255, nullable=true)
     * @Groups({"basket"})
     */
    private $sku;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @Groups({"basket"})
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="description", type="string", length=1024, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="keywords", type="string", length=1024, nullable=true)
     */
    private $keywords;

    /**
     * @var string
     *
     * @ORM\Column(name="brand", type="string", length=1024, nullable=true)
     */
    private $brand;

    /**
     * @var string
     * @ORM\Column(name="care_information", type="string", length=1024, nullable=true)
     */
    private $careInformation;

    /**
     * @var string
     * @ORM\Column(name="component_information", type="string", length=1024, nullable=true)
     */
    private $componentInformation;

    /**
     * @ORM\OneToMany(targetEntity="KitComponent", mappedBy="inventoryItem", cascade={"persist", "remove"})
     */
    private $components;

    /**
     * @var string
     *
     * @ORM\Column(name="loan_fee", type="decimal", scale=2, nullable=true)
     */
    private $loanFee = null;

    /**
     * @var string
     *
     * @ORM\Column(name="deposit_amount", type="decimal", scale=2, nullable=true)
     * @Groups({"basket"})
     */
    private $depositAmount = null;

    /**
     * @var string
     *
     * @ORM\Column(name="max_loan_days", type="integer", nullable=true)
     */
    private $maxLoanDays = null;

    /**
     * @ORM\ManyToMany(targetEntity="ProductTag", inversedBy="inventoryItems")
     */
    private $tags;

    /**
     * @ORM\ManyToMany(targetEntity="Site", inversedBy="inventoryItems")
     */
    private $sites;

    /**
     * @ORM\ManyToMany(targetEntity="CheckInPrompt", inversedBy="inventoryItems")
     */
    private $checkInPrompts;

    /**
     * @ORM\ManyToMany(targetEntity="CheckOutPrompt", inversedBy="inventoryItems")
     */
    private $checkOutPrompts;

    /**
     * @ORM\OneToMany(targetEntity="Image", mappedBy="inventoryItem", cascade={"persist", "remove"})
     */
    private $images;

    /**
     * @ORM\OneToMany(targetEntity="FileAttachment", mappedBy="inventoryItem", cascade={"persist", "remove"})
     */
    private $fileAttachments;

    /**
     * @ORM\OneToMany(targetEntity="ProductFieldValue", mappedBy="inventoryItem", cascade={"persist", "remove"})
     */
    private $fieldValues;

    /**
     * @ORM\Column(name="is_active", type="boolean", options={"default" = true})
     */
    private $isActive = true;

    /**
     * @ORM\Column(name="is_reservable", type="boolean", options={"default" = true})
     */
    private $isReservable = true;

    /**
     * @ORM\Column(name="show_on_website", type="boolean", options={"default" = true})
     */
    private $showOnWebsite = true;

    /**
     * @ORM\ManyToOne(targetEntity="InventoryLocation", inversedBy="inventoryItems")
     * @ORM\JoinColumn(name="current_location_id", referencedColumnName="id")
     */
    private $inventoryLocation;

    /**
     * @ORM\OneToMany(targetEntity="ItemMovement", mappedBy="inventoryItem", cascade={"remove"})
     */
    private $itemMovements;

    /**
     * @ORM\OneToMany(targetEntity="Note", mappedBy="inventoryItem", cascade={"remove"})
     */
    private $notes;

    /**
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="inventoryItem", cascade={"remove"})
     */
    private $payments;

    /**
     * @var string
     * Used in views for ON LOAN, ON HOLD etc
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="serial", type="string", length=64, nullable=true)
     * @Groups({"basket"})
     */
    private $serial;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="string", length=128, nullable=true)
     */
    private $note;

    /**
     * @var string
     *
     * @ORM\Column(name="price_cost", type="decimal", scale=2, nullable=true)
     */
    private $priceCost;

    /**
     * @var string
     *
     * @ORM\Column(name="price_sell", type="decimal", scale=2, nullable=true)
     */
    private $priceSell;

    /**
     * @ORM\Column(name="image_name", type="string", length=255, nullable=true)
     *
     * @var string
     * @Groups({"basket"})
     */
    private $imageName;

    /**
     * Absolute path to the main image
     * @var string
     */
    private $imagePath;

    /**
     * Absolute path to the main thumbnail
     * @var string
     */
    private $imageThumbnailPath;

    /**
     * @var ItemCondition
     *
     * @ORM\ManyToOne(targetEntity="ItemCondition")
     * @ORM\JoinColumn(name="item_condition", referencedColumnName="id", nullable=true)
     */
    private $condition;

    /**
     * @ORM\Column(name="short_url", type="string", length=64, nullable=true)
     *
     * @var string
     */
    private $shortUrl;

    /**
     * @var ItemSector
     *
     * @ORM\ManyToOne(targetEntity="ItemSector")
     * @ORM\JoinColumn(name="item_sector", referencedColumnName="id", nullable=true)
     */
    private $itemSector;

    /** @var string */
    private $quantity;

    /** @var string */
    private $quantityAvailable;

    /**
     * Empty constructor
     */
    public function __construct()
    {
        $this->tags   = new ArrayCollection();
        $this->sites  = new ArrayCollection();
        $this->checkInPrompts = new ArrayCollection();
        $this->checkOutPrompts = new ArrayCollection();
        $this->fieldValues = new ArrayCollection();
        $this->fileAttachments = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->components = new ArrayCollection();
    }

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt(new \DateTime("now"));
        $this->setUpdatedAt(new \DateTime("now"));
    }

    /**
     * Gets triggered every time on update
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->setUpdatedAt(new \DateTime("now"));

        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }

    /**
     * @param $id int
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return InventoryItem
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdBy
     *
     * @param Contact $createdBy
     *
     * @return InventoryItem
     */
    public function setCreatedBy(Contact $createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return Contact
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function setOwnedBy(?Contact $ownedBy)
    {
        $this->ownedBy = $ownedBy;

        return $this;
    }

    public function getOwnedBy()
    {
        return $this->ownedBy;
    }

    public function setDonatedBy(?Contact $donatedBy)
    {
        $this->donatedBy = $donatedBy;

        return $this;
    }

    public function getDonatedBy()
    {
        return $this->donatedBy;
    }

    /**
     * Set serial number
     *
     * @param string $serial
     *
     * @return InventoryItem
     */
    public function setSerial($serial)
    {
        $this->serial = $serial;

        return $this;
    }

    /**
     * Get serial number
     *
     * @return string
     */
    public function getSerial()
    {
        return $this->serial;
    }

    /**
     * Set note
     *
     * @param string $note
     *
     * @return InventoryItem
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set priceCost
     * @param string $priceCost
     * @return InventoryItem
     */
    public function setPriceCost($priceCost)
    {
        $this->priceCost = $priceCost;

        return $this;
    }

    /**
     * Get priceCost
     * @return string
     */
    public function getPriceCost()
    {
        return $this->priceCost;
    }

    /**
     * Set priceSell
     * @param string $priceSell
     * @return InventoryItem
     */
    public function setPriceSell($priceSell)
    {
        $this->priceSell = $priceSell;

        return $this;
    }

    /**
     * Get priceSell
     * @return string
     */
    public function getPriceSell()
    {
        return $this->priceSell;
    }

    /**
     * Key the array by the custom field ID to make it more usable
     * @return mixed
     */
    public function getFieldValues()
    {
        $values = array();
        foreach ($this->fieldValues AS $value) {
            $key = $value->getProductField()->getId();
            $values[$key] = $value;
        }
        $this->fieldValues = $values;

        return $this->fieldValues;
    }

    /**
     * Return a UI friendly array of custom field values
     * @return array|ArrayCollection
     */
    public function getTextFieldValues()
    {
        $values = array();
        foreach ($this->fieldValues AS $value) {
            $key = $value->getProductField()->getId();
            if ($value->getProductField()->getType() == "choice") {
                $selectedOptionName = '';
                $options = $value->getProductField()->getChoices();
                foreach ($options AS $option) {
                    if ($option->getId() == $value->getFieldValue()) {
                        $selectedOptionName = $option->getOptionName();
                    }
                }
                $values[$key] = [
                    'name' => $value->getProductField()->getName(),
                    'content' => $selectedOptionName,
                    'showOnWebsite' => $value->getProductField()->getShowOnWebsite()
                ];
            } else if ($value->getProductField()->getType() == "multiselect") {
                $selectedOptions = [];
                $options = $value->getProductField()->getChoices();
                foreach ($options AS $option) {
                    if (in_array($option->getId(), explode(',', $value->getFieldValue()))) {
                        $selectedOptions[] = $option->getOptionName();
                    }
                }
                $values[$key] = [
                    'name' => $value->getProductField()->getName(),
                    'content' => implode(', ', $selectedOptions),
                    'showOnWebsite' => $value->getProductField()->getShowOnWebsite()
                ];
            } else if ($value->getProductField()->getType() == "checkbox") {
                if ($value->getFieldValue() == 1) {
                    $content = 'Yes';
                } else if ( $value->getFieldValue() != null ) {
                    $content = 'No';
                } else {
                    $content = null;
                }
                $values[$key] = [
                    'name' => $value->getProductField()->getName(),
                    'content' => $content,
                    'showOnWebsite' => $value->getProductField()->getShowOnWebsite()
                ];
            } else {
                $values[$key] = [
                    'name' => $value->getProductField()->getName(),
                    'content' => $value->getFieldValue(),
                    'showOnWebsite' => $value->getProductField()->getShowOnWebsite()
                ];
            }
        }
        return $values;
    }

    /**
     * Add $fieldValue
     *
     * @param \AppBundle\Entity\ProductFieldValue $fieldValue
     * @return InventoryItem
     */
    public function addFieldValue(ProductFieldValue $fieldValue)
    {
        if (!$this->fieldValues->contains($fieldValue)) {
            $this->fieldValues[] = $fieldValue;
        }
        return $this;
    }

    /**
     * Remove $fieldValue
     *
     * @param \AppBundle\Entity\ProductFieldValue $fieldValue
     */
    public function removeFieldValue(ProductFieldValue $fieldValue)
    {
        $this->fieldValues->removeElement($fieldValue);
    }

    /**
     * @param $fieldValues
     */
    public function setFieldValues($fieldValues)
    {
        $this->fieldValues = $fieldValues;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return InventoryItem
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return InventoryItem
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
     * Set sku
     *
     * @param string $sku
     *
     * @return InventoryItem
     */
    public function setSku($sku)
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * Get sku
     *
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return InventoryItem
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set keywords
     *
     * @param string $keywords
     *
     * @return InventoryItem
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get keywords
     *
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Set $careInformation
     *
     * @param string $careInformation
     *
     * @return InventoryItem
     */
    public function setCareInformation($careInformation)
    {
        $this->careInformation = $careInformation;

        return $this;
    }

    /**
     * Get $careInformation
     *
     * @return string
     */
    public function getCareInformation()
    {
        return $this->careInformation;
    }

    /**
     * Set $componentInformation
     *
     * @param string $componentInformation
     *
     * @return InventoryItem
     */
    public function setComponentInformation($componentInformation)
    {
        $this->componentInformation = $componentInformation;

        return $this;
    }

    /**
     * Get $componentInformation
     *
     * @return string
     */
    public function getComponentInformation()
    {
        return $this->componentInformation;
    }

    /**
     * Set loanFee
     *
     * @param string $loanFee
     *
     * @return InventoryItem
     */
    public function setLoanFee($loanFee)
    {
        $this->loanFee = $loanFee;

        return $this;
    }

    /**
     * Get loanFee
     *
     * @return string
     */
    public function getLoanFee()
    {
        return $this->loanFee;
    }

    /**
     * Set deposit amount
     *
     * @param string $depositAmount
     *
     * @return InventoryItem
     */
    public function setDepositAmount($depositAmount)
    {
        $this->depositAmount = $depositAmount;

        return $this;
    }

    /**
     * Get deposit amount
     *
     * @return float
     */
    public function getDepositAmount()
    {
        return $this->depositAmount;
    }

    /**
     * Set maxLoanDays
     *
     * @param integer $maxLoanDays
     *
     * @return InventoryItem
     */
    public function setMaxLoanDays($maxLoanDays)
    {
        $this->maxLoanDays = $maxLoanDays;

        return $this;
    }

    /**
     * Get maxLoanDays
     *
     * @return integer
     */
    public function getMaxLoanDays()
    {
        return $this->maxLoanDays;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return InventoryItem
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param boolean $isReservable
     *
     * @return InventoryItem
     */
    public function setIsReservable($isReservable)
    {
        $this->isReservable = $isReservable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsReservable()
    {
        return $this->isReservable;
    }

    /**
     * Add tag
     *
     * @param \AppBundle\Entity\ProductTag $tag
     *
     * @return InventoryItem
     */
    public function addTag(ProductTag $tag)
    {
        foreach ($this->tags AS $existingTag) {
            /** @var $existingTag \AppBundle\Entity\ProductTag */
            if ($tag->getName() == $existingTag->getName()) {
                return $this;
            }
        }
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag
     *
     * @param \AppBundle\Entity\ProductTag $tag
     */
    public function removeTag(ProductTag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param $tags
     * @return $this
     */
    public function setTags($tags)
    {
        $this->tags = new ArrayCollection();
        foreach ($tags AS $tag) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * Add site
     *
     * @param \AppBundle\Entity\Site $site
     *
     * @return InventoryItem
     */
    public function addSite(Site $site)
    {
        foreach ($this->sites AS $site) {
            /** @var $site \AppBundle\Entity\Site */
            if ($site->getName() == $site->getName()) {
                return $this;
            }
        }
        $this->sites[] = $site;

        return $this;
    }

    /**
     * Remove site
     *
     * @param \AppBundle\Entity\Site $site
     */
    public function removeSite(Site $site)
    {
        $this->sites->removeElement($site);
    }

    /**
     * Get sites
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSites()
    {
        return $this->sites;
    }

    /**
     * @param $sites \Doctrine\Common\Collections\Collection
     * @return $this
     */
    public function setSites($sites)
    {
        $this->sites = $sites;
        return $this;
    }

    /***
     * @param \AppBundle\Entity\CheckInPrompt $checkInPrompt
     *
     * @return InventoryItem
     */
    public function addCheckInPrompt(CheckInPrompt $checkInPrompt)
    {
        foreach ($this->checkInPrompts AS $existingCheckInPrompt) {
            /** @var $existingCheckInPrompt \AppBundle\Entity\CheckInPrompt */
            if ($checkInPrompt->getName() == $existingCheckInPrompt->getName()) {
                return $this;
            }
        }
        $this->checkInPrompts[] = $checkInPrompt;

        return $this;
    }

    /***
     * @param \AppBundle\Entity\CheckInPrompt $checkInPrompt
     */
    public function removeCheckInPrompt(CheckInPrompt $checkInPrompt)
    {
        $this->checkInPrompts->removeElement($checkInPrompt);
    }

    /***
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCheckInPrompts()
    {
        return $this->checkInPrompts;
    }

    /**
     * @param $prompts \Doctrine\Common\Collections\Collection
     * @return $this
     */
    public function setCheckInPrompts($prompts)
    {
        $this->checkInPrompts = $prompts;

        return $this;
    }

    /***
     * @param \AppBundle\Entity\CheckOutPrompt $checkOutPrompt
     *
     * @return InventoryItem
     */
    public function addCheckOutPrompt(CheckOutPrompt $checkOutPrompt)
    {
        foreach ($this->checkOutPrompts AS $existingCheckOutPrompt) {
            /** @var $existingCheckOutPrompt \AppBundle\Entity\CheckOutPrompt */
            if ($checkOutPrompt->getName() == $existingCheckOutPrompt->getName()) {
                return $this;
            }
        }
        $this->checkOutPrompts[] = $checkOutPrompt;

        return $this;
    }

    /***
     * @param \AppBundle\Entity\CheckOutPrompt $checkOutPrompt
     */
    public function removeCheckOutPrompt(CheckOutPrompt $checkOutPrompt)
    {
        $this->checkOutPrompts->removeElement($checkOutPrompt);
    }

    /***
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCheckOutPrompts()
    {
        return $this->checkOutPrompts;
    }

    /**
     * @param $prompts \Doctrine\Common\Collections\Collection
     * @return $this
     */
    public function setCheckOutPrompts($prompts)
    {
        $this->checkOutPrompts = $prompts;

        return $this;
    }

    /**
     * Add itemMovement
     *
     * @param \AppBundle\Entity\ItemMovement $itemMovement
     *
     * @return InventoryItem
     */
    public function addItemMovement(ItemMovement $itemMovement)
    {
        $this->itemMovements[] = $itemMovement;

        return $this;
    }

    /**
     * Remove itemMovement
     *
     * @param \AppBundle\Entity\ItemMovement $itemMovement
     */
    public function removeItemMovement(ItemMovement $itemMovement)
    {
        $this->itemMovements->removeElement($itemMovement);
    }

    /**
     * Get itemMovements
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItemMovements()
    {
        return $this->itemMovements;
    }

    /**
     * Set inventoryLocation
     *
     * @param \AppBundle\Entity\InventoryLocation $inventoryLocation
     *
     * @return InventoryItem
     */
    public function setInventoryLocation(InventoryLocation $inventoryLocation = null)
    {
        $this->inventoryLocation = $inventoryLocation;

        return $this;
    }

    /**
     * Get inventoryLocation
     *
     * @return \AppBundle\Entity\InventoryLocation
     */
    public function getInventoryLocation()
    {
        return $this->inventoryLocation;
    }

    /**
     * Add note
     *
     * @param \AppBundle\Entity\Note $note
     *
     * @return InventoryItem
     */
    public function addNote(Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * Remove note
     *
     * @param \AppBundle\Entity\Note $note
     */
    public function removeNote(Note $note)
    {
        $this->notes->removeElement($note);
    }

    /**
     * Get notes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Add payment
     *
     * @param \AppBundle\Entity\Payment $payment
     *
     * @return InventoryItem
     */
    public function addPayment(Payment $payment)
    {
        $this->payments[] = $payment;

        return $this;
    }

    /**
     * Remove payment
     *
     * @param \AppBundle\Entity\Payment $payment
     */
    public function removePayment(Payment $payment)
    {
        $this->payments->removeElement($payment);
    }

    /**
     * Get payments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * @return ArrayCollection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param Image $image
     * @return $this
     */
    public function addImage(Image $image)
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
        }

        return $this;
    }

    /**
     * @param Image $image
     * @return $this
     */
    public function removeImage(Image $image)
    {
        $this->images->remove($image);

        return $this;
    }

    /**
     * @param $images
     */
    public function setImages($images)
    {
        $this->images = $images;
    }

    /**
     * @return string
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * @param $imageName
     * @return $this
     */
    public function setImageName($imageName)
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * @param $imagePath
     * @return $this
     */
    public function setImagePath($imagePath)
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * @return string
     */
    public function getImageThumbnailPath()
    {
        return $this->imageThumbnailPath;
    }

    /**
     * @param $imageThumbnailPath
     * @return $this
     */
    public function setImageThumbnailPath($imageThumbnailPath)
    {
        $this->imageThumbnailPath = $imageThumbnailPath;

        return $this;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set assignedTo
     *
     * @param Contact $assignedTo
     *
     * @return InventoryItem
     */
    public function setAssignedTo($assignedTo)
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    /**
     * Get assignedTo
     *
     * @return Contact
     */
    public function getAssignedTo()
    {
        return $this->assignedTo;
    }

    /**
     * @return ArrayCollection
     */
    public function getFileAttachments()
    {
        return $this->fileAttachments;
    }

    /**
     * @param FileAttachment $fileAttachment
     * @return $this
     */
    public function addFileAttachment(FileAttachment $fileAttachment)
    {
        if (!$this->fileAttachments->contains($fileAttachment)) {
            $this->fileAttachments->add($fileAttachment);
        }

        return $this;
    }

    /**
     * @param FileAttachment $fileAttachment
     * @return $this
     */
    public function removeFileAttachment(FileAttachment $fileAttachment)
    {
        $this->fileAttachments->remove($fileAttachment);

        return $this;
    }

    /**
     * @param $attachments ArrayCollection
     * @return $this
     */
    public function setFileAttachments($attachments)
    {
        $this->fileAttachments = $attachments;

        return $this;
    }

    /**
     * @param ItemCondition $condition
     * @return $this
     */
    public function setCondition(ItemCondition $condition)
    {
        $this->condition = $condition;

        return $this;
    }

    /**
     * @return ItemCondition
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @param $type ItemSector
     * @return $this
     */
    public function setItemSector(ItemSector $type)
    {
        $this->itemSector = $type;

        return $this;
    }

    /**
     * @return ItemSector
     */
    public function getItemSector()
    {
        return $this->itemSector;
    }

    /**
     * @param $type string
     * @return $this
     */
    public function setItemType($type)
    {
        $this->itemType = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getItemType()
    {
        return $this->itemType;
    }

    /**
     * Set brand
     *
     * @param string $brand
     *
     * @return InventoryItem
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;

        return $this;
    }

    /**
     * Get brand
     *
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Set showOnWebsite
     *
     * @param boolean $showOnWebsite
     *
     * @return InventoryItem
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
     * Set shortUrl
     *
     * @param string $shortUrl
     *
     * @return InventoryItem
     */
    public function setShortUrl($shortUrl)
    {
        $this->shortUrl = $shortUrl;

        return $this;
    }

    /**
     * Get shortUrl
     *
     * @return string
     */
    public function getShortUrl()
    {
        return $this->shortUrl;
    }

    /**
     * @param $quantity
     * @return $this
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param $quantityAvailable
     * @return $this
     */
    public function setQuantityAvailable($quantityAvailable)
    {
        $this->quantityAvailable = $quantityAvailable;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuantityAvailable()
    {
        return $this->quantityAvailable;
    }

    /**
     * @return ArrayCollection
     */
    public function getComponents()
    {
        return $this->components;
    }

    /**
     * @param $component KitComponent
     * @return $this
     */
    public function addComponent(KitComponent $component)
    {
        if (!$this->components->contains($component)) {
            $this->components[] = $component;
        }

        return $this;
    }
}
