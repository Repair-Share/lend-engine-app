<?php

/**
 *
 * NOT YET USED
 * Preparing for when emails are sent with RabbitMQ
 *
 */
namespace AppBundle\Command;

use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MailerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->addArgument('mail', InputArgument::REQUIRED)
            ->setName('mail:process')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $message = new AMQPMessage($input->getArgument('mail'));

        $body = $message->getBody();

        $uncompressed  = base64_decode($body);
        $json = gzuncompress($uncompressed);
        $mail = json_decode($json, true);

        /** @var \AppBundle\Services\QueueMailProcess $mailer */
        $mailer = $this->getContainer()->get('queue.mail.process');

        if (false == $mailer->sendMail($mail)) {
            echo 'Mailer failed : '.PHP_EOL;
            foreach ($mailer->errors AS $error) {
                echo $error.PHP_EOL;
            }
            exit(1);
        }

        exit(0);
    }
}