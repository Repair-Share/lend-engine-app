<?php
/**
 * Simple class to take a message and send it via SwiftMailer
 */
namespace AppBundle\Services;

use Psr\Log\LoggerInterface;

class MailerSwift extends Mailer
{
    /** @var LoggerInterface */
    private $logger;

    /** @var \Swift_Mailer */
    private $mailer;

    public function __construct(LoggerInterface $logger, \Swift_Transport $transport, $username, $password)
    {
        if ($transport instanceof \Swift_SmtpTransport) {
            if (isset($username)) {
                $transport->setUsername($username);
            }
            if (isset($password)) {
                $transport->setPassword($password);
            }
        }
        // Create the Mailer using your created Transport
        $mailer = new \Swift_Mailer($transport);
        $this->mailer = $mailer;
    }

    public function send($postmarkApiKey, $message)
    {
        if (isset($this->logger)) {
            $this->logger->info("sending message '" . $message['subject'] . "' to " . $message['toEmail']);
        }
        try {
            $swiftMessage = (new \Swift_Message($message['subject']))
                ->setFrom([$message['fromEmail'] => $message['fromName']])
                ->setTo($message['toEmail'])
                ->setBody(
                    $message['message'],
                    'text/html'
                );

            $this->mailer->send($swiftMessage);
        } catch (Exception $e) {
            if (isset($this->logger)) {
                $this->logger->error("Problem sending message '" . $message['subject'] . "' to " . $message['toEmail'] . ": " . $e->getMessage());
            }

        }
    }
}
