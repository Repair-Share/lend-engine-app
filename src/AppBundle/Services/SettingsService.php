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
use Symfony\Component\Cache\Adapter\FilesystemAdapter;


class SettingsService
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    /** @var  */
    private $db;

    /** @var \AppBundle\Entity\Tenant */
    private $tenant;

    public $settings = array();

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->db = $this->em->getConnection()->getDatabase();

        $tenant = $this->loadWithCache(false);

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
    public function getTenant($returnObject = true)
    {
        if ($returnObject) {
            $this->tenant = $this->loadWithCache($returnObject);
        }
        return $this->tenant;
    }

    /**
     * Used in settings controllers
     */
    public function getAllSettingValues()
    {
        if (!isset($this->settings[$this->db])) {
            $this->getAllSettings();
        }
        return $this->settings[$this->db];
    }

    /**
     * @param bool $useCache
     * @return bool
     * Get all settings into the class and define where not yet set
     */
    public function getAllSettings($useCache = true)
    {
        /** @var $repo \AppBundle\Repository\SettingRepository */
        $repo =  $this->em->getRepository('AppBundle:Setting');

        // We've already populated the settings for this account, send them back
        if (isset($this->settings[$this->db]) && count($this->settings[$this->db]) > 0 && $useCache == true) {
            return true;
        }

        // Initialise with empty values
        $allSettings = $this->getSettingsKeys();
        foreach ($allSettings AS $k) {
            $this->settings[$this->db][$k] = null;
        }

        $settings = $repo->findAll();

        foreach ($settings AS $setting) {
            /** @var $setting \AppBundle\Entity\Setting */

            if ($setting->getSetupKey() == 'org_languages' && !$setting->getSetupValue()) {
                // Languages has not yet been set, so use the default locale for the language
                $locale = $repo->findOneBy(['setupKey' => 'org_locale']);
                $setting->setSetupValue($locale);
            }

            $k = $setting->getSetupKey();
            $setupValue = $setting->getSetupValue();

            $this->settings[$this->db][$k] = $setupValue;
        }

        // For new as-yet-unset values
        $defaultSettings = [
            'group_similar_items' => 1,
            'basket_quick_add' => 0,
            'search_terms' => '1',
            'org_locale' => 'en',
            'label_type' => '11355',
            'org_timezone' => 'Europe/London',
            'event_time_step' => '30'
        ];

        foreach ($this->settings[$this->db] AS $k => $v) {
            if ($v == null && isset($defaultSettings[$k])) {
                $this->settings[$this->db][$k] = $defaultSettings[$k];
            }
        }

        return true;
    }

    /**
     * @param $key
     * @return string
     */
    public function getSettingValue($key)
    {
        // Feature toggles
        if ($key == 'ft_events') {
            return 1; // on for everyone from June 27th 2019
        }

        // If we have an account code we use the cache, else we have to go to DB each time
        // eg when running loan reminders from console for multiple tenants
        if ($this->db && $key && isset($this->settings[$this->db][$key])) {
            return $this->settings[$this->db][$key];
        } else {
            // get them all into $this->settings
            $this->getAllSettings();
            if (!isset($this->settings[$this->db][$key])) {
                // For legacy settings which arein DB but no longer in the app
                return null;
            }
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
            'basket_quick_add',
            'min_loan_days',
            'max_loan_days',
            'daily_overdue_fee',
            'org_name',
            'org_address',
            'org_country',
            'org_postcode',
            'org_email',
            'org_cc_email',
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
            'automate_email_overdue_until_loan_returned',

            // Feature toggles
            'ft_events',
            'event_time_step',

            // Setup values
            'multi_site',
            'setup_opening_hours',
            'show_events_online',
            'self_checkout',
            'self_extend',
            'hide_ga',

            // Web page header text
            'page_event_header',
            'page_registration_header',

            // Stripe card details
            'stripe_access_token',
            'stripe_refresh_token',
            'stripe_user_id',
            'stripe_publishable_key',
            'stripe_payment_method',
            'stripe_minimum_payment',
            'stripe_fee',
            'stripe_use_saved_cards',

            // Postal loans
            'postal_loans',
            'postal_shipping_item',
            'postal_item_fee',
            'postal_loan_fee',

            // Member site
            'site_domain',
            'site_domain_provider',
            'site_domain_req_name',
            'site_domain_req_email',
            'site_domain_req_time',
            'site_is_private',
            'site_welcome',
            'site_welcome_user',
            'site_css',
            'print_css',
            'site_js',
            'site_google_login',
            'site_facebook_login',
            'site_twitter_login',
            'site_allow_registration',
            'registration_require_email_confirmation',
            'site_description',
            'site_font_name',
            'site_theme_name',
            'logo_image_name',
            'group_similar_items',
            'search_terms',

            'registration_terms_uri',
            'auto_sku_stub',

            'email_membership_expiry_head',
            'email_membership_expiry_foot',

            'email_loan_reminder_head',
            'email_loan_reminder_foot',

            'email_loan_overdue_head',
            'email_loan_overdue_foot',

            'email_reservation_reminder_subject',
            'email_reservation_reminder_head',
            'email_reservation_reminder_foot',

            'email_booking_confirmation_subject',
            'email_booking_confirmation_head',
            'email_booking_confirmation_foot',

            'email_loan_confirmation_subject',
            'email_loan_confirmation_head',
            'email_loan_confirmation_foot',

            'email_loan_checkin_subject',
            'email_loan_checkin_head',
            'email_loan_checkin_foot',

            'email_cc_admin',

            'email_reserve_confirmation_subject',
            'email_reserve_confirmation_head',
            'email_reserve_confirmation_foot',

            'email_loan_extension_subject',
            'email_loan_extension_head',
            'email_loan_extension_foot',

            'email_welcome_subject',
            'email_welcome_head',
            'email_welcome_foot',

            'email_donor_notification_subject',
            'email_donor_notification_head',
            'email_donor_notification_foot',

            'loan_terms', // terms and conditions

            'enable_waiting_list',
            'google_tracking_id',

            'pay_membership_at_pickup',

            'reservation_fee',
            'reservation_buffer',
            'reservation_buffer_override',
            'max_reservations',
            'charge_daily_fee',
            'fixed_fee_pricing',
            'forward_picking',

            'open_days', // legacy, now done per site

            'max_items', // billing override
        );

        return $validKeys;
    }

    public function loadWithCache($returnObject = true, $refreshCache = false)
    {
        $tenant = null;

        $cachePool = new FilesystemAdapter();

        $cacheKey = 'tenant_' . $this->db;

        if ($returnObject) {
            $refreshCache = true;
        }

        if ($refreshCache) {
            $cachePool->deleteItem($cacheKey);
        }

        $cache = $cachePool->getItem($cacheKey);

        if (!$cache->isHit()) {
            $tenant = $this->em->getRepository('AppBundle:Tenant')->findOneBy(['dbSchema' => $this->db]);
            $cache->set(serialize($tenant));
            $cache->expiresAfter(3600); // 1 hour
            $cachePool->save($cache);
        }

        if ($returnObject) {
            return $tenant;
        }

        if ($cachePool->hasItem($cacheKey)) {
            $cacheObject = $cachePool->getItem($cacheKey);
            $tenant      = unserialize($cacheObject->get());
        }

        return $tenant;
    }

}
