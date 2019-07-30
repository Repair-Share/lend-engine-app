<?php

namespace AppBundle\Services;

use AppBundle\Entity\Tenant;
use AppBundle\Entity\Payment;
use Doctrine\ORM\EntityManager;
use Stripe\Stripe;

/**
 * Class StripeHandler
 * @package AppBundle\Services
 *
 * Tests required for payments:
 *
 * Add credit with new card
 * - with card fee
 * - without card fee
 *
 * Add credit with existing card
 * - with card fee
 * - without card fee
 *
 * Add credit by cash
 *
 * Remove credit by cash (negative amount)
 *
 * Check out loan where items have been charged already (no payment should be requested)
 *
 * Check out loan where items have not yet been charged, payment with new card
 * - with card fee
 * - without card fee
 *
 * Check out loan where items have not yet been charged, payment with existing card
 * - with card fee
 * - without card fee
 *
 * Create reservation with settings to charge at point of reservation
 *
 * Create new membership with cash
 *
 * Create new membership with new card
 * - with card fee
 * - without card fee
 *
 * Create new membership with existing card
 * - with card fee
 * - without card fee
 *
 * Self serve sign up with charged membership (new card)
 *
 * Add misc fee to loan, should reduce contact balance
 *
 * Extend loan, payment with new card
 * - with fee
 * - without fee
 *
 * Extend loan, payment with existing card
 * - with card fee
 * - without card fee
 *
 */

class StripeHandler
{

    /** @var EntityManager */
    private $em;

    /** @var SettingsService */
    private $settingsService;

    public  $currency;
    private $apiKey;

    private $cardFee = 0.00;

    public $errors = [];

    public function __construct(EntityManager $em, SettingsService $settings)
    {
        $this->em        = $em;
        $this->settingsService = $settings;

        $this->currency = $this->settingsService->getSettingValue('org_currency');
        $this->apiKey   = $this->settingsService->getSettingValue('stripe_access_token');

        // A per-transaction fee charged by the library to the member
        $this->cardFee  = $this->settingsService->getSettingValue('stripe_fee');

        if ($this->apiKey) {
            $this->setApiKey($this->apiKey);
        }
    }

    /**
     * Can be given in the controller each time this handler is called
     * Allows the controller to decide which Stripe account is charged
     * @param $apiKey
     */
    public function setApiKey($apiKey)
    {
        Stripe::setApiKey($apiKey);
    }

    /**
     * @return \Stripe\Collection
     */
    public function getAllCustomers()
    {
        return \Stripe\Customer::all(array("limit" => 3));
    }

    /**
     * @param $subscriptionId
     * @return bool|\Stripe\StripeObject
     */
    public function getSubscription($subscriptionId)
    {
        try {
            $sub = \Stripe\Subscription::retrieve($subscriptionId);
            return $sub;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $customerStripeId
     * @return \Stripe\Customer
     */
    public function getCustomerById($customerStripeId)
    {
        try {
            $stripeCustomer = \Stripe\Customer::retrieve($customerStripeId);
            return $stripeCustomer;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $customerStripeId
     * @return bool|\Stripe\Collection
     */
    public function getCustomerPaymentMethods($customerStripeId)
    {
        try {
            $paymentMethods = \Stripe\PaymentMethod::all(["customer" => $customerStripeId, "type" => "card"]);
            return $paymentMethods;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $stripeCustomerId
     * @param $paymentMethodId
     * @return bool
     */
    public function attachPaymentMethod($stripeCustomerId, $paymentMethodId)
    {
        try {
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $stripeCustomerId]);
            return true;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param array $customer
     * @return \Stripe\Customer
     */
    public function createCustomer($customer = array())
    {
        try {
            $stripeCustomer = \Stripe\Customer::create($customer);
            return $stripeCustomer;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $customerId
     * @param $amount
     * @param $msg
     * @return \Stripe\Charge
     */
    public function chargeCustomer($customerId, $amount, $msg = 'Lend Engine charge')
    {
        $params = [
            'amount'        => $amount,
            'currency'      => $this->currency,
            'customer'      => $customerId,
            'description'   => $msg,
        ];
        try {
            $charge = \Stripe\Charge::create($params);
            return $charge;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $paymentMethodId
     * @param $amount
     * @param $customer array
     * @return bool|static
     */
    public function createPaymentIntent($paymentMethodId, $amount, $customer)
    {
        // Always have a customer for payments
        if (!$customer['id']) {
            $customer = $this->createCustomer($customer);
        }

        try {
            $intent = \Stripe\PaymentIntent::create([
                'payment_method' => $paymentMethodId,
                'amount' => $amount,
                'currency' => $this->currency,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'customer' => $customer['id'],
                'setup_future_usage' => 'off_session'
            ]);
            return $intent;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $paymentIntentId
     * @return bool|static
     */
    public function retrievePaymentIntent($paymentIntentId)
    {
        try {
            $intent = \Stripe\PaymentIntent::retrieve(
                $paymentIntentId
            );
            $intent->confirm();
            return $intent;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $paymentMethodId
     * @return bool|static
     */
    public function removePaymentMethod($paymentMethodId)
    {
        try {
            $payment_method = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $payment_method->detach();
            return $payment_method;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param $chargeId
     * @param $amount
     * @return bool|\Stripe\ApiResource
     */
    public function refundPayment($chargeId, $amount)
    {
        if (!$chargeId) {
            $this->errors[] = 'A charge ID is required to process a refund.';
            return false;
        }

        // Prepare for Stripe
        $amount = $amount * 100;

        try {
            $refund = \Stripe\Refund::create([
                'charge' => $chargeId,
                'amount' => $amount,
                'reason' => 'requested_by_customer'
            ]);
            return $refund;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }

    }

    /**
     * Subscribe the tenant to a Lend Engine plan
     * @param $tokenId
     * @param Tenant $tenant
     * @param $planCode
     * @return bool|\Stripe\Charge
     */
    public function createSubscription($tokenId, Tenant $tenant, $planCode)
    {

        if ($stripeCustomerId = $tenant->getStripeCustomerId()) {
            // Update the customer to use the new card details
            \Stripe\Customer::update(
                $stripeCustomerId,
                ['source' => $tokenId,]
            );
        } else {
            // new customer
            $customerDetails = [
                'name' => $tenant->getName(),
                'description' => $tenant->getOrgEmail(),
                'email' => $tenant->getOwnerEmail(),
                'source' => $tokenId
            ];

            if (!$customer = $this->createCustomer($customerDetails)) {
                $this->errors[] = "Could not create a customer in Stripe";
                return false;
            }

            $stripeCustomerId = $customer['id'];
            $tenant->setStripeCustomerId($stripeCustomerId);
        }

        try {

            $response = \Stripe\Subscription::create([
                'customer' => $stripeCustomerId,
                'plan'     => $planCode,
                'expand' => ['latest_invoice.payment_intent']
            ]);

            if (isset($response->error)) {
                $this->errors[] = $response->error->type.' : '.$response->error->message;
                return false;
            }

            if ($response->status == 'active') {
                return $response;
            } else if ($response->status == 'incomplete') {
                // Save the Stripe customer ID, but not the subscription
                $this->errors[] = 'Your card failed. Please try again.';
                try {
                    $this->em->persist($tenant);
                    $this->em->flush($tenant);
                } catch (\Exception $generalException) {
                    $this->errors[] = 'Failed to update tenant with new Stripe ID: '.$generalException->getMessage();
                }
                return $response;
            } else {
                $this->errors[] = 'Unhandled response status: '.$response->status;
                return false;
            }
        } catch (\Exception $generalException) {
            $this->errors[] = $generalException->getMessage();
        }

        return false;

//        $response = \Stripe\Subscription::create([
//            'customer' => $stripeCustomerId,
//            'items'    => [
//                'plan' => [
//                    'id' => $planCode
//                ]
//            ],
//            'expand' => ['latest_invoice.payment_intent']
//        ]);



    }

    /**
     * @param Tenant $tenant
     * @param $planCode
     * @param $subscriptionId
     * @return bool
     */
    public function activateSubscription(Tenant $tenant, $planCode, $subscriptionId)
    {
        $oldSubscriptionId = $tenant->getSubscriptionId();

        // ACTIVATE SUBSCRIPTION
        $tenant->setPlan($planCode);
        $tenant->setStatus(Tenant::STATUS_LIVE);
        $tenant->setSubscriptionId($subscriptionId);

        try {
            $this->em->persist($tenant);
            $this->em->flush($tenant);

            // Cancel any existing plans
            if ($oldSubscriptionId) {
                try {
                    $sub = \Stripe\Subscription::retrieve($oldSubscriptionId);
                    $sub->cancel();
                } catch (\Exception $e) {
                    $this->errors[] = 'Failed to cancel previous subscription: '.$e->getMessage();
                    $this->errors[] = $e->getMessage();
                }
            }
            return true;
        } catch (\Exception $generalException) {
            $this->errors[] = 'Failed to update account with new plan: '.$generalException->getMessage();
        }

        return false;
    }

    /**
     * @param Tenant $tenant
     * @param null $subscriptionId
     * @return bool
     */
    public function cancelSubscription(Tenant $tenant, $subscriptionId = null)
    {
        if (!$stripeCustomerId = $tenant->getStripeCustomerId()) {
            // Cancel and return
            $this->cancelAccount($tenant);
            return true;
        }

        if (!$customer = $this->getCustomerById($stripeCustomerId)) {
            // Does not exist in Stripe
            $this->cancelAccount($tenant);
            return true;
        }

        if (!$tenant->getSubscriptionId()) {
            // May have a customer ID, but no active subscription on Stripe
            $this->cancelAccount($tenant);
            return true;
        }

        try {
            $sub = \Stripe\Subscription::retrieve($subscriptionId);
            $sub->cancel();
            $this->cancelAccount($tenant);
            return true;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param Tenant $tenant
     * @return bool
     */
    private function cancelAccount(Tenant $tenant)
    {
        $tenant->setPlan(null);
        $tenant->setStatus(Tenant::STATUS_CANCEL);
        $tenant->setSubscriptionId(null);
        $this->em->persist($tenant);
        try {
            $this->em->flush($tenant);
            return true;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

}