<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateMaintenanceCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('update_maintenance');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \AppBundle\Services\Schedule\UpdateMaintenance $scheduleHandler */
        $scheduleHandler = $this->getContainer()->get('service.schedule_maintenance');
        $results = $scheduleHandler->updateMaintenance();
        die($results);
    }

}