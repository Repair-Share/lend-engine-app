<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TenantNote
 *
 * @ORM\Table(name="_core.tenant_note")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TenantNoteRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TenantNote
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
     * @var Tenant
     *
     * @ORM\ManyToOne(targetEntity="Tenant")
     * @ORM\JoinColumn(name="tenant", referencedColumnName="id")
     */
    private $tenant;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="added_at", type="datetime")
     */
    private $addedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="note", type="string", length=255)
     */
    private $note;

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setAddedAt(new \DateTime("now"));
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
     * Set tenant
     *
     * @param Tenant $tenant
     *
     * @return TenantNote
     */
    public function setTenant($tenant)
    {
        $this->tenant = $tenant;

        return $this;
    }

    /**
     * Get tenant
     *
     * @return Tenant
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * Set addedAt
     *
     * @param \DateTime $addedAt
     *
     * @return TenantNote
     */
    public function setAddedAt($addedAt)
    {
        $this->addedAt = $addedAt;

        return $this;
    }

    /**
     * Get addedAt
     *
     * @return \DateTime
     */
    public function getAddedAt()
    {
        return $this->addedAt;
    }

    /**
     * Set note
     *
     * @param string $note
     *
     * @return TenantNote
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
}

