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
    public function isEnabled($plan = 'plus', $feature)
    {

        // For trial accounts
        if (!$plan) {
            $plan = 'plus';
        }

        switch ($plan) {

            case 'free':
                $enabled = [
                    'CheckInPrompt'     => false,
                    'CheckOutPrompt'    => false,
                    'ProductField'      => false,
                    'ContactField'      => false,
                    'ItemAttachment'    => false,
                    'ContactAttachment' => false,
                    'Deposits'          => false,
                    'CustomEmail'       => false,
                    'Site'              => false,
                    'Page'              => false,
                    'PrivateSite'       => false,
                    'SiteCSS'           => false,
                    'SiteJs'            => false,
                    'MultipleLanguages' => false,
                    'EmailAutomation'   => false,
                ];
                break;

            case 'standard':
                $enabled = [
                    'Site'              => false
                ];
                break;

            case 'plus':
                $enabled = [

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

            case 'standard':
                return 10000;
                break;

            case 'plus':
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

            case 'standard':
                return 10000;
                break;

            case 'plus':
                return 10000;
                break;
        }

        return 0;
    }
}