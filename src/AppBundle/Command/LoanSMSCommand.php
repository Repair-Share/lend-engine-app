<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LoanSMSCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('loansms');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \AppBundle\Services\Schedule\SMSLoanReminders $scheduleHandler */
        $scheduleHandler = $this->getContainer()->get('service.schedule_loan_sms');
        $results = $scheduleHandler->processLoanSMS();
        die($results);
    }

}