<?php

namespace AppBundle\Services;

use Postmark\PostmarkClient;

class EmailService
{
    /** @var TenantService */
    private $tenantService;

    /** @var SettingsService */
    private $settingsService;

    public function __construct(TenantService $tenantService, SettingsService $settings)
    {
        $this->tenantService = $tenantService;
        $this->settingsService = $settings;
    }

    /**
     * Intentionally soaks up the exceptions silently
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

        $senderName     = $this->tenantService->getCompanyName();
        $replyToEmail   = $this->tenantService->getReplyToEmail();
        $fromEmail      = $this->tenantService->getSenderEmail();
        $postmarkApiKey = $this->tenantService->getSetting('postmark_api_key');

        $client = new PostmarkClient($postmarkApiKey);

        try {
            $client->sendEmail(
                "{$senderName} <{$fromEmail}>",
                $toEmail,
                $subject,
                $message,
                null,
                null,
                true,
                $replyToEmail,
                null,
                null,
                null,
                $attachments
            );
        } catch (\Exception $e) {

        }

        $sendToAdmin = false;
        if ($ccAdmin == true && $this->settingsService->getSettingValue('email_cc_admin') == 1) {
            $sendToAdmin = true;
        }
        if ($ccAdmin == 'always') {
            $sendToAdmin = true;
        }

        if ($sendToAdmin == true) {
            // Insert a green box at the top of the content
            $message = preg_replace('/<!--\/\/-->/', $this->addAdminInfo($toName, $toEmail), $message);

            // Remove the login button
            $message = preg_replace('/\<a id="loginButton".*?<\/a>/', '', $message);

            try {
                $client->sendEmail(
                    "{$senderName} <{$fromEmail}>",
                    $replyToEmail,
                    '[Lend Engine CC] '.$subject,
                    $message,
                    null,
                    null,
                    true,
                    $replyToEmail
                );
            } catch (\Exception $e) {

            }
        }

        return true;

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