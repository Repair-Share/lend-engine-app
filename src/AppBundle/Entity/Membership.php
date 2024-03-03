<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Membership
 *
 * @ORM\Table(options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MembershipRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Membership
{

    const SUBS_STATUS_PENDING = 'PENDING';
    const SUBS_STATUS_ACTIVE = 'ACTIVE';
    const SUBS_STATUS_CANCELLED = 'CANCELLED';
    const SUBS_STATUS_EXPIRED = 'EXPIRED';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="MembershipType")
     * @ORM\JoinColumn(name="subscription_id", referencedColumnName="id")
     */
    private $membershipType;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Contact", inversedBy="memberships")
     * @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
     */
    private $contact;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="decimal", scale=2)
     */
    private $price = 0.00;

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
     * @var \DateTime
     *
     * @ORM\Column(name="starts_at", type="datetime")
     */
    private $startsAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires_at", type="datetime")
     */
    private $expiresAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="status", type="string", length=32)
     */
    private $status = self::SUBS_STATUS_ACTIVE;

    /**
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="membership")
     */
    private $payments;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

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
    }

    /**
     * Set price
     *
     * @param string $price
     *
     * @return Membership
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Membership
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
     * Set expiresAt
     *
     * @param \DateTime $expiresAt
     *
     * @return Membership
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Get expiresAt
     *
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * Set startsAt
     *
     * @param \DateTime $startsAt
     *
     * @return Membership
     */
    public function setStartsAt($startsAt)
    {
        $this->startsAt = $startsAt;

        return $this;
    }

    /**
     * Get startsAt
     *
     * @return \DateTime
     */
    public function getStartsAt()
    {
        return $this->startsAt;
    }

    /**
     * Set MembershipType
     *
     * @param \AppBundle\Entity\MembershipType $membershipType
     *
     * @return Membership
     */
    public function setMembershipType(\AppBundle\Entity\MembershipType $membershipType = null)
    {
        $this->membershipType = $membershipType;

        return $this;
    }

    /**
     * Get subscription
     *
     * @return \AppBundle\Entity\MembershipType
     */
    public function getMembershipType()
    {
        return $this->membershipType;
    }

    /**
     * Set contact
     *
     * @param \AppBundle\Entity\Contact $contact
     *
     * @return Membership
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
     * Set createdBy
     *
     * @param \AppBundle\Entity\Contact $createdBy
     *
     * @return Membership
     */
    public function setCreatedBy(\AppBundle\Entity\Contact $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \AppBundle\Entity\Contact
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Membership
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
     * Add payment
     *
     * @param \AppBundle\Entity\Payment $payment
     *
     * @return Membership
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

    public function calculateStartAndExpiryDates()
    {
        $duration = $this->getMembershipType()->getDuration();

        // Work out how many days left on the existing membership
        // If it's a renewal (same type) and less than 14 days to run, set end date based on end of current membership
        $calculateExpiryBasedOnCurrentExpiryDate = false;
        if ($activeMembership = $this->getContact()->getActiveMembership()) {
            $dateDiff = $activeMembership->getExpiresAt()->diff(new \DateTime());
            if ($dateDiff->days < 14 && $activeMembership->getMembershipType() == $this->getMembershipType()) {
                $calculateExpiryBasedOnCurrentExpiryDate = true;
            }
        }

        // Always start from now
        // The previous will be expired so this one will start early
        $startsAt = new \DateTime();
        if ($calculateExpiryBasedOnCurrentExpiryDate == true) {
            // A renewal created before previous membership expires
            $expiresAt = $activeMembership->getExpiresAt();
        } else {
            // A new subscription
            $expiresAt = clone $startsAt;
        }

        $expiresAt->modify("+ {$duration} days");

        $this->setStartsAt($startsAt);
        $this->setExpiresAt($expiresAt);
    }

    public function subscribe(
        $em,
        $contact,
        $user,
        $paymentService,
        $price,
        $amountPaid,
        $paymentId,
        $paymentMethod = 0,
        $paymentNote = ''
    ) {
        $flashBag = [];

        $activeMembership = $contact->getActiveMembership();

        // Switch the contact to this new membership
        $contact->setActiveMembership($this);

        // If there was a previous one, expire it prematurely
        if ($activeMembership) {
            $activeMembership->setStatus(Membership::SUBS_STATUS_EXPIRED);
            $em->persist($activeMembership);
        }

        // update the contact and save everything
        $em->persist($contact);
        $em->flush();

        $note = new Note();
        $note->setContact($contact);
        $note->setCreatedBy($user);
        $note->setCreatedAt(new \DateTime());
        $note->setText("Subscribed to " . $this->getMembershipType()->getName() . " membership.");
        $em->persist($note);

        if ($price > 0) {
            // The membership fee
            $charge = new Payment();
            $charge->setAmount(-$price);
            $charge->setContact($contact);
            $charge->setCreatedBy($user);
            $charge->setMembership($this);

            if ($contact == $user) {
                $charge->setNote("Membership fee (self serve).");
            } else {
                $charge->setNote("Membership fee.");
            }

            if (!$paymentService->create($charge)) {
                foreach ($paymentService->errors as $error) {
                    $flashBag[] = [
                        'type' => 'error',
                        'msg'  => $error
                    ];
                }
            }
        }

        if ($amountPaid > 0) {
            // The payment for the charge

            if ($paymentId) {
                // We've created a payment via Stripe payment intent, link it to the credit
                $payments = $paymentService->get(['id' => $paymentId]);
                $payment  = $payments[0];
            } else {
                // No existing payment exists
                $payment = new Payment();
            }

            $payment->setCreatedBy($user);
            $payment->setPaymentMethod($paymentMethod);
            $payment->setAmount($amountPaid);
            $paymentNote = Payment::TEXT_PAYMENT_RECEIVED . '. ' . $paymentNote;
            $payment->setNote($paymentNote);
            $payment->setContact($contact);
            $payment->setMembership($this);

            if (!$paymentService->create($payment)) {
                foreach ($paymentService->errors as $error) {
                    $flashBag[] = [
                        'type' => 'error',
                        'msg'  => $error
                    ];
                }
            }
        }

        return $flashBag;
    }
}
