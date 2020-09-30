<?php
/**
 * Simple class to take a message and send it via PHPMailer to Mailcatcher smtp://127.0.0.1:1025 (see https://mailcatcher.me/)
 */
namespace AppBundle\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../PHPMailer/Exception.php';
require __DIR__ . '/../PHPMailer/PHPMailer.php';
require __DIR__ . '/../PHPMailer/SMTP.php';

class MailerDev extends Mailer
{
    public function send($postmarkApiKey, $message)
    {
        $client = new PHPMailer(true);

        $fromName  = $message['fromName'];
        $fromEmail = $message['fromEmail'];
        //Server settings
        $client->SMTPDebug = SMTP::DEBUG_SERVER;               // Enable verbose debug output
        $client->isSMTP();                                     // Send using SMTP
        $client->Host       = '127.0.0.1';                     // Set the SMTP server to send through
        $client->SMTPAuth   = false;                           // Disable SMTP authentication
        $client->Port       = 1025;                            // TCP port to connect to

        try {
            //Recipients
            $client->setFrom($fromEmail, $fromName);
            $client->addAddress($message['toEmail']);     // Add a recipient
            $client->addReplyTo($message['replyToEmail']);

            // Content
            $client->isHTML(true);                                  // Set email format to HTML
            $client->Subject = $message['subject'];
            $client->Body    = $message['message'];

            $client->send();
        } catch (Exception $e) {

        }

        return true;
    }
}