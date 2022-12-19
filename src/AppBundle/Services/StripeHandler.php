<?php

namespace AppBundle\Services;

use AppBundle\Entity\Tenant;
use AppBundle\Entity\Payment;
use AppBundle\Services\Debug\DebugService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Session\Session;

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

    /** @var DebugService */
    private $debugService;

    public  $currency;
    private $apiKey;

    private $cardFee = 0.00;

    public $errors = [];

    /**
     * BasketService constructor.
     * @param EntityManagerInterface $em
     * @param SettingsService $settings
     * @param DebugService $debugService
     */
    public function __construct(EntityManager $em, SettingsService $settings, DebugService $debugService)
    {
        $this->em              = $em;
        $this->settingsService = $settings;
        $this->debugService    = $debugService;

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
     * @param $customer
     * @return bool|static
     */
    public function createPaymentIntent($paymentMethodId, $amount, $customer)
    {
        // Always have a customer for payments
        if (!$customer['id']) {
            $customer = $this->createCustomer($customer);
        }

        try {

            $params = [
                'payment_method'      => $paymentMethodId,
                'amount'              => $amount,
                'currency'            => $this->currency,
                'confirmation_method' => 'manual',
                'confirm'             => true,
                'customer'            => $customer['id'],
                'setup_future_usage'  => 'off_session',
                'metadata'            => ['integration_check' => 'accept_a_payment']
            ];

            $this->debugService->stripeDebug('Creating intent', $params);

            $intent = \Stripe\PaymentIntent::create($params);

            $this->debugService->stripeDebug('Intent created', $intent);
            $this->debugService->stripeDebug($this->debugService->getSeparator());

            return $intent;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();

            $this->debugService->stripeDebug('Creating intent failed', $e->getMessage());
            $this->debugService->stripeDebug($this->debugService->getSeparator());

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
        $tenant->setStripeCustomerId(null);

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