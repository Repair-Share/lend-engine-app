<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MembershipExpireCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('expire_memberships');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \AppBundle\Services\Schedule\ExpireMemberships $scheduleHandler */
        $scheduleHandler = $this->getContainer()->get('service.schedule_memberships');
        $output->writeln('About to process memberships ...');
        $results = $scheduleHandler->processMemberships($output);
        die($results);
    }

}