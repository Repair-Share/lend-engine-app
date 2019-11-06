<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OverdueEmailCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('overdue_emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \AppBundle\Services\Schedule\EmailOverdueLoans $scheduleHandler */
        $scheduleHandler = $this->getContainer()->get('service.schedule_overdue_loans');
        $results = $scheduleHandler->processOverdueEmails();
        die($results);
    }

}