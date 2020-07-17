<?php

namespace AppBundle\Services;

use AppBundle\Services\Queue\MailQueueProducer;

class EmailService
{
    /** @var TenantService */
    private $tenantService;

    /** @var SettingsService */
    private $settingsService;

    /** @var MailQueueProducer */
    private $publisher;

    /** @var string */
    public $senderName;

    /** @var string */
    public $fromEmail;

    /** @var string */
    public $replyToEmail;

    /** @var string */
    public $postmarkApiKey;

    /** @var array */
    private $errors = [];

    public function __construct(TenantService $tenantService, SettingsService $settings, MailQueueProducer $publisher)
    {
        $this->tenantService = $tenantService;
        $this->settingsService = $settings;
        $this->publisher = $publisher;
    }

    /**
     * Intentionally soaks up the admin exceptions silently
     * @param $toEmail
     * @param $toName
     * @param $subject
     * @param $message
     * @param bool $ccAdmin
     * @param array $attachments
     * @return bool
     */
    public function send($toEmail, $toName, $subject, $message, $ccAdmin = false, $attachments = [])
    {

        if (!$toEmail) {
            return false;
        }

        // When sending mails synchronously in code, we have a tenant already
        // When sending mails from a schedule, we have to set the sender parameters from the tenant we are working with
        if (!$this->senderName) {
            $this->senderName = $this->tenantService->getCompanyName();
        }
        if (!$this->replyToEmail) {
            $this->replyToEmail = $this->tenantService->getReplyToEmail();
        }
        if (!$this->fromEmail) {
            $this->fromEmail = $this->tenantService->getSenderEmail();
        }
        if (!$this->postmarkApiKey) {
            $this->postmarkApiKey = $this->tenantService->getSetting('postmark_api_key');
        }

        // Send the email via queue
        $payload = [
            'postmarkApiKey' => $this->postmarkApiKey,
            'replyToEmail' => $this->replyToEmail,
            'fromEmail' => $this->fromEmail,
            'fromName' => $this->senderName,
            'toName' => $toName,
            'toEmail' => $toEmail,
            'subject' => $subject,
            'message' => $message
        ];

        $this->publisher->publish(json_encode($payload));

        // Next we see if we have to send a CC to admin

        $sendToAdmin = false;
        $ccEmailAddress = $this->settingsService->getSettingValue('org_cc_email');
        if ($ccAdmin == true
            && $this->settingsService->getSettingValue('email_cc_admin') == 1) {
            $sendToAdmin = true;
        }
        if ($ccAdmin === 'always') {
            $sendToAdmin = true;
        }

        if ($sendToAdmin == true) {
            // Insert a green box at the top of the content
            $message = preg_replace('/<!--\/\/-->/', $this->addAdminInfo($toName, $toEmail), $message);

            // Remove the login button
            $message = preg_replace('/\<a id="loginButton".*?<\/a>/', '', $message);

            if (!$ccEmailAddress) {
                $ccEmailAddress = $this->replyToEmail;
            }

            // Send the email via queue
            $payload = [
                'postmarkApiKey' => $this->postmarkApiKey,
                'replyToEmail' => $ccEmailAddress,
                'fromEmail' => $this->fromEmail, // hello@lend-engine unless white labelled
                'fromName' => $this->senderName,
                'toName' => $this->senderName,
                'toEmail' => $ccEmailAddress,
                'subject' => '[Lend Engine CC] '.$subject,
                'message' => $message
            ];

            $this->publisher->publish(json_encode($payload));
        }

        if (count($this->errors) > 0) {
            return false;
        }

        return true;

    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $toName
     * @param $toEmail
     * @return string
     */
    private function addAdminInfo($toName, $toEmail)
    {
        $msg = "This is a copy of the email sent to {$toName} ({$toEmail}).";
        return '<br><div style="padding: 10px; background-color: #d5f996; border-radius: 4px; margin-bottom: 10px;">'.$msg.'</div>';
    }
}