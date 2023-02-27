<?php

namespace AppBundle\Controller\Admin\Billing;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BillingPlansController extends Controller
{
    /**
     * @Route("admin/billing", name="billing")
     */
    public function signupAction(Request $request)
    {
        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        /** @var \AppBundle\Services\BillingService $billingService */
        $billingService = $this->get('billing');
        $plans = $billingService->getPlans();

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $settingsService->getTenant(false);

        $subscription = null;
        if ($subscriptionId = $tenant->getSubscriptionId()) {
            $subscription = $stripeService->getSubscription($subscriptionId);
        }

        return $this->render('default/billing.html.twig', array(
            'token' => md5($tenant->getStub()),
            'plans' => $plans,
            'subscription' => $subscription
        ));
    }

}