<?php
// src/AppBundle/Entity/Contact.php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\AttributeOverrides;
use Doctrine\ORM\Mapping\AttributeOverride;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Captcha\Bundle\CaptchaBundle\Validator\Constraints as CaptchaAssert;

/**
 * @ORM\Entity
 * @ORM\Table(name="contact", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="email",
 *          column=@ORM\Column(
 *              name     = "email",
 *              nullable = true,
 *              unique   = false
 *          )
 *      ),
 *      @ORM\AttributeOverride(name="emailCanonical",
 *          column=@ORM\Column(
 *              name     = "email_canonical",
 *              nullable = true,
 *              unique   = false
 *          )
 *      ),
 *      @ORM\AttributeOverride(name="username",
 *          column=@ORM\Column(
 *              name     = "username",
 *              nullable = true,
 *              unique   = false
 *          )
 *      ),
 *      @ORM\AttributeOverride(name="usernameCanonical",
 *          column=@ORM\Column(
 *              name     = "username_canonical",
 *              nullable = true,
 *              unique   = false
 *          )
 *      )
 * })
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ContactRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Contact extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"basket"})
     */
    protected $id;

    /**
     * @ORM\Column(name="facebook_id", type="string", length=255, nullable=true)
     */
    protected $facebook_id;

    /**
     * @ORM\Column(name="facebook_access_token", type="string", length=255, nullable=true)
     */
    protected $facebook_access_token;

    /**
     * @ORM\Column(name="google_id", type="string", length=255, nullable=true)
     */
    protected $google_id;

    /**
     * @ORM\Column(name="google_access_token", type="string", length=255, nullable=true)
     */
    protected $google_access_token;

    /**
     * @ORM\Column(name="twitter_id", type="string", length=255, nullable=true)
     */
    protected $twitter_id;

    /**
     * @ORM\Column(name="twitter_access_token", type="string", length=255, nullable=true)
     */
    protected $twitter_access_token;

    /**
     * @ORM\Column(name="twitter_access_token_secret", type="string", length=255, nullable=true)
     */
    protected $twitter_access_token_secret;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=32, nullable=true)
     * @Groups({"basket"})
     */
    protected $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=32, nullable=true)
     * @Groups({"basket"})
     */
    protected $lastName;

    /**
     * @ORM\Column(name="is_active", type="boolean", options={"default" = true})
     */
    private $isActive = true;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string", length=64, nullable=true)
     */
    private $telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="membership_number", type="string", length=64, nullable=true)
     */
    private $membershipNumber;

    /**
     * @var string
     *
     * @ORM\Column(name="address_line_1", type="string", length=255, nullable=true)
     */
    private $addressLine1;

    /**
     * @var string
     *
     * @ORM\Column(name="address_line_2", type="string", length=255, nullable=true)
     */
    private $addressLine2;

    /**
     * @var string
     *
     * @ORM\Column(name="address_line_3", type="string", length=255, nullable=true)
     */
    private $addressLine3;

    /**
     * @var string
     *
     * @ORM\Column(name="address_line_4", type="string", length=255, nullable=true)
     */
    private $addressLine4;

    /**
     * @var string
     *
     * @ORM\Column(name="country_iso_code", type="string", length=3, nullable=true)
     */
    private $countryIsoCode;

    /**
     * @var string
     *
     * @ORM\Column(name="latitude", type="string", length=32, nullable=true)
     */
    private $latitude;

    /**
     * @var string
     *
     * @ORM\Column(name="longitude", type="string", length=32, nullable=true)
     */
    private $longitude;

    /**
     * @var string
     *
     * @ORM\Column(name="gender", type="string", length=1, nullable=true)
     */
    private $gender;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Contact")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id", nullable=true)
     */
    private $createdBy;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="Membership", mappedBy="contact", cascade={"remove", "persist"})
     */
    private $memberships;

    /**
     * @ORM\OneToMany(targetEntity="Attendee", mappedBy="contact", cascade={"remove", "persist"})
     */
    private $attendees;

    /**
     * @ORM\OneToMany(targetEntity="WaitingListItem", mappedBy="contact", cascade={"remove"})
     */
    private $waitingListItems;

    /**
     * @var Membership
     *
     * @ORM\OneToOne(targetEntity="Membership")
     * @ORM\JoinColumn(name="active_membership", referencedColumnName="id", nullable=true)
     */
    private $activeMembership;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site")
     * @ORM\JoinColumn(name="active_site", referencedColumnName="id", nullable=true)
     */
    private $activeSite;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site")
     * @ORM\JoinColumn(name="created_at_site", referencedColumnName="id", nullable=true)
     */
    private $createdAtSite;

    /**
     * @ORM\OneToMany(targetEntity="Loan", mappedBy="contact")
     */
    private $loans;

    /**
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="contact", cascade={"remove"})
     */
    private $payments;

    /**
     * @ORM\OneToMany(targetEntity="Deposit", mappedBy="contact", cascade={"remove"})
     */
    private $deposits;

    /**
     * @ORM\OneToMany(targetEntity="Note", mappedBy="contact", cascade={"remove"})
     */
    private $notes;

    /**
     * @ORM\OneToMany(targetEntity="ContactFieldValue", mappedBy="contact", cascade={"persist", "remove"})
     */
    private $fieldValues;

    /**
     * @ORM\OneToMany(targetEntity="Child", mappedBy="contact", cascade={"persist", "remove"})
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="FileAttachment", mappedBy="contact", cascade={"persist", "remove"})
     */
    private $fileAttachments;

    /**
     * Cache the sum of fees and payments
     * @var float
     * @ORM\Column(name="balance", type="decimal", scale=2)
     */
    private $balance = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="stripe_customer_id", type="string", nullable=true)
     */
    private $stripeCustomerId;

    /**
     * @var string
     *
     * @ORM\Column(name="locale", type="string", nullable=true)
     */
    private $locale = 'en';

    /**
     * @var array
     */
    private $creditCards;

    /**
     * @var int
     * @ORM\Column(name="subscriber", type="boolean")
     */
    private $subscriber = false;

    /**
     * @CaptchaAssert\ValidCaptcha(message = "CAPTCHA validation failed, try again.",groups={"AppBundleRegistrationOff"})
     */
    protected $captchaCode;

    public function __construct()
    {
        parent::__construct();
        $this->memberships  = new ArrayCollection();
        $this->loans        = new ArrayCollection();
        $this->payments     = new ArrayCollection();
        $this->deposits     = new ArrayCollection();
        $this->notes        = new ArrayCollection();
        $this->fieldValues  = new ArrayCollection();
        $this->creditCards  = new ArrayCollection();
        $this->fileAttachments = new ArrayCollection();
        $this->waitingListItems = new ArrayCollection();
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
     * Gets triggered every time on update
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        if ($this->getCreatedAt() == null) {
            $this->setCreatedAt(new \DateTime('now'));
        }
    }

    public function getCaptchaCode()
    {
        return $this->captchaCode;
    }

    public function setCaptchaCode($captchaCode)
    {
        $this->captchaCode = $captchaCode;
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
     * Use the email address data here, we don't need a username
     * @return string
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $this->getEmail();

        return $this;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     *
     * @return Contact
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     *
     * @return Contact
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return Contact
     */
    public function setEmail($email)
    {
        $email = is_null($email) ? '' : $email;
        parent::setEmail($email);
        $this->setUsername($email);

        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     *
     * @return Contact
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @param string $membershipNumber
     *
     * @return Contact
     */
    public function setMembershipNumber($membershipNumber)
    {
        $this->membershipNumber = $membershipNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getMembershipNumber()
    {
        return $this->membershipNumber;
    }

    /**
     * Set addressLine1
     *
     * @param string $addressLine1
     *
     * @return Contact
     */
    public function setAddressLine1($addressLine1)
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    /**
     * Get addressLine1
     *
     * @return string
     */
    public function getAddressLine1()
    {
        return $this->addressLine1;
    }

    /**
     * Set addressLine2
     *
     * @param string $addressLine2
     *
     * @return Contact
     */
    public function setAddressLine2($addressLine2)
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    /**
     * Get addressLine2
     *
     * @return string
     */
    public function getAddressLine2()
    {
        return $this->addressLine2;
    }

    /**
     * Set addressLine3
     *
     * @param string $addressLine3
     *
     * @return Contact
     */
    public function setAddressLine3($addressLine3)
    {
        $this->addressLine3 = $addressLine3;

        return $this;
    }

    /**
     * Get addressLine3
     *
     * @return string
     */
    public function getAddressLine3()
    {
        return $this->addressLine3;
    }

    /**
     * Set addressLine4
     *
     * @param string $addressLine4
     *
     * @return Contact
     */
    public function setAddressLine4($addressLine4)
    {
        $this->addressLine4 = $addressLine4;

        return $this;
    }

    /**
     * Get addressLine4
     *
     * @return string
     */
    public function getAddressLine4()
    {
        return $this->addressLine4;
    }

    /**
     * Set countryIsoCode
     *
     * @param string $countryIsoCode
     *
     * @return Contact
     */
    public function setCountryIsoCode($countryIsoCode)
    {
        $this->countryIsoCode = $countryIsoCode;

        return $this;
    }

    /**
     * Get countryIsoCode
     *
     * @return string
     */
    public function getCountryIsoCode()
    {
        return $this->countryIsoCode;
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return Contact
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set createdBy
     *
     * @param integer $createdBy
     *
     * @return Contact
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Contact
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
     * @return mixed
     */
    public function getMemberships()
    {
        return $this->memberships;
    }

    /**
     * @return Membership
     */
    public function getActiveMembership()
    {
        return $this->activeMembership;
    }

    /**
     * @param \AppBundle\Entity\Membership $membership
     * @return $this
     */
    public function setActiveMembership($membership)
    {
        $this->activeMembership = $membership;

        return $this;
    }

    /**
     * @return Site
     */
    public function getActiveSite()
    {
        return $this->activeSite;
    }

    /**
     * @param \AppBundle\Entity\Site $activeSite
     * @return $this
     */
    public function setActiveSite($activeSite)
    {
        $this->activeSite = $activeSite;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLoans()
    {
        return $this->loans;
    }

    /**
     * Add membership
     *
     * @param \AppBundle\Entity\Membership $membership
     *
     * @return Contact
     */
    public function addMembership(Membership $membership)
    {
        $this->memberships[] = $membership;

        return $this;
    }

    /**
     * Remove $membership
     *
     * @param \AppBundle\Entity\Membership $memberships
     */
    public function removeMembership(Membership $memberships)
    {
        $this->memberships->removeElement($memberships);
    }

    /**
     * Add loan
     *
     * @param \AppBundle\Entity\Loan $loan
     *
     * @return Contact
     */
    public function addLoan(Loan $loan)
    {
        $this->loans[] = $loan;

        return $this;
    }

    /**
     * Remove loan
     *
     * @param \AppBundle\Entity\Loan $loan
     */
    public function removeLoan(Loan $loan)
    {
        $this->loans->removeElement($loan);
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->firstName.' '.$this->lastName;
    }

    /**
     * Add payment
     *
     * @param \AppBundle\Entity\Payment $payment
     *
     * @return Contact
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
     * @param $balance
     * @return $this
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Add Deposit
     *
     * @param \AppBundle\Entity\Deposit $deposit
     *
     * @return Contact
     */
    public function addDeposit(Deposit $deposit)
    {
        $this->deposits[] = $deposit;

        return $this;
    }

    /**
     * Remove Deposit
     *
     * @param \AppBundle\Entity\Deposit $deposit
     */
    public function removeDeposit(Deposit $deposit)
    {
        $this->deposits->removeElement($deposit);
    }

    /**
     * Get deposits
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function Deposits()
    {
        return $this->deposits;
    }

    /**
     * @return string
     */
    public function getStripeCustomerId()
    {
        return $this->stripeCustomerId;
    }

    /**
     * @param $stripeCustomerId
     * @return $this
     */
    public function setStripeCustomerId($stripeCustomerId)
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }

    /**
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Add notes
     *
     * @param \AppBundle\Entity\Note $note
     *
     * @return Loan
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
     * Key the array by the custom field ID to make it more usable
     * @return mixed
     */
    public function getFieldValues()
    {
        $values = array();
        foreach ($this->fieldValues AS $value) {
            $key = $value->getContactField()->getId();
            $values[$key] = $value;
        }
        $this->fieldValues = $values;

        return $this->fieldValues;
    }

    /**
     * Add $fieldValue
     *
     * @param \AppBundle\Entity\ContactFieldValue $fieldValue
     * @return Contact
     */
    public function addFieldValue(ContactFieldValue $fieldValue)
    {
        $this->fieldValues[] = $fieldValue;
        return $this;
    }

    /**
     * Remove $fieldValue
     *
     * @param \AppBundle\Entity\ContactFieldValue $fieldValue
     */
    public function removeFieldValue(ContactFieldValue $fieldValue)
    {
        $this->fieldValues->removeElement($fieldValue);
    }

    public function setFieldValues($fieldValues)
    {
        $this->fieldValues = $fieldValues;
    }

    /**
     * Add child
     *
     * @param \AppBundle\Entity\Child $child
     *
     * @return Contact
     */
    public function addChild(Child $child)
    {
        $child->setContact($this);

        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child
     *
     * @param \AppBundle\Entity\Child $child
     */
    public function removeChild(Child $child)
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add CreditCard
     *
     * @param \AppBundle\Entity\CreditCard $creditCard
     *
     * @return Loan
     */
    public function addCreditCard($creditCard)
    {

        $creditCard->setContact($this);

        $this->creditCards[] = $creditCard;

        return $this;
    }

    /**
     * Remove CreditCard
     *
     * @param \AppBundle\Entity\CreditCard $creditCard
     */
    public function removeCreditCards(\AppBundle\Entity\CreditCard $creditCard)
    {
        $this->notes->removeElement($creditCard);
    }

    /**
     * Get CreditCards
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCreditCards()
    {
        return $this->creditCards;
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     *
     * @return Contact
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param string $longitude
     *
     * @return Contact
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }


    /**
     * Set facebookId
     *
     * @param string $facebookId
     *
     * @return Contact
     */
    public function setFacebookId($facebookId)
    {
        $this->facebook_id = $facebookId;

        return $this;
    }

    /**
     * Get facebookId
     *
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebook_id;
    }

    /**
     * Set facebookAccessToken
     *
     * @param string $facebookAccessToken
     *
     * @return Contact
     */
    public function setFacebookAccessToken($facebookAccessToken)
    {
        $this->facebook_access_token = $facebookAccessToken;

        return $this;
    }

    /**
     * Get facebookAccessToken
     *
     * @return string
     */
    public function getFacebookAccessToken()
    {
        return $this->facebook_access_token;
    }

    /**
     * Set googleId
     *
     * @param string $googleId
     *
     * @return Contact
     */
    public function setGoogleId($googleId)
    {
        $this->google_id = $googleId;

        return $this;
    }

    /**
     * Get googleId
     *
     * @return string
     */
    public function getGoogleId()
    {
        return $this->google_id;
    }

    /**
     * Set googleAccessToken
     *
     * @param string $googleAccessToken
     *
     * @return Contact
     */
    public function setGoogleAccessToken($googleAccessToken)
    {
        $this->google_access_token = $googleAccessToken;

        return $this;
    }

    /**
     * Get googleAccessToken
     *
     * @return string
     */
    public function getGoogleAccessToken()
    {
        return $this->google_access_token;
    }

    /***
     * @param string $twitterId
     *
     * @return Contact
     */
    public function setTwitterId($twitterId)
    {
        $this->twitter_id = $twitterId;

        return $this;
    }

    /***
     * @return string
     */
    public function getTwitterId()
    {
        return $this->twitter_id;
    }

    /***
     * @param string $twitterAccessToken
     *
     * @return Contact
     */
    public function setTwitterAccessToken($twitterAccessToken)
    {
        $this->twitter_access_token = $twitterAccessToken;

        return $this;
    }

    /***
     * @return string
     */
    public function getTwitterAccessToken()
    {
        return $this->twitter_access_token;
    }

    /***
     * @param string $twitterAccessTokenSecret
     *
     * @return Contact
     */
    public function setTwitterAccessTokenSecret($twitterAccessTokenSecret)
    {
        $this->twitter_access_token_secret = $twitterAccessTokenSecret;

        return $this;
    }

    /***
     * @return string
     */
    public function getTwitterAccessTokenSecret()
    {
        return $this->twitter_access_token_secret;
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
     * Set subscriber
     *
     * @param integer $subscriber
     *
     * @return Contact
     */
    public function setSubscriber($subscriber)
    {
        $this->subscriber = $subscriber;

        return $this;
    }

    /**
     * Get subscriber
     *
     * @return boolean
     */
    public function getSubscriber()
    {
        return $this->subscriber;
    }

    /**
     * @param $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }


    /**
     * Add waitingListItem
     *
     * @param \AppBundle\Entity\WaitingListItem $waitingListItem
     *
     * @return Contact
     */
    public function addWaitingListItem(\AppBundle\Entity\WaitingListItem $waitingListItem)
    {
        $this->waitingListItems[] = $waitingListItem;

        return $this;
    }

    /**
     * Remove waitingListItem
     *
     * @param \AppBundle\Entity\WaitingListItem $waitingListItem
     */
    public function removeWaitingListItem(\AppBundle\Entity\WaitingListItem $waitingListItem)
    {
        $this->waitingListItems->removeElement($waitingListItem);
    }

    /**
     * Get waitingListItems
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getWaitingListItems()
    {
        return $this->waitingListItems;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     *
     * @return Contact
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
     * @return ArrayCollection
     */
    public function getAttendees()
    {
        return $this->attendees;
    }
}
