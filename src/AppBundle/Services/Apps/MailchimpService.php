<?php

namespace AppBundle\Services\Apps;

use AppBundle\Entity\Contact;
use DrewM\MailChimp\MailChimp;

class MailchimpService
{
    /** @var \AppBundle\Services\Apps\AppService  */
    private $appService;

    private $apiKey;
    private $listId;
    private $optIn;

    public function __construct(AppService $appService)
    {
        $this->appService = $appService;
        $this->app = $this->appService->get('mailchimp');

        if (isset($this->app['settings']['api_key']['data'])) {
            $this->apiKey = $this->app['settings']['api_key']['data'];
        }
        if (isset($this->app['settings']['list_id']['data'])) {
            $this->listId = $this->app['settings']['list_id']['data'];
        }
        if (isset($this->app['settings']['opt_in']['data'])) {
            $this->optIn  = $this->app['settings']['opt_in']['data'];
        }
    }

    /**
     * @param Contact $user
     * @return bool|null
     */
    public function checkMemberStatus(Contact $user)
    {
        if (!$this->installedAndConfigured()) {
            return false;
        }

        if (!$user->getEmail()) {
            return false;
        }

        $MailChimp = new MailChimp($this->apiKey);
        $subscriber_hash = MailChimp::subscriberHash($user->getEmail());
        $result = $MailChimp->get("lists/{$this->listId}/members/{$subscriber_hash}");

        if (isset($result['status'])) {
            return $result['status'];
        }

        return null;
    }

    /**
     * @param Contact $user
     * @return bool
     */
    public function updateMember(Contact $user)
    {
        if (!$this->installedAndConfigured()) {
            return false;
        }

        if (!$user->getEmail()) {
            return false;
        }

        if (!$user->getSubscriber()) {
            return false;
        }

        $MailChimp = new MailChimp($this->apiKey);

        // Attempt to update the user
        $subscriber_hash = MailChimp::subscriberHash($user->getEmail());
        $mergeFields = [
            'FNAME' => $user->getFirstName(),
            'LNAME' => $user->getLastName(),
        ];

        $result = $MailChimp->put("lists/$this->listId/members/$subscriber_hash", [
            'merge_fields' => $mergeFields
        ]);

        if (isset($result['status']) && $result['status'] == 400) {
            // Failed to update, create a new member
            if ($this->optIn) {
                $subscribed = 'pending';
            } else {
                $subscribed = 'subscribed';
            }
            $params = [
                'email_address' => $user->getEmail(),
                'status'        => $subscribed,
                'merge_fields'  => $mergeFields,
            ];
            $result = $MailChimp->post("lists/$this->listId/members", $params);
        }

        return true;
    }

    /**
     * @return bool
     */
    private function installedAndConfigured()
    {
        if (!$this->app) {
            return false;
        }

        if (!$this->apiKey || !$this->listId) {
            return false;
        }
        return true;
    }

}