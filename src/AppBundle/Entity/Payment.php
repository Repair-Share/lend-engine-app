<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Payment
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PaymentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Payment
{
    const PAYMENT_TYPE_FEE      = 'FEE';     // money charged to member
    const PAYMENT_TYPE_REFUND   = 'REFUND';  // money refunded to member (usually deposit)
    const PAYMENT_TYPE_DEPOSIT  = 'DEPOSIT'; // money paid by member
    const PAYMENT_TYPE_PAYMENT  = 'PAYMENT'; // money paid by member
    const PAYMENT_TYPE_COST     = 'COST';

    const TEXT_PAYMENT_RECEIVED = "Payment received";
    const TEXT_PAYMENT_MADE = "Refund made";

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
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=36)
     */
    private $type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="payment_date", type="date")
     */
    private $paymentDate;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="decimal", scale=2)
     */
    private $amount = 0.00;

    /**
     * @var PaymentMethod
     *
     * @ORM\ManyToOne(targetEntity="PaymentMethod")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id")
     */
    private $paymentMethod;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="string", length=255, nullable=true)
     */
    private $note;

    /**
     * @var Loan
     *
     * @ORM\ManyToOne(targetEntity="Loan", inversedBy="payments")
     * @ORM\JoinColumn(name="loan_id", referencedColumnName="id", nullable=true)
     */
    private $loan;

    /**
     * @var Event
     *
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="payments")
     * @ORM\JoinColumn(name="event_id", referencedColumnName="id", nullable=true)
     */
    private $event;

    /**
     * @var InventoryItem
     *
     * @ORM\ManyToOne(targetEntity="InventoryItem", inversedBy="payments")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=true)
     */
    private $inventoryItem;

    /**
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="payments")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id", nullable=true)
     */
    private $contact;

    /**
     * @var Membership
     *
     * @ORM\ManyToOne(targetEntity="Membership", inversedBy="payments")
     * @ORM\JoinColumn(name="membership_id", referencedColumnName="id", nullable=true)
     */
    private $membership;

    /**
     * @var Deposit
     *
     * @ORM\ManyToOne(targetEntity="Deposit", inversedBy="payments")
     * @ORM\JoinColumn(name="deposit_id", referencedColumnName="id", nullable=true)
     */
    private $deposit;

    /**
     * @var string
     *
     * @ORM\Column(name="psp_code", type="string", length=255, nullable=true)
     */
    private $pspCode;

    // ## NON-DB, used in payment service

    /**
     * @var boolean
     */
    private $isDeposit;

    /**
     * @var LoanRow
     */
    private $loanRow = null;

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt(new \DateTime("now"));
        if (!$this->paymentDate) {
            $this->setPaymentDate(new \DateTime("now"));
        }
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
     * @return Payment
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
     * @return Payment
     */
    public function setCreatedBy($createdBy)
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
     * Set paymentDate
     *
     * @param \DateTime $paymentDate
     *
     * @return Payment
     */
    public function setPaymentDate($paymentDate)
    {
        $this->paymentDate = $paymentDate;

        return $this;
    }

    /**
     * Get paymentDate
     *
     * @return \DateTime
     */
    public function getPaymentDate()
    {
        return $this->paymentDate;
    }

    /**
     * Set paymentMethod
     *
     * @param PaymentMethod $paymentMethod
     *
     * @return Payment
     */
    public function setPaymentMethod(PaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod
     *
     * @return PaymentMethod
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Payment
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
     * Set amount
     *
     * @param float $amount
     *
     * @return Payment
     */
    public function setAmount($amount)
    {
        if ($amount > 0 && !$this->getType()) {
            // Money in
            $this->setType(Payment::PAYMENT_TYPE_PAYMENT);
        } else if (!$this->getType() && $amount < 0) {
            // Money out
            $amount = abs($amount);
            $this->setType(Payment::PAYMENT_TYPE_FEE);
        }

        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set note
     *
     * @param string $note
     *
     * @return Payment
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
     * Set $pspCode
     *
     * @param string $pspCode
     *
     * @return Payment
     */
    public function setPspCode($pspCode)
    {
        $this->pspCode = $pspCode;

        return $this;
    }

    /**
     * Get $pspCode
     *
     * @return string
     */
    public function getPspCode()
    {
        return $this->pspCode;
    }

    /**
     * Set inventoryItem
     *
     * @param \AppBundle\Entity\InventoryItem $inventoryItem
     *
     * @return Payment
     */
    public function setInventoryItem(InventoryItem $inventoryItem)
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

    /**
     * Set loan
     *
     * @param \AppBundle\Entity\Loan $loan
     *
     * @return Payment
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

    /**
     * Set contact
     *
     * @param \AppBundle\Entity\Contact $contact
     *
     * @return Payment
     */
    public function setContact(\AppBundle\Entity\Contact $contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact
     *
     * @return \AppBundle\Entity\Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set membership
     *
     * @param \AppBundle\Entity\Membership $membership
     *
     * @return Payment
     */
    public function setMembership(\AppBundle\Entity\Membership $membership = null)
    {
        $this->membership = $membership;

        return $this;
    }

    /**
     * Get membership
     *
     * @return \AppBundle\Entity\Membership
     */
    public function getMembership()
    {
        return $this->membership;
    }

    /**
     * Set Deposit
     *
     * @param \AppBundle\Entity\Deposit $deposit
     *
     * @return Payment
     */
    public function setDeposit(Deposit $deposit = null)
    {
        $this->deposit = $deposit;

        return $this;
    }

    /**
     * Get Deposit
     *
     * @return \AppBundle\Entity\Deposit
     */
    public function getDeposit()
    {
        return $this->deposit;
    }

    /**
     * @param $isDeposit bool
     * @return $this
     */
    public function setIsDeposit($isDeposit)
    {
        $this->isDeposit = $isDeposit;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIsDeposit()
    {
        if (!$this->isDeposit && $this->getDeposit()) {
            $this->setIsDeposit(true);
        }
        return $this->isDeposit;
    }

    /**
     * @param LoanRow $loanRow
     * @return $this
     *
     * Required when passing a payment through to payment service for a deposit against a loan item
     */
    public function setLoanRow(LoanRow $loanRow)
    {
        $this->loanRow = $loanRow;

        return $this;
    }

    /**
     * @return LoanRow
     */
    public function getLoanRow()
    {
        return $this->loanRow;
    }

    /**
     * @param Event $event
     * @return $this
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return Event
     */
    public function getEvent()
    {
        return $this->event;
    }
}
