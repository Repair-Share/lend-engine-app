<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemMovement
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ItemMovementRepository")
 * @ORM\HasLifecycleCallbacks
 */
class ItemMovement
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
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt(new \DateTime("now"));
    }

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     *
     * @ORM\ManyToOne(targetEntity="InventoryItem", inversedBy="itemMovements")
     * @ORM\JoinColumn(name="inventory_item_id", referencedColumnName="id", nullable=false)
     */
    private $inventoryItem;

    /**
     *
     * @ORM\ManyToOne(targetEntity="InventoryLocation")
     * @ORM\JoinColumn(name="inventory_location_id", referencedColumnName="id", nullable=false)
     */
    private $inventoryLocation;

    /**
     *
     * @ORM\OneToOne(targetEntity="LoanRow")
     * @ORM\JoinColumn(name="loan_row_id", referencedColumnName="id", nullable=true)
     */
    private $loanRow;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="assigned_to_contact_id", referencedColumnName="id")
     */
    private $assignedTo;

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
     * @return ItemMovement
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
     * @return ItemMovement
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

    /**
     * Set inventoryItem
     *
     * @param \AppBundle\Entity\InventoryItem $inventoryItem
     *
     * @return ItemMovement
     */
    public function setInventoryItem(InventoryItem $inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;

        return $this;
    }

    /**
     * Get inventoryItem
     *
     * @return InventoryItem
     */
    public function getInventoryItem()
    {
        return $this->inventoryItem;
    }

    /**
     * Set inventoryLocation
     *
     * @param \AppBundle\Entity\InventoryLocation $inventoryLocation
     *
     * @return ItemMovement
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
     * Set loanRow
     *
     * @param \AppBundle\Entity\LoanRow $loanRow
     *
     * @return ItemMovement
     */
    public function setLoanRow(LoanRow $loanRow = null)
    {
        $this->loanRow = $loanRow;

        return $this;
    }

    /**
     * Get loanRow
     *
     * @return \AppBundle\Entity\LoanRow
     */
    public function getLoanRow()
    {
        return $this->loanRow;
    }

    /**
     * Set assignedTo
     *
     * @param \AppBundle\Entity\Contact $assignedTo
     *
     * @return ItemMovement
     */
    public function setAssignedTo(Contact $assignedTo = null)
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    /**
     * Get assignedTo
     *
     * @return \AppBundle\Entity\Contact
     */
    public function getAssignedTo()
    {
        return $this->assignedTo;
    }
}
