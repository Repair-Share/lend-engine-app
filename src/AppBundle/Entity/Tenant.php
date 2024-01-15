<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Tenant
 *
 * @ORM\Table(name="_core.account", options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity
 */
class Tenant
{

    const STATUS_PENDING  = 'PENDING';
    const STATUS_DEPLOYING  = 'DEPLOYING';
    const STATUS_TRIAL    = 'TRIAL';
    const STATUS_LIVE     = 'LIVE';
    const STATUS_CANCEL   = 'CANCELLED';
    const STATUS_ARCHIVED = 'ARCHIVED';
    const STATUS_DELETED  = 'DELETED';

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(name="stub", type="string", length=32, nullable=false)
     */
    private $stub;

    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(name="industry", type="string", length=255, nullable=true)
     */
    private $industry;

    /**
     * @var string
     * @ORM\Column(name="db_schema", type="string", length=255, nullable=false)
     */
    private $dbSchema;

    /**
     * @var string
     * @ORM\Column(name="domain", type="string", length=255, nullable=true)
     */
    private $domain;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="trial_expires_at", type="datetime", nullable=true)
     */
    private $trialExpiresAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="last_access_at", type="datetime", nullable=true)
     */
    private $lastAccessAt;

    /**
     * @var string
     * @ORM\Column(name="owner_name", type="string", length=255, nullable=false)
     */
    private $ownerName;

    /**
     * @var string
     * @ORM\Column(name="owner_email", type="string", length=255, nullable=false)
     */
    private $ownerEmail;

    /**
     * @var string
     * @ORM\Column(name="org_email", type="string", length=255, nullable=false)
     */
    private $orgEmail;

    /**
     * @var string
     * @ORM\Column(name="status", type="string", length=255, nullable=false)
     */
    private $status = 'PENDING';

    /**
     * @var string
     * @ORM\Column(name="plan", type="string", length=32, nullable=true)
     */
    private $plan;

    /**
     * @var string
     *
     * @ORM\Column(name="stripe_customer_id", type="string", nullable=true)
     */
    private $stripeCustomerId;

    /**
     * @var string
     *
     * @ORM\Column(name="subscription_id", type="string", nullable=true)
     */
    private $subscriptionId;

    /**
     * @var string
     * @ORM\Column(name="server_name", type="string", length=255, nullable=false)
     */
    private $server = 'lend-engine-eu';

    /**
     * @var string
     * @ORM\Column(name="time_zone", type="string", length=255, nullable=false)
     */
    private $timeZone = "Europe/London";

    /**
     * @var string
     * @ORM\Column(name="schema_version", type="string", length=255, nullable=true)
     */
    private $schemaVersion;

    /**
     * @ORM\OneToMany(targetEntity="TenantNote", mappedBy="tenant", cascade={"remove"})
     */
    private $notes;

    /**
     * @var \DateTime
     * @ORM\Column(name="migration_started", type="datetime", nullable=false)
     */
    private $migrationStarted;

    /**
     * @var \DateTime
     * @ORM\Column(name="migration_completed", type="datetime", nullable=false)
     */
    private $migrationCompleted;

    /**
     * @var
     */
    private $age;

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
     * Set stub
     *
     * @param string $stub
     *
     * @return Tenant
     */
    public function setStub($stub)
    {
        $this->stub = $stub;

        return $this;
    }

    /**
     * Get stub
     *
     * @return string
     */
    public function getStub()
    {
        return $this->stub;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Tenant
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $industry
     * @return $this
     */
    public function setIndustry($industry)
    {
        $this->industry = $industry;

        return $this;
    }

    /**
     * @return string
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * Set dbSchema
     *
     * @param string $dbSchema
     *
     * @return Tenant
     */
    public function setDbSchema($dbSchema)
    {
        $this->dbSchema = $dbSchema;

        return $this;
    }

    /**
     * Get dbSchema
     *
     * @return string
     */
    public function getDbSchema()
    {
        return $this->dbSchema;
    }

    /**
     * @param string $domain
     *
     * @return Tenant
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @param bool $withHttp
     * @return string
     */
    public function getDomain($withHttp = false)
    {
        $stub = '';
        if (getenv('APP_ENV') == 'dev') {
            if ($withHttp) {
                $stub = 'http://';
            }
            return $stub.$this->stub.'.localhost:8000';
        }
        if ($this->domain) {
            if ($withHttp) {
                $stub = 'https://';
            }
            return $stub.$this->domain;
        } else {
            if ($withHttp) {
                $stub = 'http://';
            }
            return $stub.$this->stub.'.lend-engine-app.com';
        }
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Tenant
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
     * Set lastAccessAt
     *
     * @param \DateTime $lastAccessAt
     *
     * @return Tenant
     */
    public function setLastAccessAt($lastAccessAt)
    {
        $this->lastAccessAt = $lastAccessAt;

        return $this;
    }

    /**
     * Get lastAccessAt
     *
     * @return \DateTime
     */
    public function getLastAccessAt()
    {
        return $this->lastAccessAt;
    }

    /**
     * Set ownerName
     *
     * @param string $ownerName
     *
     * @return Tenant
     */
    public function setOwnerName($ownerName)
    {
        $this->ownerName = $ownerName;

        return $this;
    }

    /**
     * Get ownerName
     *
     * @return string
     */
    public function getOwnerName()
    {
        return $this->ownerName;
    }

    /**
     * Set ownerEmail
     *
     * @param string $ownerEmail
     *
     * @return Tenant
     */
    public function setOwnerEmail($ownerEmail)
    {
        $this->ownerEmail = $ownerEmail;

        return $this;
    }

    /**
     * Get ownerEmail
     *
     * @return string
     */
    public function getOwnerEmail()
    {
        return $this->ownerEmail;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setOrgEmail($email)
    {
        $this->orgEmail = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrgEmail()
    {
        return $this->orgEmail;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Tenant
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
     * @param string $plan
     *
     * @return Tenant
     */
    public function setPlan($plan)
    {
        $this->plan = $plan;

        return $this;
    }

    /**
     * @return string
     * Used to map various pricing plans onto the same feature plan
     * !!! ALSO REPLICATED IN CustomConnectionFactory.php !!!
     */
    public function getPlan()
    {
        $plan = $this->plan;

        // start replicated code
        switch ($plan) {
            case 'free':
                $plan = 'free';
                break;
            case 'standard':
            case 'starter':
            case 'plan_Cv8Lg7fyOJSB0z': // standard monthly 5.00
            case 'plan_Cv6TbQ0PPSnhyL': // test plan
            case 'plan_Cv6rBge0LPVNin': // test plan
            case 'single':
                $plan = 'starter';
                break;
            case 'premium':
            case 'plus':
            case 'multiple':
                $plan = 'plus';
                break;
            case 'business':
            case 'plan_F4HR4VG76biNcB': // test
            case 'plan_F4HgQehPQ2nOlN': // prod
                $plan = 'business';
                break;
        }
        // end replicated code

        $this->plan = $plan;
        return $this->plan;
    }

    /**
     * @param string $subscriptionId
     *
     * @return Tenant
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * @param string $dt
     *
     * @return Tenant
     */
    public function setTrialExpiresAt($dt)
    {
        $this->trialExpiresAt = $dt;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrialExpiresAt()
    {
        return $this->trialExpiresAt;
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
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param $serverName
     * @return $this
     */
    public function setServer($serverName)
    {
        $this->server = $serverName;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimeZone()
    {
        return $this->timeZone;
    }

    /**
     * @param $timeZone
     * @return $this
     */
    public function setTimeZone($timeZone)
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    /**
     * @return string
     */
    public function getSchemaVersion()
    {
        return $this->schemaVersion;
    }

    /**
     * @param $version
     * @return $this
     */
    public function setSchemaVersion($version)
    {
        $this->schemaVersion = $version;

        return $this;
    }

    /**
     * Set migrationStarted
     *
     * @param \DateTime $migrationStarted
     *
     * @return $this
     */
    public function setMigrationStarted($migrationStarted)
    {
        $this->migrationStarted = $migrationStarted;

        return $this;
    }

    /**
     * Get migrationStarted
     *
     * @return \DateTime
     */
    public function getMigrationStarted()
    {
        return $this->migrationStarted;
    }

    /**
     * Set migrationCompleted
     *
     * @param \DateTime $migrationCompleted
     *
     * @return $this
     */
    public function setMigrationCompleted($migrationCompleted)
    {
        $this->migrationCompleted = $migrationCompleted;

        return $this;
    }

    /**
     * Get migrationCompleted
     *
     * @return \DateTime
     */
    public function getMigrationCompleted()
    {
        return $this->migrationCompleted;
    }

    /**
     * Check that the migration is started but not completed yet
     *
     * @return bool
     */
    public function isMigrationInProgress()
    {
        if ($this->migrationStarted && !$this->migrationCompleted) {
            return true;
        }

        return false;
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
     * @return string
     */
    public function getAge()
    {
        $now = new \DateTime();
        $age = $this->createdAt->diff($now);
        return $age->format('%a');
    }

}
