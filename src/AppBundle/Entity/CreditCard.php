<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CreditCard
 *
 */
class CreditCard
{
    /**
     * @var integer
     *
     */
    private $id;

    /**
     * @var string
     */
    private $cardId;

    /**
     * @var Contact
     */
    private $contact;

    /**
     * @var string
     */
    private $last4;

    /**
     * @var string
     */
    private $exp_month;

    /**
     * @var string
     */
    private $exp_year;

    /**
     * @var string
     */
    private $brand;

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
     * @return string
     */
    public function getCardId()
    {
        return $this->cardId;
    }

    /**
     * @param $cardId
     */
    public function setCardId($cardId)
    {
        $this->cardId = $cardId;
    }

    /**
     * @return string
     */
    public function getLast4()
    {
        return $this->last4;
    }

    /**
     * @param $last4
     */
    public function setLast4($last4)
    {
        $this->last4 = $last4;
    }

    /**
     * @return string
     */
    public function getExpMonth()
    {
        return $this->exp_month;
    }

    /**
     * @param $expMonth
     */
    public function setExpMonth($expMonth)
    {
        $this->exp_month = $expMonth;
    }

    /**
     * @return string
     */
    public function getExpYear()
    {
        return $this->exp_year;
    }

    /**
     * @param $expYear
     */
    public function setExpYear($expYear)
    {
        $this->exp_year = $expYear;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * @param $brand
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * @param Contact $contact
     * @return $this
     */
    public function setContact(Contact $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get Contact
     *
     * @return Contact
     */
    public function getContact()
    {
        return $this->contact;
    }

}
