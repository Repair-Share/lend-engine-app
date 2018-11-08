<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Note
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NoteRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Note
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
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="notes")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     */
    private $contact;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Loan", inversedBy="notes")
     * @ORM\JoinColumn(name="loan_id", referencedColumnName="id")
     */
    private $loan;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="InventoryItem", inversedBy="notes")
     * @ORM\JoinColumn(name="inventory_item_id", referencedColumnName="id")
     */
    private $inventoryItem;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=1024, nullable=true)
     */
    private $text;

    /**
     * @var integer
     *
     * @ORM\Column(name="admin_only", type="integer", nullable=true)
     */
    private $adminOnly;

    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt(new \DateTime("now"));
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
     * Set createdBy
     *
     * @param Contact $createdBy
     *
     * @return Note
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
        if (!$this->createdBy) {
            $this->createdBy = new Contact();
            $this->createdBy->setFirstName("Automation");
        }
        return $this->createdBy;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Note
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
     * Set contact
     *
     * @param Contact $contact
     *
     * @return Note
     */
    public function setContact($contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return integer
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set loan
     *
     * @param Loan $loan
     *
     * @return Note
     */
    public function setLoan($loan)
    {
        $this->loan = $loan;

        return $this;
    }

    /**
     * Get loan
     *
     * @return Loan
     */
    public function getLoan()
    {
        return $this->loan;
    }

    /**
     * Set text
     *
     * @param string $text
     *
     * @return Note
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set adminOnly
     *
     * @param boolean $adminOnly
     *
     * @return Note
     */
    public function setAdminOnly($adminOnly)
    {
        $this->adminOnly = $adminOnly;

        return $this;
    }

    /**
     * Get adminOnly
     *
     * @return boolean
     */
    public function getAdminOnly()
    {
        return $this->adminOnly;
    }

    /**
     * Set inventoryItem
     *
     * @param \AppBundle\Entity\InventoryItem $inventoryItem
     *
     * @return Note
     */
    public function setInventoryItem(\AppBundle\Entity\InventoryItem $inventoryItem = null)
    {
        $this->inventoryItem = $inventoryItem;

        return $this;
    }

    /**
     * Get inventoryItem
     *
     * @return \AppBundle\Entity\InventoryItem
     */
    public function getInventoryItem()
    {
        return $this->inventoryItem;
    }
}
