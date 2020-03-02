<?php

namespace AppBundle\Controller\Admin\Billing;

use AppBundle\Entity\Tenant;
use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class BillingCancelController extends Controller
{

    /**
     * @Route("admin/billing/cancel", name="cancel_subscription")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function cancelAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        // Override for Lend Engine subscriptions
        $stripeService->setApiKey($this->getParameter('billing_secret_key'));
        $stripeService->currency = 'gbp';

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $settingsService->getTenant();

        // Optionally set for Stripe subscriptions
        $subscriptionId = $request->get('id');

        if ($stripeService->cancelSubscription($tenant, $subscriptionId)) {
            $this->addFlash('success', "Your subscription was cancelled");
            $tenant->setStatus(Tenant::STATUS_CANCEL);
            $tenant->setPlan(null);
            $em->persist($tenant);
            $em->flush();

            $this->sendCancelEmail($tenant);
        } else {
            foreach ($stripeService->errors AS $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('billing');
    }

    /**
     * @param Tenant $tenant
     */
    private function sendCancelEmail(Tenant $tenant)
    {
        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        $message = $this->renderView('emails/billing_cancel.html.twig', []);

        // Send the email
        if (!$emailService->send($tenant->getOwnerEmail(), $tenant->getOwnerName(), "We've cancelled your account", $message, true)) {
            foreach ($emailService->getErrors() AS $msg) {
                $this->addFlash('error', $msg);
            }
        }
    }
}