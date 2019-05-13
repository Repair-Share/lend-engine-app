<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Tenant;
use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BillingController extends Controller
{
    /**
     * @Route("admin/billing", name="billing")
     */
    public function signupAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        /** @var \AppBundle\Services\BillingService $billingService */
        $billingService = $this->get('billing');
        $plans = $billingService->getPlans();

        // Override for Lend Engine subscriptions
        $stripeService->setApiKey($this->getParameter('billing_secret_key'));
        $stripeService->currency = 'gbp';

        /** @var $repo \AppBundle\Repository\TenantRepository */
        $repo = $em->getRepository('AppBundle:Tenant');

        $accountCode = $request->getSession()->get('account_code');

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $repo->findOneBy(['stub' => $accountCode]);

        // Subscribing for a new plan
        if ($request->get('plan')) {

            $token   = $request->get('stripeToken');
            $plan    = $request->get('plan');

            $success = false;
            if ($plan == 'free') {
                $tenant->setPlan($plan);
                $tenant->setStatus(Tenant::STATUS_LIVE);
                $em->persist($tenant);
                $em->flush();
                $success = true;
            } else if ($token) {
                if ($stripeService->createSubscription($token, $tenant, $plan)) {
                    $success = true;
                } else {
                    $this->addFlash('error', 'Failed to process your card details using Stripe.');
                    foreach ($stripeService->errors AS $error) {
                        $this->addFlash('error', $error);
                    }
                }
            }

            if ($success == true) {
                $this->sendBillingConfirmationEmail($tenant, $plan);
            }

            return $this->redirectToRoute('billing');
        }

        return $this->render('default/billing.html.twig', array(
            'plans' => $plans
        ));
    }

    /**
     * @Route("admin/billing/cancel", name="cancel_subscription")
     */
    public function cancelAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        // Override for Lend Engine subscriptions
        $stripeService->setApiKey($this->getParameter('billing_secret_key'));
        $stripeService->currency = 'gbp';

        /** @var $repo \AppBundle\Repository\TenantRepository */
        $repo = $em->getRepository('AppBundle:Tenant');

        $accountCode = $request->getSession()->get('account_code');

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $repo->findOneBy(['stub' => $accountCode]);

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
     * @param $planCode
     */
    private function sendBillingConfirmationEmail(Tenant $tenant, $planCode)
    {
        try {
            $client = new PostmarkClient($this->getParameter('postmark_api_key'));
            $message = $this->renderView('emails/billing_welcome.html.twig',
                ['plan' => $planCode]
            );
            $client->sendEmail(
                "Lend Engine <hello@lend-engine.com>",
                $tenant->getOwnerEmail(),
                "Thanks for signing up",
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
                "Thanks for signing up",
                $message
            );
        } catch (PostmarkException $ex) {
            $this->addFlash('error', 'Failed to send email:' . $ex->message . ' : ' . $ex->postmarkApiErrorCode);
        } catch (\Exception $generalException) {
            $this->addFlash('error', 'Failed to send email:' . $generalException->getMessage());
        }
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