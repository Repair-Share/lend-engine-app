<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanUpClosedLoansCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('cleanup_closed_loans');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \AppBundle\Services\Schedule\ExpireMemberships $scheduleHandler */
        $scheduleHandler = $this->getContainer()->get('service.cleanup_closed_loans');
        $output->writeln('About to process loans ...');
        $results = $scheduleHandler->processLoans($output);
        die($results);
    }

}