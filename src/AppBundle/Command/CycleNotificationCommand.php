<?php

namespace AppBundle\Command;

use Postmark\PostmarkClient;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CycleNotificationCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('cycle-notify');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $postMarkApiKey = getenv('SYMFONY__POSTMARK_API_KEY');

        try {
            $toEmail = 'chris@lend-engine.com';
            $client = new PostmarkClient($postMarkApiKey);
            $message = 'Heroku dyno cycled';
            $client->sendEmail(
                "hello@lend-engine.com",
                $toEmail,
                "Dyno cycled at ".date("Y-m-d H:i:s"),
                $message
            );
        } catch (\Exception $generalException) {
            die("ERROR: Failed to send email : " . PHP_EOL . $generalException->getMessage());
        }

    }

}