<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * LoanRow
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LoanRowRepository")
 */
class LoanRow
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
     * @var Loan
     *
     * @ORM\ManyToOne(targetEntity="Loan", inversedBy="loanRows")
     * @ORM\JoinColumn(name="loan_id", referencedColumnName="id")
     */
    private $loan;

    /**
     * @var InventoryItem
     *
     * @ORM\ManyToOne(targetEntity="InventoryItem")
     * @ORM\JoinColumn(name="inventory_item_id", referencedColumnName="id")
     * @Groups({"basket"})
     */
    private $inventoryItem;

    /**
     * @var Deposit
     *
     * @ORM\OneToOne(targetEntity="Deposit")
     * @ORM\JoinColumn(name="deposit_id", referencedColumnName="id", nullable=true)
     */
    private $deposit;

    /**
     * @var integer
     *
     * @ORM\Column(name="product_quantity", type="integer")
     */
    private $productQuantity;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due_out_at", type="datetime", nullable=true)
     * @Groups({"basket"})
     */
    private $dueOutAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="due_in_at", type="datetime")
     * @Groups({"basket"})
     */
    private $dueInAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="checked_out_at", type="datetime", nullable=true)
     */
    private $checkedOutAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="checked_in_at", type="datetime", nullable=true)
     */
    private $checkedInAt;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site")
     * @ORM\JoinColumn(name="site_from", referencedColumnName="id")
     * @Groups({"basket"})
     */
    private $siteFrom;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site")
     * @ORM\JoinColumn(name="site_to", referencedColumnName="id")
     * @Groups({"basket"})
     */
    private $siteTo;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property
     * Required to show the image on the loan screen
     * @var string
     */
    private $imagePath;

    /**
     * @var float
     *
     * @ORM\Column(name="fee", type="decimal", scale=2)
     * @Groups({"basket"})
     */
    private $fee = 0;

    /**
     * @var int
     * @Groups({"basket"})
     */
    private $duration = 0;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->payments = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set inventory item
     *
     * @param InventoryItem $inventoryItem
     *
     * @return LoanRow
     */
    public function setInventoryItem(InventoryItem $inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;

        return $this;
    }

    /**
     * Get InventoryItem
     *
     * @return InventoryItem
     */
    public function getInventoryItem()
    {
        return $this->inventoryItem;
    }

    /**
     * Set productQuantity
     *
     * @param integer $productQuantity
     *
     * @return LoanRow
     */
    public function setProductQuantity($productQuantity)
    {
        $this->productQuantity = $productQuantity;

        return $this;
    }

    /**
     * Get productQuantity
     *
     * @return integer
     */
    public function getProductQuantity()
    {
        return $this->productQuantity;
    }

    /**
     * Set loan
     *
     * @param Loan $loan
     *
     * @return LoanRow
     */
    public function setLoan(Loan $loan = null)
    {
        $this->loan = $loan;

        return $this;
    }

    /**
     * Get loan
     *
     * @return \AppBundle\Entity\Loan
     */
    public function getLoan()
    {
        return $this->loan;
    }

    public function setImagePath($path)
    {
        $this->imagePath = $path;
    }

    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Set fee
     *
     * @param string $fee
     *
     * @return LoanRow
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Get fee
     *
     * @return string
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set dueOutAt
     *
     * @param \DateTime $dueOutAt
     *
     * @return LoanRow
     */
    public function setDueOutAt($dueOutAt)
    {
        $this->dueOutAt = $dueOutAt;
        $this->setDuration();

        return $this;
    }

    /**
     * Get dueOutAt
     *
     * @return \DateTime
     */
    public function getDueOutAt()
    {
        // Loans created without a reservation sometimes don't have dueOutAt on the row
        if (!$this->dueOutAt && $this->getLoan()) {
            return $this->getLoan()->getTimeOut();
        }
        return $this->dueOutAt;
    }

    /**
     * Set dueInAt
     *
     * @param \DateTime $dueInAt
     *
     * @return LoanRow
     */
    public function setDueInAt($dueInAt)
    {
        $this->dueInAt = $dueInAt;
        $this->setDuration();

        return $this;
    }

    /**
     * Get dueInAt
     *
     * @return \DateTime
     */
    public function getDueInAt()
    {
        // Loans created without a reservation sometimes don't have dueInAt on the row
        if (!$this->dueInAt && $this->getLoan()) {
            return $this->getLoan()->getTimeIn();
        } else if (!$this->dueInAt) {
            // So that modify() in basket does not throw exception
            $this->dueInAt = new \DateTime();
        }
        return $this->dueInAt;
    }

    /**
     * Set checkedInAt
     *
     * @param \DateTime $checkedInAt
     *
     * @return LoanRow
     */
    public function setCheckedInAt($checkedInAt)
    {
        $this->checkedInAt = $checkedInAt;

        return $this;
    }

    /**
     * Get checkedInAt
     *
     * @return \DateTime
     */
    public function getCheckedInAt()
    {
        return $this->checkedInAt;
    }

    /**
     * @return bool
     */
    public function getIsReturned()
    {
        if ($this->getcheckedInAt() > new \DateTime("0000-00-00")) {
            return true;
        } else {
            return false;
        }
    }

    public function getIsCheckedOut()
    {
        if ($this->getCheckedOutAt() > new \DateTime("0000-00-00")) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return integer
     */
    public function getDuration()
    {
        if (!$this->getDueOutAt() || !$this->getDueInAt()) {
            return 0;
        }
        $interval = $this->getDueOutAt()->diff($this->getDueInAt());
        $days = round($interval->format('%a'), 0);
        if ($days == 0) {
            $days = 1;
        }
        return $days;
    }

    /**
     * Set dynamically, Setter required for Serializer
     * @param $duration int
     * @return $this
     */
    public function setDuration($duration = null)
    {
        if ($duration == null) {
            $duration = $this->getDuration();
        }
        $this->duration = $duration;

        return $this;
    }

    /**
     * Set dateOut
     *
     * @param \DateTime $checkedOutAt
     *
     * @return LoanRow
     */
    public function setCheckedOutAt($checkedOutAt)
    {
        $this->checkedOutAt = $checkedOutAt;

        return $this;
    }

    /**
     * Get dateOut
     *
     * @return \DateTime
     */
    public function getCheckedOutAt()
    {
        return $this->checkedOutAt;
    }

    /**
     * @return Site
     */
    public function getSiteFrom()
    {
        return $this->siteFrom;
    }

    /**
     * @param $siteFrom Site
     * @return $this
     */
    public function setSiteFrom($siteFrom)
    {
        $this->siteFrom = $siteFrom;
        return $this;
    }

    /**
     * @return Site
     */
    public function getSiteTo()
    {
        return $this->siteTo;
    }

    /**
     * @param $siteTo Site
     * @return $this
     */
    public function setSiteTo($siteTo)
    {
        $this->siteTo = $siteTo;
        return $this;
    }

    /**
     * @param Deposit $deposit
     * @return $this
     */
    public function setDeposit(Deposit $deposit)
    {
        $this->deposit = $deposit;
        return $this;
    }

    /**
     * @return Deposit
     */
    public function getDeposit()
    {
        return $this->deposit;
    }

}
