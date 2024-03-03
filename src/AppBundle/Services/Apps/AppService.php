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
        if (count($this->installedAppsByCode) == 0) {
            $this->getInstalled();
        }

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
                case "twilio":
                    $appData = $this->getTwilio();
                    break;
                case "recaptcha":
                    $appData = $this->getRecaptcha();
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
        $apps['twilio'] = $this->getTwilio();
        $apps['recaptcha'] = $this->getRecaptcha();
//        $apps['stripe'] = $this->getStripe();

        return $apps;
    }

    private function getMailChimp()
    {
        $description = <<<EOT
Send your Lend Engine contacts over to Mailchimp for bulk emailing. New contacts added via admin or self-registration are sent to Mailchimp, 
as well as existing contacts updated by admin in Lend Engine.
EOT;

        return [
            'code' => 'mailchimp',
            'status' => $this->getStatus('mailchimp'),
            'type' => 'crm_sync',
            'name' => "Mailchimp",
            'description' => $description,
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
        $description = <<<EOT
Send automated SMS reminders and notifications using Twilio.
Choose which notifications are sent by SMS (email automation is also required), and the content of the message. 
Payment for SMS is made directly to Twilio via your Twilio account.
EOT;

        return [
            'code' => 'twilio',
            'status' => $this->getStatus('twilio'),
            'type' => 'sms',
            'name' => "Twilio SMS",
            'description' => $description,
            'settings' => [
                'account_id' => [
                    'title' => 'Account ID',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'auth_token' => [
                    'title' => 'Auth token',
                    'type' => 'text',
                    'data' => '',
                    'help' => ''
                ],
                'number' => [
                    'title' => 'Outgoing number',
                    'type' => 'text',
                    'data' => '',
                    'help' => 'As set up in your Twilio account, e.g. "+447447325333"'
                ],
                'loan_reminder_text' => [
                    'title' => 'Loan return reminder text',
                    'type' => 'text',
                    'data' => '',
                    'help' => 'Add text to send a reminder message before the loan is due back.<br>Max 140 characters.'
                ],
                'loan_reminder_hours' => [
                    'title' => 'Loan return reminder hours',
                    'type' => 'text',
                    'data' => '',
                    'help' => 'How many hours before the loan is due back.'
                ],
                'reservation_collect_text' => [
                    'title' => 'Reservation collection reminder text',
                    'type' => 'text',
                    'data' => '',
                    'help' => 'Add text to send a reminder message before a reservation is due to be collected.<br>Max 140 characters.'
                ],
                'reservation_collect_hours' => [
                    'title' => 'Reservation collection reminder hours',
                    'type' => 'text',
                    'data' => '',
                    'help' => 'How many hours before the reservation is due to be collected.'
                ],
            ]
        ];
    }



    private function getRecaptcha()
    {
        $description = <<<EOT
reCAPTCHA protects your website from fraud and abuse without creating friction.
EOT;

        return [
            'code' => 'recaptcha',
            'status' => $this->getStatus('recaptcha'),
            'type' => 'recaptcha',
            'name' => "reCAPTCHA",
            'description' => $description,
            'settings' => [
                'site_key' => [
                    'title' => 'Site key',
                    'type' => 'text',
                    'data' => '',
                    'help' => 'In Recaptcha page: copy the site key when registering a new site. It appears only once on Google\'s page!'
                ],
                'secret_key' => [
                    'title' => 'Secret key',
                    'type' => 'text',
                    'data' => '',
                    'help' => 'In Recaptcha page: copy the secret key when registering a new site. It appears only once on Google\'s page!'
                ]
            ]
        ];
    }
}