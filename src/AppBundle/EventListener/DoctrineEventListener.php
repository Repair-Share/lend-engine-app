<?php

/**
 *
 * Intention here was to remove unsupported entities per pay plan
 * Not working, but still connected in services
 *
 *
 */
namespace AppBundle\EventListener;

use AppBundle\Services\BillingService;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\Session\Session;

class DoctrineEventListener
{

    /**
     * @var Session
     */
    private $session;

    /**
     * @var BillingService
     */
    private $billingService;

    public function __construct(Session $session, BillingService $billingService)
    {
        $this->session = $session;
        $this->billingService = $billingService;
    }

    public function postLoad(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();

        $entityName = str_replace('AppBundle\\Entity\\', "", get_class($entity));
        $plan        = $this->session->get('plan');

        if (!$this->billingService->isEnabled($plan, $entityName)) {

        }
    }
}