<?php

namespace AppBundle\Services;

use AppBundle\Entity\Tenant;
use AppBundle\Entity\Payment;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Container;
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

    /**
     * @var EntityManager
     */
    private $em;

    private $container;
    public  $currency;
    private $apiKey;

    private $cardFee = 0.00;

    public $errors = [];

    public function __construct(EntityManager $em, Container $container)
    {
        $this->em        = $em;
        $this->container = $container;

        $this->currency = $this->container->get('settings')->getSettingValue('org_currency');
        $this->apiKey   = $this->container->get('settings')->getSettingValue('stripe_access_token');

        // A per-transaction fee charged by the library to the member
        $this->cardFee  = $this->container->get('settings')->getSettingValue('stripe_fee');

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
     * @param $token
     * @param $amount
     * @param $msg
     * @return \Stripe\Charge
     */
    public function chargeWithToken($token, $amount, $msg = 'Lend Engine charge')
    {
        $params = [
            'amount'        => $amount,
            'currency'      => $this->currency,
            'source'        => $token,
            'description'   => $msg,
//            'application_fee' => round($amount * $this->fee, 0)
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
//            'application_fee' => round($amount * $this->fee, 0)
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
     * @param $cardId
     * @param $customerId
     * @param $amount
     * @param $msg
     * @return bool|\Stripe\Charge
     */
    public function chargeWithCard($cardId, $customerId, $amount, $msg = 'Lend Engine charge')
    {
        $params = [
            'amount'        => $amount,
            'currency'      => $this->currency,
            'customer'      => $customerId,
            'source'        => $cardId,
            'description'   => $msg,
//            'application_fee' => round($amount * $this->fee, 0)
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
     * @param $token
     * @param $cardId
     * @param Payment $payment
     * @param $msg string
     * @return bool|\Stripe\Charge
     */
    public function processPayment($token, $cardId, $payment, $msg = '')
    {

        if (!$token && !$cardId) {
            $this->errors[] = 'Either Stripe checkout token or card ID is required.';
        }

        $amount = $payment->getAmount();
        if (!$amount || $amount < 0) {
            $this->errors[] = 'Positive amount is required to charge via Stripe.';
        }

        $contact = $payment->getContact();
        if ($token && !$contact->getStripeCustomerId()) {

            // We don't yet have this customer in Stripe so create them
            $stripeCustomer = [
                'description' => $contact->getName(),
                'email' => $contact->getEmail(),
                'source' => $token
            ];

            if ($stripeCustomer = $this->createCustomer($stripeCustomer)) {

                $customerStripeId = $stripeCustomer['id'];
                $contact->setStripeCustomerId($customerStripeId);
                $this->em->persist($contact);

                // The card just used will have been set as the default for the new Stripe customer
                if (isset($stripeCustomer['sources']['data'])) {
                    foreach ($stripeCustomer['sources']['data'] AS $source) {
                        $cardId = $source['id'];
                    }
                }

                try {
                    $this->em->flush($contact);
                } catch (\Exception $generalException) {
                    $this->errors[] = 'Failed to update member with Stripe details: '.$generalException->getMessage();
                }

            } else {

                $this->errors[] = 'Could not create customer in Stripe.';
                return false;

            }

        }

        if ($cardId && $contact->getStripeCustomerId()) {
            $charge = $this->chargeWithCard($cardId, $contact->getStripeCustomerId(), $amount*100, $msg);
//            $this->savePayment($payment);
            return $charge;
        } else if ($token) {
            $charge = $this->chargeWithToken($token, $amount*100, $msg);
//            $this->savePayment($payment);
            return $charge;
        }

        $this->errors[] = 'Other payment error.';
        return false;
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
     * Save the payment as soon as it's deemed OK by Stripe in case of downstream problems
     * @param Payment $payment
     */
    private function savePayment(Payment $payment)
    {
//        if ($this->cardFee > 0) {
//            // Add a fee to reduce the customer balance by the fee amount
//            // (will have been added into the $payment amount to charge to Stripe already)
//            $fee = new Payment();
//            $fee->setCreatedBy($payment->getCreatedBy());
//            $fee->setAmount(-$this->cardFee);
//            $fee->setContact($payment->getContact());
//            $fee->setLoan($payment->getLoan());
//            $fee->setNote("Card fee.");
//            $this->em->persist($fee);
//        }
//
//        $this->em->persist($payment);
//
//        try {
//            $this->em->flush($payment);
//            if (isset($fee)) {
//                $this->em->flush($fee);
//            }
//        } catch (\Exception $generalException) {
//            $this->errors[] = 'Failed to save Payment: '.$generalException->getMessage();
//        }
    }

    /**
     * Subscribe the tenant to a Lend Engine plan
     * @param $token
     * @param Tenant $tenant
     * @param $planCode
     * @return bool|\Stripe\Charge
     */
    public function createSubscription($token, Tenant $tenant, $planCode)
    {

        if ($token && !$tenant->getStripeCustomerId()) {

            // We don't yet have this customer in Stripe so create them
            $stripeCustomer = [
                'description' => $tenant->getName(),
                'email' => $tenant->getOwnerEmail(),
                'source' => $token
            ];

            if ($stripeCustomer = $this->createCustomer($stripeCustomer)) {

                $customerStripeId = $stripeCustomer['id'];
                $tenant->setStripeCustomerId($customerStripeId);
                $this->em->persist($tenant);

                // The card just used will have been set as the default for the new Stripe customer
                if (isset($stripeCustomer['sources']['data'])) {
                    foreach ($stripeCustomer['sources']['data'] AS $source) {
                        $cardId = $source['id'];
                    }
                }

                try {
                    $this->em->flush($tenant);
                } catch (\Exception $generalException) {
                    $this->errors[] = 'Failed to update account with Stripe details: '.$generalException->getMessage();
                }

            }
        }

        if ($tenant->getStripeCustomerId()) {

            if ($subscription = $this->subscribeCustomer($tenant->getStripeCustomerId(), $planCode)) {
                // Save the new plan
                $tenant->setPlan($planCode);
                $tenant->setStatus(Tenant::STATUS_LIVE);
                $tenant->setSubscriptionId($subscription['id']);
                $this->em->persist($tenant);
                try {
                    $this->em->flush($tenant);
                    return true;
                } catch (\Exception $generalException) {
                    $this->errors[] = 'Failed to update account with new plan: '.$generalException->getMessage();
                }
            }

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

        if (!$subscriptionId = $tenant->getSubscriptionId()) {
            // May have a customer ID, but no subscription on Stripe
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

    /**
     * @param string $stripeCustomerId
     * @param string $planCode
     * @return bool|\Stripe\Subscription
     */
    public function subscribeCustomer($stripeCustomerId, $planCode)
    {
        try {
            $response = \Stripe\Subscription::create([
                'customer' => $stripeCustomerId,
                'plan'     => $planCode
            ]);

//            $response = \Stripe\Subscription::create([
//                'customer' => $stripeCustomerId,
//                'items'    => [
//                    'plan' => [
//                        'id' => $planCode
//                    ]
//                ]
//            ]);

            if (isset($response->error)) {
                $this->errors[] = $response->error->type.' : '.$response->error->message;
                return false;
            }
            return $response;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

}