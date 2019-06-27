<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Loan
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\LoanRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Loan
{

    const STATUS_PENDING  = 'PENDING';
    const STATUS_RESERVED = 'RESERVED';
    const STATUS_ACTIVE   = 'ACTIVE';
    const STATUS_CLOSED   = 'CLOSED';
    const STATUS_OVERDUE  = 'OVERDUE';
    const STATUS_CANCELLED  = 'CANCELLED';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="loans")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     * @Groups({"basket"})
     */
    protected $contact;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=32)
     */
    protected $status = Loan::STATUS_PENDING;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site")
     * @ORM\JoinColumn(name="created_at_site", referencedColumnName="id", nullable=true)
     */
    protected $createdAtSite;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datetime_out", type="datetime")
     */
    protected $timeOut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datetime_in", type="datetime")
     */
    protected $timeIn;

    /**
     * @var string
     *
     * @ORM\Column(name="reference", type="string", length=32, nullable=true)
     */
    protected $reference;

    /**
     * @ORM\OneToMany(targetEntity="LoanRow", mappedBy="loan", cascade={"persist", "remove"})
     * @Groups({"basket"})
     */
    protected $loanRows;

    /**
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="loan", cascade={"remove"})
     */
    protected $payments;

    /**
     * @ORM\OneToMany(targetEntity="Note", mappedBy="loan", cascade={"remove"})
     */
    private $notes;

    /**
     * To allow admin to change the fee amount for the basket
     * @var float
     * @Groups({"basket"})
     */
    public $reservationFee;

    /**
     * @var float
     * The sum of LoanRow.fee
     * @ORM\Column(name="total_fee", type="decimal", scale=2)
     */
    protected $totalFee;

    /**
     * @var float
     * Calculated
     */
    protected $balance;

    /**
     * @var float
     * Calculated
     */
    protected $totalDeposits;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loanRows = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->notes    = new ArrayCollection();
    }

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt(new \DateTime("now"));
        if (!$this->getTimeIn()) {
            $this->setTimeIn(new \DateTime("now"));
        }
        if (!$this->getTimeOut()) {
            $this->setTimeOut(new \DateTime("now"));
        }
        $this->setTotalFee();
        $this->setReturnDate();
    }

    /**
     * Gets triggered every time on update
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
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
     * Set contact
     *
     * @param Contact $contact
     *
     * @return Loan
     */
    public function setContact(Contact $contact)
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Loan
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
     * @return Loan
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
     * Set createdAtSite
     *
     * @param Site $createdAtSite
     *
     * @return Loan
     */
    public function setCreatedAtSite(Site $createdAtSite)
    {
        $this->createdAtSite = $createdAtSite;

        return $this;
    }

    /**
     * Get createdAtSite
     *
     * @return Site
     */
    public function getCreatedAtSite()
    {
        return $this->createdAtSite;
    }

    /**
     * Add loanRow
     *
     * @param \AppBundle\Entity\LoanRow $loanRow
     *
     * @return Loan
     */
    public function addLoanRow(\AppBundle\Entity\LoanRow $loanRow)
    {
        $this->loanRows[] = $loanRow;

        return $this;
    }

    /**
     * Remove loanRow
     *
     * @param \AppBundle\Entity\LoanRow $loanRow
     */
    public function removeLoanRow(\AppBundle\Entity\LoanRow $loanRow)
    {
        $this->loanRows->removeElement($loanRow);
    }

    /**
     * Get loanRows
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLoanRows()
    {
        return $this->loanRows;
    }

    /**
     * Set ref
     *
     * @param string $reference
     *
     * @return Loan
     */
    public function setReference($reference)
    {
        $this->reference = $reference;

        return $this;
    }

    /**
     * Get reference
     *
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Loan
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set timeOut
     *
     * @param \DateTime $timeOut
     *
     * @return Loan
     */
    public function setTimeOut($timeOut)
    {
        $this->timeOut = $timeOut;

        return $this;
    }

    /**
     * Get timeOut
     *
     * @return \DateTime
     */
    public function getTimeOut()
    {
        return $this->timeOut;
    }

    /**
     * Set timeIn
     *
     * @param \DateTime $timeIn
     *
     * @return Loan
     */
    public function setTimeIn($timeIn)
    {
        $this->timeIn = $timeIn;

        return $this;
    }

    /**
     * Get timeIn
     *
     * @return \DateTime
     */
    public function getTimeIn()
    {
        return $this->timeIn;
    }

    /**
     * Set the loan return date as the date of the last item due back
     */
    public function setReturnDate()
    {
        $dateDue = new \DateTime("-1 year");
        foreach ($this->getLoanRows() AS $row) {
            /** @var $row \AppBundle\Entity\LoanRow */
            if ($row->getDueInAt() > $dateDue) {
                $dateDue = $row->getDueInAt();
            }
        }
        $this->setTimeIn($dateDue);

        if ($dateDue < new \DateTime() && $this->getStatus() == Loan::STATUS_ACTIVE) {
            $this->setStatus(Loan::STATUS_OVERDUE);
        } else if ($dateDue > new \DateTime() && $this->getStatus() == Loan::STATUS_OVERDUE) {
            $this->setStatus(Loan::STATUS_ACTIVE);
        }

    }

    /**
     * @return integer
     */
    public function getDuration()
    {
        $days = 0;
        if ($this->timeOut) {
            $interval = $this->timeOut->diff($this->timeIn);
            $days = (int)$interval->format('%a');
        }
        return $days;
    }

    /**
     * Add payment
     *
     * @param \AppBundle\Entity\Payment $payment
     *
     * @return Loan
     */
    public function addPayment(\AppBundle\Entity\Payment $payment)
    {
        $this->payments[] = $payment;

        return $this;
    }

    /**
     * Remove payment
     *
     * @param \AppBundle\Entity\Payment $payment
     */
    public function removePayment(\AppBundle\Entity\Payment $payment)
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
     * Add notes
     *
     * @param \AppBundle\Entity\Note $note
     *
     * @return Loan
     */
    public function addNote(\AppBundle\Entity\Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * Remove note
     *
     * @param \AppBundle\Entity\Note $note
     */
    public function removeNote(\AppBundle\Entity\Note $note)
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
     * @param $fee
     * @return $this
     */
    public function setReservationFee($fee) {
        $this->reservationFee = $fee;
        return $this;
    }

    /**
     * @return float
     */
    public function getReservationFee() {
        return $this->reservationFee;
    }

    /**
     * @param $totalFee
     * @return $this
     */
    public function setTotalFee($totalFee = 0)
    {
        if ($totalFee) {
            $this->totalFee = $totalFee;
        } else {
            // Automatically calculate on persist
            $this->totalFee = $this->getTotalFee();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getTotalFee()
    {
        $totalFee = 0.00;
        if (count($this->getPayments()) > 0) {
            /** @var \AppBundle\Entity\Payment $payment */
            foreach ($this->getPayments() AS $payment) {
                if ($payment->getType() == Payment::PAYMENT_TYPE_FEE && !$payment->getInventoryItem()) {
                    $totalFee += $payment->getAmount();
                }
            }
        }
        foreach ($this->getLoanRows() AS $loanRow) {
            $totalFee += $loanRow->getFee();
        }
        $this->totalFee = $totalFee;
        return $this->totalFee;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        $charges = $this->getChargedTotal();
        $loanBalance = $this->getTotalFee() - $charges;

        return $loanBalance;
    }

    /**
     * Fees charged against the member account
     * @return float
     */
    public function getChargedTotal()
    {
        $charges = 0.00;
        foreach ($this->getPayments() AS $payment) {
            if ($payment->getType() == Payment::PAYMENT_TYPE_FEE) {
                $charges += $payment->getAmount();
            }
        }

        return $charges;
    }

    public function getItemsTotal()
    {
        $total = 0.00;
        /** @var \AppBundle\Entity\LoanRow $loanRow */
        foreach ($this->getLoanRows() AS $loanRow) {
            $total += $loanRow->getFee();
        }

        return $total;
    }

    /**
     * @return float
     */
    public function getTotalDeposits()
    {
        $totalDeposits = 0.00;
        foreach ($this->loanRows AS $row) {
            /** @var $deposit \AppBundle\Entity\Deposit */
            if ($deposit = $row->getDeposit()) {
                $totalDeposits += $deposit->getAmount();
            }
        }
        return $totalDeposits;
    }

}
