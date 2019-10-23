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
        try {
            $client = new PostmarkClient($this->getParameter('postmark_api_key'));
            $message = $this->renderView('emails/billing_cancel.html.twig',
                []
            );
            $client->sendEmail(
                "Lend Engine <hello@lend-engine.com>",
                $tenant->getOwnerEmail(),
                "We've cancelled your account",
                $message,
                null,
                null,
                true,
                'hello@lend-engine.com'
            );

            // And one to admin
            $client->sendEmail(
                "Lend Engine billing <hello@lend-engine.com>",
                "hello@lend-engine.com",
                "We've cancelled your account",
                $message
            );
        } catch (PostmarkException $ex) {
            $this->addFlash('error', 'Failed to send email:' . $ex->message . ' : ' . $ex->postmarkApiErrorCode);
        } catch (\Exception $generalException) {
            $this->addFlash('error', 'Failed to send email:' . $generalException->getMessage());
        }
    }
}