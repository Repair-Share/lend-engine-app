<?php

namespace AppBundle\Services;

/**
 * Class BillingService
 * @package AppBundle\Services
 *
 * Handles how features are mapped to billing plans
 *
 */
class BillingService
{
    /** @var string */
    private $env;

    /**
     * @param $symfonyEnv
     */
    public function __construct($symfonyEnv)
    {
        $this->env = $symfonyEnv;
    }

    public function isEnabled($plan = 'plus', $feature)
    {

        // For trial accounts
        if (!$plan) {
            $plan = 'plus';
        }

        $enabled = [
            'CheckInPrompt'     => false,
            'CheckOutPrompt'    => false,
            'ProductField'      => false,
            'ContactField'      => false,
            'ItemAttachment'    => false,
            'ContactAttachment' => false,
            'Deposits'          => false,
            'CustomEmail'       => false,
            'Site'              => false, // restrict items to sites
            'Page'              => false, // add/edit web pages
            'PrivateSite'       => false,
            'CustomStyle'       => false,
            'CustomTheme'       => false,
            'MultipleLanguages' => false,
            'EmailAutomation'   => false,
            'Labels'            => false,
            'WhiteLabel'        => false,
            'EventBooking'      => false,
        ];

        switch ($plan) {

            case 'free':
                // nothing extra for the free plan
                break;

            case 'starter':
                $enabled = [
                    'CheckInPrompt'     => false,
                    'CheckOutPrompt'    => false,
                    'ProductField'      => false,
                    'ContactField'      => false,
                    'ItemAttachment'    => false,
                    'ContactAttachment' => false,
                    'Deposits'          => true,
                    'CustomEmail'       => true,
                    'Site'              => false,
                    'Page'              => true,
                    'PrivateSite'       => true,
                    'CustomStyle'       => true,
                    'CustomTheme'       => false,
                    'MultipleLanguages' => true,
                    'EmailAutomation'   => true,
                    'Labels'            => false,
                    'WhiteLabel'        => false,
                    'EventBooking'      => true,
                ];
                break;

            case 'plus':
                $enabled = [
                    'CheckInPrompt'     => true,
                    'CheckOutPrompt'    => true,
                    'ProductField'      => true,
                    'ContactField'      => true,
                    'ItemAttachment'    => true,
                    'ContactAttachment' => true,
                    'Deposits'          => true,
                    'CustomEmail'       => true,
                    'Site'              => false,
                    'Page'              => true,
                    'PrivateSite'       => true,
                    'CustomStyle'       => true,
                    'CustomTheme'       => true,
                    'MultipleLanguages' => false,
                    'EmailAutomation'   => true,
                    'Labels'            => true,
                    'WhiteLabel'        => false,
                    'EventBooking'      => true,
                ];
                break;

            case 'business':
                $enabled = [
                    'CheckInPrompt'     => true,
                    'CheckOutPrompt'    => true,
                    'ProductField'      => true,
                    'ContactField'      => true,
                    'ItemAttachment'    => true,
                    'ContactAttachment' => true,
                    'Deposits'          => true,
                    'CustomEmail'       => true,
                    'Site'              => true,
                    'Page'              => true,
                    'PrivateSite'       => true,
                    'CustomStyle'       => true,
                    'CustomTheme'       => true,
                    'MultipleLanguages' => true,
                    'EmailAutomation'   => true,
                    'Labels'            => true,
                    'WhiteLabel'        => true,
                    'EventBooking'      => true,
                ];
                break;

        }

        if (isset($enabled[$feature]) && $enabled[$feature] == false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @param $plan
     * @return int
     */
    public function getMaxItems($plan)
    {
        // For trial accounts
        if (!$plan) {
            $plan = 'free';
        }
        switch ($plan) {
            case 'free':
                return 100;
                break;

            case 'starter':
                return 500;
                break;

            case 'plus':
                return 2000;
                break;

            case 'business':
                return 10000;
                break;
        }
        return 0;
    }

    /**
     * @param $plan
     * @return int
     */
    public function getMaxContacts($plan)
    {
        if (!$plan) {
            $plan = 'free';
        }
        switch ($plan) {
            case 'free':
                return 100;
                break;

            case 'starter':
                return 500;
                break;

            case 'plus':
                return 2000;
                break;

            case 'business':
                return 10000;
                break;
        }
        return 0;
    }

    /**
     * @param $plan
     * @return int
     */
    public function getMaxSites($plan)
    {
        // During trial, only one site
        if (!$plan) {
            $plan = 'free';
        }
        switch ($plan) {
            case 'free':
                return 1;
                break;

            case 'starter':
                return 1;
                break;

            case 'plus':
                return 10;
                break;

            case 'business':
                return 30;
                break;
        }
        return 0;
    }

    /**
     * @param $plan
     * @return int
     */
    public function getMaxEvents($plan)
    {
        if (!$plan) {
            $plan = 'free';
        }
        switch ($plan) {
            case 'free':
                return 10;
                break;

            case 'starter':
                return 50;
                break;

            case 'plus':
                return 100;
                break;

            case 'business':
                return 500;
                break;
        }
        return 0;
    }

    /**
     * Returns the CURRENT billing plans for display in the UI
     * Customers on legacy plans are mapped to one of the current plans in CustomConnectionFactory
     *
     *
     * Any changes to this map also need to be made on lend-engine-site codebase
     *
     *
     * @return array
     */
    public function getPlans()
    {

        /**
         *
         * We now have to use only the live plans, since payment is processed all on www.lend-engine.com
         * To test the subscription flow, boot a dev server on lend-engine-site and access the subscribe page manually
         * /subscribe?account=XX&planCode={devPlanCode}&token=md5({accountCode})
         *
         */
        if ($this->env == 'prod' || 1) {

            // ALL PROD SERVERS

            $plans = [
                [
                    'code' => 'free',
                    'stripeCode' => 'free',
                    'name' => 'Free',
                    'amount' => 0
                ],
                [
                    'code' => 'starter',
                    'stripeCode' => 'plan_Cv8Lg7fyOJSB0z', // Standard monthly 5.00
                    'name' => 'Starter',
                    'amount' => 500
                ],
                [
                    'code' => 'plus',
                    'stripeCode' => 'plus',
                    'name' => 'Plus',
                    'amount' => 2000
                ],
                [
                    'code' => 'business',
                    'stripeCode' => 'plan_F4HgQehPQ2nOlN',
                    'name' => 'Business',
                    'amount' => 4000
                ]
            ];

        } else {

            // STAGING AND DEV SERVER

            $plans = [
                [
                    'code' => 'free',
                    'stripeCode' => 'free',
                    'name' => 'Free',
                    'amount' => 0
                ],
                [
                    'code' => 'starter',
                    'stripeCode' => 'plan_FX1HLedEtRzj4k',
                    'name' => 'Starter',
                    'amount' => 500
                ],
                [
                    'code' => 'plus',
                    'stripeCode' => 'plus',
                    'name' => 'Plus',
                    'amount' => 2000
                ],
                [
                    'code' => 'business',
                    'stripeCode' => 'plan_F4HR4VG76biNcB',
                    'name' => 'Business',
                    'amount' => 4000
                ]
            ];

        }

        return $plans;
    }

    /**
     * Transform plan_Cv6rBge0LPVNin to starter to allow dynamic plans on Stripe while keeping fixed codes in app
     * @param $planStripeCode
     * @return mixed
     */
    public function getPlanCode($planStripeCode)
    {
        $plan = 'NOTSET';
        switch ($planStripeCode) {

            case 'free':
                $plan = 'free';
                break;

            case 'standard':
            case 'starter':
            case 'plan_Cv8Lg7fyOJSB0z': // standard monthly 5.00
            case 'plan_Cv6TbQ0PPSnhyL': // test plan
            case 'plan_Cv6rBge0LPVNin': // test plan
            case 'plan_FX1HLedEtRzj4k': // starter on test env
            case 'single':
                $plan = 'starter';
                break;

            case 'premium':
            case 'plus':
            case 'multiple':
                $plan = 'plus';
                break;

            case 'business':
            case 'plan_F4HR4VG76biNcB': // test
            case 'plan_F4HgQehPQ2nOlN': // prod
                $plan = 'business';
                break;
        }

        return $plan;
    }
}