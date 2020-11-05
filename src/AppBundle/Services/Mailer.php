<?php
/**
 * Simple class to take a message and send it via PostmarkApp
 */
namespace AppBundle\Services;

use Postmark\PostmarkClient;

class Mailer
{
    public function send($postmarkApiKey, $message)
    {
        $client = new PostmarkClient($postmarkApiKey);

        $fromName  = $message['fromName'];
        $fromEmail = $message['fromEmail'];

        try {
            $client->sendEmail(
                "{$fromName} <{$fromEmail}>",
                $message['toEmail'],
                $message['subject'],
                $message['message'],
                null,
                null,
                true,
                $message['replyToEmail']
            );
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return true;
    }
}