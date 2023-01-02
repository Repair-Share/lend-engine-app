<?php

namespace AppBundle\Services\Debug;

use AppBundle\Services\SettingsService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\PaymentIntent;

class DebugService
{
    const STRIPE = 'stripe';

    /** @var EntityManager */
    private $em;

    /** @var SettingsService */
    private $settingsService;

    private $debug = false;

    /**
     * DebugService constructor.
     * @param  EntityManagerInterface  $em
     * @param  SettingsService  $settings
     */
    public function __construct(EntityManagerInterface $em, SettingsService $settings)
    {
        $this->em              = $em;
        $this->settingsService = $settings;

        if ($this->settingsService->getSettingValue('stripe_debug') === '1') {
            $this->debug = true;
        }
    }

    public function isDebugOn()
    {
        return $this->debug;
    }

    public function getLogDir()
    {
        return dirname(__DIR__, 4) . '/var/logs';
    }

    public function getLogFile($type)
    {
        $dbSchema = $this->settingsService->getTenant()->getDbSchema();
        return $this->getLogDir() . '/debug-' . $type . '-' . $dbSchema . '.log';
    }

    /**
     * Get the masked value
     *
     * @param $value
     * @param  int  $first  Leaves the first x character unmasked
     * @param  int  $last  Leaves the last x character unmasked
     *
     * @return string
     */
    public function getMaskedValue($value, $first = 0, $last = 0)
    {
        return substr($value, 0, $first) . '...' . substr($value, strlen($value) - $last);
    }

    /**
     * @param $params
     * @param $key
     * @param  int  $first  Leaves the first x character unmasked
     * @param  int  $last  Leaves the last x character unmasked
     * @return false|void
     */
    public function maskSensitiveInfo(&$params, $key, $first = 0, $last = 0)
    {
        /*if ($params instanceof PaymentIntent) {

            $value = $params->{$key};

            $maskedValue = substr($value, 0, $first) . '...' . substr($value, strlen($value) - $last);

            $params->{$key} = $maskedValue;

        } else {

            if (!is_array($params)) {
                return false;
            }

            if (!isset($params[$key])) {
                return false;
            }

            $value = $params[$key];

            $maskedValue = substr($value, 0, $first) . '...' . substr($value, strlen($value) - $last);

            $params[$key] = $maskedValue . ' (' . strlen($value) . ')';
        }*/
    }

    public function getSeparator()
    {
        return PHP_EOL . PHP_EOL . str_repeat('=', 100) . PHP_EOL;
    }

    public function stripeDebug($title, $debugParams = [])
    {
        if ($this->debug) {

            // Mask sensitive info
            $this->maskSensitiveInfo($debugParams, 'client_secret', 3, 3);
            $this->maskSensitiveInfo($debugParams, 'customer', 3, 3);

            $this->saveDebug(self::STRIPE, $title, $debugParams);
        }
    }

    protected function saveDebug($type, $title, $debugParams = [])
    {
        $logFile = $this->getLogFile($type);

        $logContent = '[' . date('Y-m-d H:i:s') . ']: ' . $title . PHP_EOL;

        if (is_array($debugParams)) {

            if (sizeof($debugParams)) {
                $logContent .= print_r($debugParams, true);
            }

        } else {
            $logContent .= print_r($debugParams, true);
        }

        file_put_contents($logFile, $logContent, FILE_APPEND);
    }

}