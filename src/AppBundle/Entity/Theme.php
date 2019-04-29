<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Theme
 *
 * @ORM\Table(name="theme")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ThemeRepository")
 */
class Theme
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=32, unique=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=32, unique=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=2055)
     */
    private $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="thumbnail", type="string", length=255, nullable=true)
     */
    private $thumbnail;

    /**
     * @var string|null
     *
     * @ORM\Column(name="font", type="string", length=32, nullable=true)
     */
    private $font;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="decimal", scale=2)
     */
    private $price;

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
     * Set name.
     *
     * @param string $name
     *
     * @return Theme
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Theme
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Theme
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set thumbnail.
     *
     * @param string|null $thumbnail
     *
     * @return Theme
     */
    public function setThumbnail($thumbnail = null)
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    /**
     * Get thumbnail.
     *
     * @return string|null
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set font.
     *
     * @param string|null $font
     *
     * @return Theme
     */
    public function setFont($font = null)
    {
        $this->font = $font;

        return $this;
    }

    /**
     * Get font.
     *
     * @return string|null
     */
    public function getFont()
    {
        return $this->font;
    }

    /**
     * Set price.
     *
     * @param string $price
     *
     * @return Theme
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price.
     *
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }
}
