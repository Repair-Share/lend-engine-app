<?php
// src/AppBundle/EventListener/LocaleListener.php
namespace AppBundle\EventListener;

use AppBundle\Services\TenantService;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleListener implements EventSubscriberInterface
{
    /** @var string */
    private $defaultLocale;

    /** @var TenantService */
    private $tenantInformation;

    /**
     * @param string $defaultLocale
     * @param TenantService $tenantInformation
     */
    public function __construct($defaultLocale = 'en', TenantService $tenantInformation)
    {
        $this->tenantInformation = $tenantInformation;

        try {
            if ($locale = $tenantInformation->getLocale()) {
                $this->defaultLocale = $locale;
            } else {
                $this->defaultLocale = $defaultLocale;
            }
        } catch (\Exception $e) {
            $this->defaultLocale = $defaultLocale;
        }

    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale($request->getSession()->get('_locale', $this->defaultLocale));
        }
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            // must be registered after the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 15)),
        );
    }
}