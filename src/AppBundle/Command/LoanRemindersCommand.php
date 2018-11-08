<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoanRemindersCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('loanreminders');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scheduleHandler = $this->getContainer()->get('service.schedule_handler');
        $results = $scheduleHandler->processLoanReminders();
        die($results);
    }

}