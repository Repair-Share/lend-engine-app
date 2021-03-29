<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SiteOpening
 *
 * @ORM\Table(name="site_opening", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SiteOpeningRepository")
 */
class SiteOpening
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
     * @var int
     *
     * @ORM\Column(name="week_day", type="integer")
     */
    private $weekDay;

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
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Site", inversedBy="siteOpenings")
     * @ORM\JoinColumn(name="site_id", referencedColumnName="id")
     */
    private $site;

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
     * Set weekDay
     *
     * @param integer $weekDay
     *
     * @return SiteOpening
     */
    public function setWeekDay($weekDay)
    {
        $this->weekDay = $weekDay;

        return $this;
    }

    /**
     * Get weekDay
     *
     * @return int
     */
    public function getWeekDay()
    {
        return $this->weekDay;
    }

    /**
     * Set timeFrom
     *
     * @param string $timeFrom
     *
     * @return SiteOpening
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
     * @return SiteOpening
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
     * @return SiteOpening
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
     * Set siteId
     *
     * @param Site $site
     *
     * @return SiteOpening
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
    public function getSiteId()
    {
        return $this->site;
    }

    public function getWeekDayName()
    {
        $weekDayNames = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return $weekDayNames[$this->weekDay];
    }

    /**
     * @return string
     */
    public function getFriendlyTimeFrom()
    {
        $time = (int)$this->getTimeFrom();

        if (!$time) {
            return '';
        }

        $timeFrom = new \DateTime('2020-01-01 ' . $this->getTimeFrom());
        return $timeFrom->format("g:i a");
    }

    /**
     * @return string
     */
    public function getFriendlyTimeTo()
    {
        $time = (int)$this->getTimeTo();

        if (!$time) {
            return '';
        }

        $timeTo = new \DateTime('2020-01-01 ' . $this->getTimeTo());
        return $timeTo->format("g:i a");
    }
}

