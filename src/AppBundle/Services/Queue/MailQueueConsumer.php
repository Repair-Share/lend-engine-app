<?php

namespace AppBundle\Services\Queue;

use AppBundle\Services\EmailService;
use AppBundle\Services\Mailer;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class MailQueueConsumer
{
    /** @var LoggerInterface */
    private $logger;

    /** @var Mailer */
    private $mailer;

    /** @var array */
    public $errors = [];

    public function __construct(LoggerInterface $logger, Mailer $mailer)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
    }

    /**
     * @param AMQPMessage $msg
     * @return bool
     * @throws \Exception
     */
    public function execute(AMQPMessage $msg)
    {
        $body = $msg->getBody();
        $message = json_decode($body, true);

        // Connect to client

        if (isset($message['message']) && isset($message['postmarkApiKey'])) {
            // Send email
            $this->mailer->send($message['postmarkApiKey'], $message);
        } else if (isset($message['sms_body'])) {
            // Send SMS

        }

        return true;

        // true = ack and remove
        // false = requeue
        // 0 redeliver
    }

}