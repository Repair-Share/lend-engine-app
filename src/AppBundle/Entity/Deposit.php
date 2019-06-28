<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Deposit
 *
 * @ORM\Table(name="deposit", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\DepositRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Deposit
{
    /**
     * @var int
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
     * @var Contact
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="deposits")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     */
    private $contact;

    /**
     * @var float
     * The initial amount of the deposit
     *
     * @ORM\Column(name="amount", type="decimal", scale=2)
     */
    private $amount = 0.00;

    /**
     * @var float
     * The amount due back to the member. Zero when fully refunded.
     * Updated when a payment is created with a deposit ID
     *
     * @ORM\Column(name="balance", type="decimal", scale=2)
     */
    private $balance = 0.00;

    /**
     * @var LoanRow
     *
     * @ORM\OneToOne(targetEntity="LoanRow", mappedBy="deposit", )
     * @ORM\JoinColumn(name="loan_row_id", referencedColumnName="id")
     */
    private $loanRow;

    /**
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="deposit")
     */
    private $payments;
    
    /**
     * 
     */
    public function __construct()
    {
        $this->payments     = new ArrayCollection();
    }
    
    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt(new \DateTime("now"));
        $this->balance = $this->amount;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt.
     *
     * @param \DateTime $createdAt
     *
     * @return Deposit
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdBy.
     *
     * @param Contact $createdBy
     *
     * @return Deposit
     */
    public function setCreatedBy(Contact $createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return int
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
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
     * Set amount.
     *
     * @param string $amount
     *
     * @return Deposit
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set balance.
     *
     * @param string $balance
     *
     * @return Deposit
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance.
     *
     * @return string
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set loanRowId.
     *
     * @param LoanRow $loanRow
     *
     * @return Deposit
     */
    public function setLoanRow($loanRow)
    {
        $this->loanRow = $loanRow;

        return $this;
    }

    /**
     * Get loanRow.
     *
     * @return LoanRow
     */
    public function getLoanRow()
    {
        return $this->loanRow;
    }

    /**
     * Add payment
     *
     * @param \AppBundle\Entity\Payment $payment
     *
     * @return Deposit
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

}
