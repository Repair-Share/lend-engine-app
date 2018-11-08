<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CoreLoan
 *
 * @ORM\Table(name="_core.loan")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CoreLoanRepository")
 */
class CoreLoan
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
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity="Account")
     * @ORM\JoinColumn(name="library", referencedColumnName="id")
     */
    private $library;

    /**
     * @var string
     *
     * @ORM\Column(name="member", type="string", length=255)
     */
    private $member;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var string
     *
     * @ORM\Column(name="item", type="string", length=255)
     */
    private $item;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255)
     */
    private $image;


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
     * Set library
     *
     * @param Account $library
     *
     * @return CoreLoan
     */
    public function setLibrary($library)
    {
        $this->library = $library;

        return $this;
    }

    /**
     * Get library
     *
     * @return Account
     */
    public function getLibrary()
    {
        return $this->library;
    }

    /**
     * Set member
     *
     * @param string $member
     *
     * @return CoreLoan
     */
    public function setMember($member)
    {
        $this->member = $member;

        return $this;
    }

    /**
     * Get member
     *
     * @return string
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return CoreLoan
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
     * Set item
     *
     * @param string $item
     *
     * @return CoreLoan
     */
    public function setItem($item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * Get item
     *
     * @return string
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set image
     *
     * @param string $image
     *
     * @return CoreLoan
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }
}

