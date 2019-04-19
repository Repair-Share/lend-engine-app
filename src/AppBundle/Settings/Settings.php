<?php

/**
 * Class used to fetch all the settings from the DB
 *
 */

namespace AppBundle\Settings;

use AppBundle\Entity\Tenant;
use AppBundle\Entity\TenantSite;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Session\Session;

class Settings
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /** @var \AppBundle\Entity\Tenant */
    private $tenant;

    public $settings = array();

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param Tenant $tenant
     * @param EntityManager $em
     */
    public function setTenant(Tenant $tenant, EntityManager $em = null)
    {
        $this->tenant = $tenant;
        if ($em) {
            $this->em = $em;
        }
    }

    /**
     * @param $key
     * @return string
     */
    public function getSettingValue($key)
    {
        // If we have an account code we use the cache, else we have to go to DB each time
        // eg when running loan reminders from console for multiple tenants
        $db = $this->em->getConnection()->getDatabase();

        if ($db && $key && isset($this->settings[$db][$key])) {
            return $this->settings[$db][$key];
        } else {
            /** @var $repo \AppBundle\Repository\SettingRepository */
            $repo =  $this->em->getRepository('AppBundle:Setting');

            // Init with empty values
            $allSettings = $repo->getSettingsKeys();
            foreach ($allSettings AS $k) {
                $this->settings[$db][$k] = '';
            }

            $settings = $repo->findAll();
            foreach ($settings AS $setting) {
                /** @var $setting \AppBundle\Entity\Setting */
                if ($key == 'org_languages' && !$setting->getSetupValue()) {
                    // Languages has not yet been set, so use the default locale
                    $locale = $repo->findOneBy(['setupKey' => 'org_locale']);
                    $setting->setSetupValue($locale);
                }
                $k = $setting->getSetupKey();
                $this->settings[$db][$k] = $setting->getSetupValue();
            }

            // Set bull value for remainder of settings
            if (!isset($this->settings[$db][$key])) {
                $this->settings[$db][$key] = null;
            }

            // Set predefined values for new (as yet unset) settings
            // These will be shown in the UI, used in the app, and saved when settings are next saved
            // THIS CODE IS IN SettingRepository AND Setting service
            $newSettings = [
                'automate_email_loan_reminder' => 1,
                'automate_email_reservation_reminder' => 1,
                'automate_email_membership' => 1,
                'org_locale' => 'en'
            ];
            foreach ($newSettings AS $k => $v) {
                if ($this->settings[$db][$k] == null) {
                    $this->settings[$db][$k] = $v;
                }
            }

            return $this->settings[$db][$key];

        }
    }

    /**
     * Push a few setting values up into Core and add/update sites
     * @param $accountCode
     * @return bool
     */
    public function updateCore($accountCode)
    {
        // We also need to get the tenant so we can update the _core database for the library directory
        /** @var $tenantRepo \AppBundle\Repository\TenantRepository */
        $tenantRepo = $this->em->getRepository('AppBundle:Tenant');

        /** @var $tenantSiteRepo \AppBundle\Repository\TenantSiteRepository */
        $tenantSiteRepo = $this->em->getRepository('AppBundle:TenantSite');

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $tenantRepo->findOneBy(['stub' => $accountCode]);

        /** @var \AppBundle\Entity\Site $site */
        $sites = $this->em->getRepository('AppBundle:Site')->findAll();

        foreach ($sites AS $site) {

            if ($site->getAddress() && $site->getPostCode() && $site->getCountry()) {

                // Update the site in the _core library
                $uniqueSiteId = $tenant->getId().'-'.$site->getId();
                if (!$tenantSite = $tenantSiteRepo->findOneBy(['uniqueId' => $uniqueSiteId])) {
                    $tenantSite = new TenantSite();
                    $tenantSite->setTenant($tenant);
                    $tenantSite->setUniqueId($uniqueSiteId);
                }

                // Set the tenant's default site as the first site saved, or preferably site ID 1
                if ($site->getId() == 1 || !$tenant->getSite()) {
                    $tenant->setSite($tenantSite);
                }

                if ($site->getIsListed()) {
                    $tenantSite->setStatus(TenantSite::STATUS_ACTIVE);
                } else {
                    $tenantSite->setStatus(TenantSite::STATUS_HIDDEN);
                }

                $tenantSite->setAddress($site->getAddress());
                $tenantSite->setName($site->getName());
                $tenantSite->setPostCode($site->getPostCode());
                $tenantSite->setCountry($site->getCountry());
                $this->em->persist($tenantSite);

            }

        }

        $tenant->setTimeZone($this->getSettingValue('org_timezone'));
        $tenant->setIndustry($this->getSettingValue('industry'));
        $tenant->setName($this->getSettingValue('org_name'));
        $tenant->setOrgEmail($this->getSettingValue('org_email'));

        $this->em->persist($tenant);
        $this->em->flush();

        return true;
    }
}