<?php

/**
 * Class used to fetch all the settings from the DB
 *
 */

namespace AppBundle\Services;

use AppBundle\Entity\Setting;
use AppBundle\Entity\Tenant;
use AppBundle\Entity\TenantSite;
use Doctrine\ORM\EntityManager;

class SettingsService
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /** @var  */
    private $db;

    /** @var \AppBundle\Entity\Tenant */
    private $tenant;

    /** @var  */
    private $repo;

    public $settings = array();

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->db = $this->em->getConnection()->getDatabase();

        $tenant = $this->em->getRepository('AppBundle:Tenant')->findOneBy(['dbSchema' => $this->db]);
        if (!$tenant) {
            throw new \Exception("No tenant found when getting settings");
        }
        $this->setTenant($tenant);
    }

    /**
     * Override the active tenant (used in a scheduled loop eg reminders)
     * @param Tenant $tenant
     * @param EntityManager $em
     */
    public function setTenant(Tenant $tenant, EntityManager $em = null)
    {
        $this->tenant = $tenant;
        if ($em) {
            $this->em = $em;
            $this->db = $this->em->getConnection()->getDatabase();
        }
    }

    /**
     * @return Tenant
     */
    public function getTenant()
    {
        return $this->tenant;
    }

    /**
     * Used in settings controllers
     */
    public function getAllSettingValues()
    {
        $this->getAllSettings();
        return $this->settings[$this->db];
    }

    /**
     * Get all settings into the class and define where not yet set
     */
    public function getAllSettings()
    {
        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');

        // Init with empty values
        $allSettings = $this->getSettingsKeys();
        foreach ($allSettings AS $k) {
            $this->settings[$this->db][$k] = null;
        }

        $settings = $repo->findAll();
        foreach ($settings AS $setting) {
            /** @var $setting \AppBundle\Entity\Setting */
            if ($setting->getSetupKey() == 'org_languages' && !$setting->getSetupValue()) {
                // Languages has not yet been set, so use the default locale
                $locale = $repo->findOneBy(['setupKey' => 'org_locale']);
                $setting->setSetupValue($locale);
            }
            $k = $setting->getSetupKey();
            $this->settings[$this->db][$k] = $setting->getSetupValue();
        }

        // Set predefined values for new (as yet unset) settings
        // These will be shown in the UI, used in the app, and saved when settings are next saved
        $newSettings = [
            'automate_email_loan_reminder' => 1,
            'automate_email_reservation_reminder' => 1,
            'automate_email_membership' => 1,
            'org_locale' => 'en',
            'label_type' => '11355'
        ];
        foreach ($newSettings AS $k => $v) {
            if ($this->settings[$this->db][$k] == null) {
                $this->settings[$this->db][$k] = $v;
            }
        }
    }

    /**
     * Pull all settings from the DB
     * @return array
     */
    public function getAllSettingsOld()
    {
        $settingsArray = array();
        /** @var \AppBundle\Repository\SettingRepository repo */
        $this->repo = $this->em->getRepository('AppBundle:Setting');

        // initialise the settings array in case the DB has no values
        $keys = $this->getSettingsKeys();
        foreach ($keys AS $key) {
            $settingsArray[$key] = '';
        }

        $settings = $this->repo->findAll();

        foreach ($settings AS $setting) {
            /** @var $setting \AppBundle\Entity\Setting */
            $setupKey   = $setting->getSetupKey();
            $setupValue = $setting->getSetupValue();
            $settingsArray[$setupKey] = $setupValue;
        }

        // Set predefined values for new (as yet unset) settings
        // These will be shown in the UI, used in the app, and saved when settings are next saved
        // THIS CODE IS IN SettingRepository AND Setting service
        $newSettings = [
            'automate_email_loan_reminder' => 1,
            'automate_email_reservation_reminder' => 1,
            'automate_email_membership' => 1,
            'org_locale' => 'en',
            'label_type' => '11355'
        ];
        foreach ($newSettings AS $k => $v) {
            if ($settingsArray[$k] == null) {
                $settingsArray[$k] = $v;
            }
        }

        return $settingsArray;
    }

    /**
     * @param $key
     * @return string
     */
    public function getSettingValue($key)
    {
        // If we have an account code we use the cache, else we have to go to DB each time
        // eg when running loan reminders from console for multiple tenants
        if ($this->db && $key && isset($this->settings[$this->db][$key])) {
            return $this->settings[$this->db][$key];
        } else {
            // get them all into $this->settings
            $this->getAllSettings();
            return $this->settings[$this->db][$key];
        }
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    public function setSettingValue($key, $value)
    {
        if (!$this->isValidSettingsKey($key)) {
            return false;
        }

        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');

        /** @var $setting \AppBundle\Entity\Setting */
        if (!$setting = $repo->findOneBy(['setupKey' => $key])) {
            $setting = new Setting();
            $setting->setSetupKey($key);
        }

        $setting->setSetupValue($value);
        $this->em->persist($setting);
        $this->em->flush();

        return true;
    }

    /**
     * @param $key
     * @return bool
     */
    public function isValidSettingsKey($key)
    {
        $keys = $this->getSettingsKeys();
        if (!in_array($key, $keys)) {
            return false;
        }
        return true;
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

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $tenantRepo->findOneBy(['stub' => $accountCode]);

        $tenant->setTimeZone($this->getSettingValue('org_timezone'));
        $tenant->setIndustry($this->getSettingValue('industry'));
        $tenant->setName($this->getSettingValue('org_name'));
        $tenant->setOrgEmail($this->getSettingValue('org_email'));

        $this->em->persist($tenant);
        $this->em->flush();

        return true;
    }

    /**
     * @return array
     */
    private function getSettingsKeys()
    {
        $validKeys = array(
            'org_timezone',
            'org_currency',
            'default_checkin_location',
            'default_loan_fee',
            'default_loan_days',
            'min_loan_days',
            'max_loan_days',
            'daily_overdue_fee',
            'org_name',
            'org_address',
            'org_country',
            'org_postcode',
            'org_email',
            'org_email_footer',
            'org_logo_url',
            'org_locale',
            'org_languages',
            'industry',
            'hide_branding',

            // To allow whitelabel users to send from their own email address
            'postmark_api_key',
            'from_email',

            //labels
            'use_labels',
            'label_type',

            // Reminders
            'automate_email_loan_reminder',
            'automate_email_reservation_reminder',
            'automate_email_membership',
            'automate_email_overdue_days',

            // Setup values
            'multi_site',
            'setup_opening_hours',

            // Stripe card details
            'stripe_access_token',
            'stripe_refresh_token',
            'stripe_user_id',
            'stripe_publishable_key',
            'stripe_payment_method',
            'stripe_minimum_payment',
            'stripe_fee',
            'stripe_use_saved_cards',

            'site_is_private',
            'site_welcome',
            'site_welcome_user',
            'site_css',
            'site_js',
            'site_google_login',
            'site_facebook_login',
            'site_twitter_login',
            'site_allow_registration',
            'site_description',
            'site_font_name',
            'site_theme_name',
            'logo_image_name',
            'group_similar_items',

            'registration_terms_uri',
            'auto_sku_stub',

            'email_membership_expiry_head',
            'email_membership_expiry_foot',

            'email_loan_reminder_head',
            'email_loan_reminder_foot',

            'email_loan_overdue_head',
            'email_loan_overdue_foot',

            'email_reservation_reminder_head',
            'email_reservation_reminder_foot',

            'email_loan_confirmation_subject',
            'email_loan_confirmation_head',
            'email_loan_confirmation_foot',

            'email_reserve_confirmation_subject',
            'email_reserve_confirmation_head',
            'email_reserve_confirmation_foot',

            'email_loan_extension_subject',
            'email_loan_extension_head',
            'email_loan_extension_foot',

            'email_welcome_subject',
            'email_welcome_head',
            'email_welcome_foot',

            'loan_terms', // terms and conditions

            'mailchimp_api_key',
            'mailchimp_default_list_id',
            'mailchimp_double_optin',
            'enable_waiting_list',

            'reservation_fee',
            'charge_daily_fee',
            'fixed_fee_pricing',

            'open_days', // legacy, now done per site
        );

        return $validKeys;
    }

}