<?php

namespace AppBundle\Services\Apps;

use AppBundle\Services\SettingsService;
use Twilio\Rest\Client;

class TwilioService
{
    /** @var \AppBundle\Services\Apps\AppService */
    private $appService;

    /** @var SettingsService */
    private $settings;

    private $accountId;
    private $authToken;
    private $number;

    public function __construct(AppService $appService, SettingsService $settings)
    {
        $this->appService = $appService;
        $this->settings   = $settings;

        $this->app = $this->appService->get('twilio');

        if (isset($this->app['settings']['account_id']['data'])) {
            $this->accountId = $this->app['settings']['account_id']['data'];
        }
        if (isset($this->app['settings']['auth_token']['data'])) {
            $this->authToken = $this->app['settings']['auth_token']['data'];
        }
        if (isset($this->app['settings']['number']['data'])) {
            $this->number = $this->app['settings']['number']['data'];
        }
    }

    /**
     * @param $number
     * @param $content
     * @return bool
     */
    public function sendSms($number, $content)
    {
        if ($this->app['status'] != 'active') {
            return false;
        }

        if (!$this->installedAndConfigured()) {
            return false;
        }

        if (!$formattedNumber = $this->preparePhoneForSms($number)) {
            return false;
        }

        $client = new Client($this->accountId, $this->authToken);
        $client->messages->create(
            $formattedNumber,
            [
                'from' => $this->number,
                'body' => $content
            ]
        );

        return true;
    }

    /**
     * @param $number
     * @return bool|string
     */
    private function preparePhoneForSms($number)
    {
        if (!$number) {
            return false;
        }

        if (strlen($number) < 11) {
            return false;
        }

        $phoneNumber = null;
        $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();

        if (!$countryCode = $this->settings->getSettingValue('org_country')) {
            $countryCode = 'GB';
        }

        try {
            $phoneNumber = $phoneUtil->parse($number, $countryCode);
            return $phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::E164);
        } catch (\libphonenumber\NumberParseException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

    }

    /**
     * @return bool
     */
    private function installedAndConfigured()
    {
        if (!$this->app) {
            return false;
        }

        if (!$this->authToken || !$this->accountId || !$this->number) {
            return false;
        }
        return true;
    }
}