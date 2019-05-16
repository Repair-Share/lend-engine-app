<?php

/**
 *
 * Set the UI timezone, assuming all dates are in DB as UTC
 *
 */
namespace AppBundle\EventListener;

use AppBundle\Services\SettingsService;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class TwigDateRequestListener
{
    /** @var \Twig_Environment  */
    protected $twig;

    /** @var \AppBundle\Services\SettingsService  */
    private $settings;

    function __construct(\Twig_Environment $twig, SettingsService $settings) {
        $this->twig = $twig;
        $this->settings = $settings;
    }

    public function onKernelRequest(GetResponseEvent $event) {
        try {
            if (!$timezone = $this->settings->getSettingValue('org_timezone')) {
                $timezone = 'Europe/London';
            }
        } catch (\Exception $e) {
            $timezone = 'Europe/London';
        }
        $this->twig->getExtension('Twig_Extension_Core')->setTimezone($timezone);
    }
}