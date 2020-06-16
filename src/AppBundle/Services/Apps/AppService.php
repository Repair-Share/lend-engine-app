<?php

namespace AppBundle\Services\Apps;
use AppBundle\Entity\App;
use AppBundle\Entity\AppSetting;
use AppBundle\Services\SettingsService;
use Doctrine\ORM\EntityManager;

/**
 * Class AppService
 * @package AppBundle\Services\Apps
 */
class AppService
{
    /** @var EntityManager  */
    private $em;

    /** @var SettingsService */
    private $settings;

    /** @var \AppBundle\Repository\AppRepository */
    private $repo;

    private $installedAppsByCode = [];

    /**
     * AppService constructor.
     * @param EntityManager $em
     * @param SettingsService $settings
     */
    public function __construct(EntityManager $em, SettingsService $settings)
    {
        $this->em = $em;
        $this->settings = $settings;


        $this->repo = $this->em->getRepository('AppBundle:App');

        // Initialise
        $this->getInstalled();
    }

    public function getInstalled()
    {
        $apps = $this->repo->findAll();
        /** @var \AppBundle\Entity\App $app */
        foreach ($apps AS $app) {
            $this->installedAppsByCode[$app->getCode()] = $app;
        }
        return $apps;
    }

    public function getStatus($code)
    {
        if (!isset($this->installedAppsByCode[$code])) {
            return '';
        }

        /** @var \AppBundle\Entity\App $app */
        $app = $this->installedAppsByCode[$code];

        if ($app->getUnInstalledAt()) {
            return 'inactive';
        }

        return 'active';
    }

    public function install($code)
    {
        if (!$app = $this->repo->findOneBy(['code' => $code])) {
            // not yet installed
            $app = new App();
            $app->setCode($code);
            $app->setInstalledAt(new \DateTime());
        } else {
            // reinstalling
            $app->setUnInstalledAt(null);
            $app->setIsActive(true);
        }

        $this->repo->save($app);

        return true;
    }

    public function deactivate($code)
    {
        /** @var $app \AppBundle\Entity\App */
        if (!$app = $this->repo->findOneBy(['code' => $code])) {
            return false;
        }
        $app->setUnInstalledAt(new \DateTime());
        $app->setIsActive(false);
        $this->repo->save($app);

        return true;
    }

    /**
     * @param $code
     * @return array|null
     * @throws \Exception
     */
    public function get($code)
    {
        /** @var \AppBundle\Entity\App $app */
        if ($app = $this->repo->findOneBy(['code' => $code])) {

            switch ($code) {
                case "mailchimp":
                    $appData = $this->getMailChimp();
                    break;
                case "stripe":
                    $appData = $this->getStripe();
                    break;
                default:
                    throw new \Exception("App {$code} not found");
            }

            // Load the setup values from the DB
            /** @var \AppBundle\Entity\AppSetting $setting */
            foreach ($app->getSettings() AS $setting) {
                $appData['settings'][$setting->getSetupKey()]['data'] = $setting->getSetupValue();
            }

            return $appData;
        }

        return null;
    }

    /**
     * @param $code
     * @param $key
     * @param $value
     * @return AppSetting|bool|null|object
     */
    public function saveSetting($code, $key, $value)
    {
        /** @var \AppBundle\Repository\AppSettingRepository $settingRepo */
        $settingRepo = $this->em->getRepository('AppBundle:AppSetting');

        if (!$app = $this->repo->findOneBy(['code' => $code])) {
            return false;
        }

        if (!$setting = $settingRepo->findOneBy(['setupKey' => $key, 'app' => $app])) {
            $setting = new AppSetting();
            $setting->setApp($app);
            $setting->setSetupKey($key);
        }

        $setting->setSetupValue($value);
        $settingRepo->save($setting);

        return $setting;
    }

    public function getAll()
    {
        $apps = [];
        $apps['mailchimp'] = $this->getMailChimp();
//        $apps['stripe'] = $this->getStripe();

        return $apps;
    }

    private function getMailChimp()
    {
        return [
            'code' => 'mailchimp',
            'status' => $this->getStatus('mailchimp'),
            'type' => 'crm_sync',
            'name' => "Mailchimp",
            'description' => "Sync Lend Engine contacts with Mailchimp",
            'settings' => [
                'api_key' => [
                    'title' => 'API key',
                    'type' => 'text',
                    'data' => '',
                    'help' => 'In Mailchimp: Account Settings > Extra > API keys'
                ],
                'list_id' => [
                    'title' => 'Audience ID',
                    'type' => 'text',
                    'data' => '',
                    'help' => 'In Mailchimp Audience page: Manage Audience > Settings > Unique ID (at the bottom)'
                ],
                'opt_in' => [
                    'title' => 'Send opt-in email to new subscribers',
                    'type' => 'toggle',
                    'data' => '',
                    'help' => 'Tell Mailchimp to send an opt-in email when adding a new contact to your audience.'
                ],
            ]
        ];
    }

    private function getStripe()
    {
        return [
            'code' => 'stripe',
            'status' => $this->getStatus('stripe'),
            'type' => 'payment',
            'name' => "Stripe.com card payments",
            'description' => "Take card payments using Stripe.com",
            'settings' => [
                'stripe_access_token' => [
                    'title' => 'Acccess token',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'stripe_fee' => [
                    'title' => 'Payment fee',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'stripe_minimum_payment' => [
                    'title' => 'Minimum payment amount',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'stripe_payment_method' => [
                    'title' => 'Payment method',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'stripe_publishable_key' => [
                    'title' => 'Publishable key',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'stripe_refresh_token' => [
                    'title' => 'Refresh token',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'stripe_use_saved_cards' => [
                    'title' => 'Use saved cards',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'stripe_user_id' => [
                    'title' => 'Stripe user ID',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
            ]
        ];
    }

    private function getTwilio()
    {
        return [
            'code' => 'twilio',
            'status' => $this->getStatus('twilio'),
            'type' => 'sms',
            'name' => "Twilio SMS",
            'description' => "Send SMS using Twilio",
            'settings' => [
                'api_key' => [
                    'title' => 'API key',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'list_id' => [
                    'title' => 'API token',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ]
            ]
        ];
    }
}