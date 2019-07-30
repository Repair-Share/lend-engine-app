<?php

namespace AppBundle\Controller\Billing;

use AppBundle\Entity\Tenant;
use AppBundle\Form\Type\BillingType;
use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class BillingPaymentController extends Controller
{
    /**
     * @Route("admin/billing_payment", name="billing_payment")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function billingPaymentAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        /** @var \AppBundle\Services\BillingService $billingService */
        $billingService = $this->get('billing');
        $plans = $billingService->getPlans();

        $selectedPlan = null;
        $amount = 0;

        $planCode = $request->get('planCode');

        foreach ($plans AS $plan) {
            if ($planCode == $plan['stripeCode']) {
                $selectedPlan = $plan;
                $amount = $plan['amount'];
            }
        }

        // Override for Lend Engine subscriptions
        $stripeService->setApiKey($this->getParameter('billing_secret_key'));
        $stripeService->currency = 'gbp';

        // Create the form
        $form = $this->createForm(BillingType::class, null, [
            'action' => $this->generateUrl('billing_payment_success')
        ]);

        $form->get('paymentAmount')->setData($amount);
        $form->get('planCode')->setData($planCode);

        return $this->render('modals/billing_payment.html.twig', array(
            'contact' => $this->getUser(),
            'form' => $form->createView(),
            'plan' => $selectedPlan,
            'billing_public_key' => $this->getParameter('billing_public_key')
        ));
    }

    /**
     * @Route("admin/billing_payment_success", name="billing_payment_success")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function billingPaymentSuccess(Request $request)
    {
        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        /** @var \AppBundle\Services\BillingService $billingService */
        $billingService = $this->get('billing');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $settingsService->getTenant();

        // Create the form
        $form = $this->createForm(BillingType::class, null, [
            'action' => $this->generateUrl('billing_payment_success')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $planCode       = $form->get('planCode')->getData();
            $subscriptionId = $form->get('subscriptionId')->getData();

            $stripeService->activateSubscription($tenant, $planCode, $subscriptionId);

            $this->addFlash("success", "You're subscribed!");
        }

        // Map the Stripe plan code to something neater.
        $this->sendBillingConfirmationEmail($tenant, $billingService->getPlanCode($planCode));

        return $this->redirectToRoute('billing');
    }

    /**
     * @Route("admin/billing_payment_handler", name="billing_payment_handler")
     * @Security("has_role('ROLE_SUPER_USER')")
     */
    public function billingPaymentHandler(Request $request)
    {

        /** @var \AppBundle\Services\StripeHandler $stripeService */
        $stripeService = $this->get('service.stripe');

        /** @var $settingsService \AppBundle\Services\SettingsService */
        $settingsService = $this->get('settings');

        /** @var \AppBundle\Entity\Tenant $tenant */
        $tenant = $settingsService->getTenant();

        $em = $this->getDoctrine()->getManager();

        // Override for Lend Engine subscriptions
        $stripeService->setApiKey($this->getParameter('billing_secret_key'));
        $stripeService->currency = 'gbp';

        $message = '';

        $data = json_decode($request->getContent(), true);

        if (isset($data['stripeTokenId']) && isset($data['planCode'])) {

            $tokenId  = $data['stripeTokenId'];
            $planCode = $data['planCode'];

            if ($planCode == 'free') {

                // Cancel any existing plans if they are downgrading
                if ($subscriptionId = $tenant->getSubscriptionId()) {
                    $stripeService->cancelSubscription($tenant, $subscriptionId);
                }
                $tenant->setPlan($planCode);
                $tenant->setStatus(Tenant::STATUS_LIVE);
                $em->persist($tenant);
                $em->flush();

                return new JsonResponse([
                    'success' => true,
                    'subscription_id' => $subscriptionId,
                    'message' => $message,
                ]);

            } else if ($tokenId) {

                // We're creating a new subscription
                $subscriptionResponse = $stripeService->createSubscription($tokenId, $tenant, $planCode);

                if (!$subscriptionResponse) {
                    $extraErrors = 'Failed to process card';
                    return new JsonResponse([
                        'error' => $extraErrors,
                        'message' => $message,
                        'errors' => $stripeService->errors
                    ]);
                } else if ($subscriptionResponse->status == 'active') {
                    return new JsonResponse([
                        'success' => true,
                        'subscription_id' => $subscriptionResponse->id,
                        'message' => $message,
                    ]);
                } else if ($subscriptionResponse->status == 'incomplete') {
                    return new JsonResponse([
                        'requires_action' => true,
                        'subscription_id' => $subscriptionResponse->id,
                        'payment_intent_client_secret' => $subscriptionResponse->latest_invoice->payment_intent->client_secret,
                        'message' => $message,
                    ]);
                }

            } else {
                return new JsonResponse([
                    'error' => "Invalid plan or token",
                    'message' => $message,
                    'errors' => $stripeService->errors
                ]);
            }

        } else {

            return new JsonResponse([
                'error' => "No planCode or Token",
                'message' => $message,
                'errors' => $stripeService->errors
            ]);

        }

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
}