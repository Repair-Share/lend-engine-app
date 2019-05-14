<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\Tenant;
use AppBundle\Services\SettingsService;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\Routing\Router;

class BillingListener
{
    private $settings;
    private $router;

    function __construct(SettingsService $settings, Router $router) {
        $this->settings = $settings;
        $this->router = $router;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        $request = $event->getRequest();

        if (!in_array($request->attributes->get('_route'),
            [
                'billing',
                'fos_user_security_login',
                'fos_user_security_logout',
                'fos_user_resetting_request',
                'clear_site'
            ])) {

            if (!$tenant = $this->settings->getTenant()) {
                die('We could not find your account');
            } else {
                // If cancelled, redirect to home
                if ($tenant->getStatus() == Tenant::STATUS_CANCEL) {
                    $redirectUrl = $this->router->generate('billing');
                    $event->setController(function() use ($redirectUrl) {
                        return new RedirectResponse($redirectUrl);
                    });
                }

                // Prevent user from saving data (POST)
                if ($request->getMethod() == "POST") {
                    // If in trial, check the trial expiry date
                    if ($tenant->getStatus() == Tenant::STATUS_TRIAL) {
                        $now = new \DateTime();
                        if ($tenant->getTrialExpiresAt() < $now) {
                            $redirectUrl = $this->router->generate('billing');
                            $event->setController(function() use ($redirectUrl) {
                                return new RedirectResponse($redirectUrl);
                            });
                        }
                    }
                }
            }

        }
    }
}