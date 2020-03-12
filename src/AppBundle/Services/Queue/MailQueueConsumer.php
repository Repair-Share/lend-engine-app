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
        $mail = json_decode($body, true);

        if (!$this->validateMessage($mail)) {
            return true;
        }

        $this->mailer->send($mail['postmarkApiKey'], $mail);

        return true;

        // true = ack and remove
        // false = requeue
        // 0 redeliver
    }

    /**
     * @param $mail
     * @return bool
     * @throws \Exception
     */
    private function validateMessage($mail)
    {
        if (!isset($mail['message'])) {
            return false;
        }

        return true;
    }

}