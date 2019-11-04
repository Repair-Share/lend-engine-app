<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Maintenance
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MaintenanceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Maintenance
{

    CONST STATUS_PLANNED = 'planned';
    CONST STATUS_OVERDUE = 'overdue';
    CONST STATUS_IN_PROGRESS = 'in_progress';
    CONST STATUS_COMPLETED = 'completed';
    CONST STATUS_SKIPPED = 'skipped';

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
     * @ORM\Column(name="status", type="string", length=32)
     */
    protected $status = Maintenance::STATUS_PLANNED;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="due_at", type="datetime")
     */
    private $dueAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="completed_at", type="datetime", nullable=true)
     */
    private $completedAt;

    /**
     * @var Contact
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="completed_by", referencedColumnName="id", nullable=true)
     */
    private $completedBy;

    /**
     * @var InventoryItem
     * @ORM\ManyToOne(targetEntity="InventoryItem", inversedBy="maintenances")
     * @ORM\JoinColumn(name="inventory_item_id", referencedColumnName="id", nullable=false)
     */
    private $inventoryItem;

    /**
     * @var MaintenancePlan
     * @ORM\ManyToOne(targetEntity="MaintenancePlan")
     * @ORM\JoinColumn(name="maintenance_plan_id", referencedColumnName="id", nullable=false)
     */
    private $maintenancePlan;

    /**
     * @var Contact
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="assigned_to", referencedColumnName="id", nullable=true)
     */
    private $assignedTo;

    /**
     * @var float
     * @ORM\Column(name="total_cost", type="decimal", scale=2)
     */
    private $totalCost = 0.00;

    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="string", length=2055, nullable=true)
     */
    private $notes;

    /**
     * @ORM\OneToMany(targetEntity="FileAttachment", mappedBy="maintenance", cascade={"persist", "remove"})
     */
    private $fileAttachments;


    public function __construct()
    {
        $this->fileAttachments = new ArrayCollection();
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
     * @param \DateTime $createdAt
     * @return Maintenance
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $dueAt
     * @return Maintenance
     */
    public function setDueAt($dueAt)
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDueAt()
    {
        return $this->dueAt;
    }

    /**
     * @param \DateTime $startedAt
     * @return Maintenance
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param \DateTime $completedAt
     * @return Maintenance
     */
    public function setCompletedAt($completedAt)
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCompletedAt()
    {
        return $this->completedAt;
    }
    
    /**
     * Set inventoryItem
     *
     * @param \AppBundle\Entity\InventoryItem $inventoryItem
     *
     * @return Maintenance
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
     * @param \AppBundle\Entity\Contact $assignedTo
     * @return Maintenance
     */
    public function setAssignedTo(Contact $assignedTo = null)
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getAssignedTo()
    {
        return $this->assignedTo;
    }

    /**
     * @param \AppBundle\Entity\Contact $completedBy
     * @return Maintenance
     */
    public function setCompletedBy(Contact $completedBy = null)
    {
        $this->completedBy = $completedBy;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getCompletedBy()
    {
        return $this->completedBy;
    }

    /**
     * @param MaintenancePlan $plan
     * @return $this
     */
    public function setMaintenancePlan(MaintenancePlan $plan)
    {
        $this->maintenancePlan = $plan;

        return $this;
    }

    /**
     * @return MaintenancePlan
     */
    public function getMaintenancePlan()
    {
        return $this->maintenancePlan;
    }

    /**
     * @return float
     */
    public function getTotalCost()
    {
        return $this->totalCost;
    }

    /**
     * @param $cost
     * @return $this
     */
    public function setTotalCost($cost)
    {
        $this->totalCost = $cost;

        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param $notes
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        switch ($status) {
            case $this::STATUS_PLANNED:
                $this->setStartedAt(null);
                $this->setCompletedAt(null);
                $this->setCompletedBy(null);
                break;
            case $this::STATUS_IN_PROGRESS:
                // Start if we've not already started
                if (!$this->getStartedAt()) {
                    $startedAt = new \DateTime();
                    $this->setStartedAt($startedAt);
                }
                break;
            case $this::STATUS_COMPLETED:
                $completedAt = new \DateTime();
                if (!$this->getCompletedAt()) {
                    $this->setCompletedAt($completedAt);
                }
                if (!$this->getStartedAt()) {
                    $this->setStartedAt($completedAt);
                }
                break;
            case $this::STATUS_SKIPPED:
                $completedAt = new \DateTime();
                if (!$this->getCompletedAt()) {
                    $this->setCompletedAt($completedAt);
                }
                $this->setStartedAt(null);
                break;
        }

        return $this;
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
}
