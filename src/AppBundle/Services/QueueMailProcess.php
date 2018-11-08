<?php

/**
 *
 * NOT YET USED
 * Preparing for when emails are sent with RabbitMQ
 *
 */
namespace AppBundle\Services;

use PhpAmqpLib\Message\AMQPMessage;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Psr\Log\LoggerInterface;

class QueueMailProcess
{
    private $logger;

    public $errors = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
            return false;
        }

        $postMarkApiKey = getenv('SYMFONY__POSTMARK_API_KEY');

        sleep(2);
        return true;
//        try {
//            $client = new PostmarkClient($postMarkApiKey);
//            $client->sendEmail(
//                "hello@lend-engine.com",
//                $mail['to'],
//                $mail['subject'],
//                $mail['message']
//            );
//            $this->logger->info("Email sent to ".$mail['to']);
//            return true;
//        } catch (\Exception $generalException) {
//            $this->logger->error($generalException->getMessage());
//            return false;
//        }
    }

    /**
     * @param $mail
     * @return bool
     * @throws \Exception
     */
    private function validateMessage($mail)
    {
        if (!$mail['message']) {
            throw new \Exception("No message body");
        }

        return true;
    }

}