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
            'CustomStyle'       => false,
            'CustomTheme'       => false,
            'MultipleLanguages' => false,
            'EmailAutomation'   => false,
            'Labels'            => false,
        ];

        switch ($plan) {

            case 'free':
                // nothing extra for the free plan
                break;

            case 'standard':
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
                    'Page'              => false,
                    'PrivateSite'       => true,
                    'CustomStyle'       => true,
                    'CustomTheme'       => false,
                    'MultipleLanguages' => true,
                    'EmailAutomation'   => true,
                    'Labels'            => false,
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
                    'Site'              => true,
                    'Page'              => true,
                    'PrivateSite'       => true,
                    'CustomStyle'       => true,
                    'CustomTheme'       => true,
                    'MultipleLanguages' => true,
                    'EmailAutomation'   => true,
                    'Labels'            => true,
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