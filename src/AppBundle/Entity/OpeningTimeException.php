<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * OpeningTimeException
 *
 * @ORM\Table(name="opening_time_exception", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\OpeningTimeExceptionRepository")
 */
class OpeningTimeException
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
     * @ORM\Column(name="date", type="date")
     */
    private $date;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="openingTimeExceptions")
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
     * @return OpeningTimeException
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
     * @return OpeningTimeException
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
     * @return OpeningTimeException
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
        return $this->timeFrom;
    }

    /**
     * Set timeChangeover
     *
     * @param string $timeChangeover
     *
     * @return OpeningTimeException
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
        return $this->timeChangeover;
    }

    /**
     * Set timeTo
     *
     * @param string $timeTo
     *
     * @return OpeningTimeException
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
        return $this->timeTo;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return OpeningTimeException
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
}

