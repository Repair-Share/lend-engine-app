<?php

namespace AppBundle\Entity;

use AppBundle\Helpers\DateTimeHelper;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Event
 *
 * @ORM\Table(name="event", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\EventRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Event
{
    const STATUS_DRAFT      = 'DRAFT';
    const STATUS_PUBLISHED  = 'PUBLISHED';
    const STATUS_PAST       = 'PAST';

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
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="events")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id")
     */
    private $site;

    /**
     * @var string
     *
     * @ORM\Column(name="time_from", type="string", length=4)
     */
    private $timeFrom;

    /**
     * @var string
     *
     * @ORM\Column(name="time_changeover", type="string", length=4, nullable=true)
     */
    private $timeChangeover;

    /**
     * @var string
     *
     * @ORM\Column(name="time_to", type="string", length=4)
     */
    private $timeTo;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=1)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=256, nullable=true)
     */
    private $title;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_bookable", type="boolean")
     */
    private $isBookable = true;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_url", type="string", length=256, nullable=true)
     */
    private $facebookUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=16)
     */
    private $status = '';

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=1024, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="decimal", scale=2)
     */
    private $price = 0.00;

    /**
     * @var string
     *
     * @ORM\Column(name="max_attendees", type="integer")
     */
    private $maxAttendees = 0;

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
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Attendee", mappedBy="event", cascade={"remove", "persist"})
     */
    private $attendees;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Payment", mappedBy="event", cascade={"persist"})
     */
    private $payments;

    private $utcFrom;
    private $utcTo;

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt(new \DateTime("now"));
    }

    function __construct()
    {
        $this->attendees = new ArrayCollection();
        $this->payments  = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Event
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set siteId
     *
     * @param Site $site
     *
     * @return Event
     */
    public function setSite($site)
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Get siteId
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set timeFrom
     *
     * @param string $timeFrom
     *
     * @return Event
     */
    public function setTimeFrom($timeFrom)
    {
        $this->timeFrom = $timeFrom;

        return $this;
    }

    /**
     * Get timeFrom
     *
     * @return string
     */
    public function getTimeFrom()
    {
        return DateTimeHelper::parseTime($this->timeFrom);
    }

    /**
     * Set timeChangeover
     *
     * @param string $timeChangeover
     *
     * @return Event
     */
    public function setTimeChangeover($timeChangeover)
    {
        $this->timeChangeover = $timeChangeover;

        return $this;
    }

    /**
     * Get timeChangeover
     *
     * @return string
     */
    public function getTimeChangeover()
    {
        return DateTimeHelper::parseTime($this->timeChangeover);
    }

    /**
     * Set timeTo
     *
     * @param string $timeTo
     *
     * @return Event
     */
    public function setTimeTo($timeTo)
    {
        $this->timeTo = $timeTo;

        return $this;
    }

    /**
     * Get timeTo
     *
     * @return string
     */
    public function getTimeTo()
    {
        return DateTimeHelper::parseTime($this->timeTo);
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Event
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
     * @param $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $price
     * @return $this
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Page
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Set createdBy
     *
     * @param Contact $createdBy
     *
     * @return Event
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
     * @param $maxAttendees
     * @return $this
     */
    public function setMaxAttendees($maxAttendees)
    {
        $this->maxAttendees = $maxAttendees;

        return $this;
    }

    /**
     * @return string
     */
    public function getMaxAttendees()
    {
        return $this->maxAttendees;
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
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $url
     * @return $this
     */
    public function setFacebookUrl($url)
    {
        $this->facebookUrl = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getFacebookUrl()
    {
        return $this->facebookUrl;
    }

    /**
     * @return ArrayCollection
     */
    public function getAttendees()
    {
        return $this->attendees;
    }

    /**
     * @param $attendee
     * @return $this
     */
    public function addAttendee($attendee)
    {
        if (!$this->getAttendees()->contains($attendee)) {
            $this->attendees[] = $attendee;
        }

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getPayments() {
        return $this->payments;
    }

    /**
     * @return int
     */
    public function getRevenue()
    {
        $amount = 0;
        foreach ($this->payments AS $payment) {
            $amount += $payment->getAmount();
        }
        return (float)$amount;
    }

    /**
     * @return bool
     */
    public function getIsBookable()
    {
        return $this->isBookable;
    }

    /**
     * @param $isBookable
     * @return $this
     */
    public function setIsBookable($isBookable)
    {
        $this->isBookable = $isBookable;

        return $this;
    }

    /**
     * @return string
     */
    public function getFriendlyTimeFrom()
    {
        $timeFrom = new \DateTime($this->getDate()->format("Y-m-d") . ' ' . $this->getTimeFrom());
        return $timeFrom->format("g:i a");
    }

    /**
     * @return string
     */
    public function getFriendlyTimeTo()
    {
        $timeTo = new \DateTime($this->getDate()->format("Y-m-d") . ' ' . $this->getTimeTo());
        return $timeTo->format("g:i a");
    }

    public function setUTCFrom($from)
    {
        $this->utcFrom = $from;
    }

    public function setUTCTo($to)
    {
        $this->utcTo = $to;
    }

    public function getUTCFrom()
    {
        return $this->utcFrom;
    }

    public function getUTCTo()
    {
        return $this->utcTo;
    }
}

