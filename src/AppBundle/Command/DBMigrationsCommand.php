<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DBMigrationsCommand extends ContainerAwareCommand
{
    const SERVER_DEV = 'dev';
    const SERVER_STAGING = 'lend-engine-staging';
    const SERVER_EU = 'lend-engine-eu';
    const SERVER_EU_PLUS = 'lend-engine-eu-plus';
    const SERVER_EU_3 = 'lend-engine-3';

    protected static $defaultName = 'app:db-migrations';

    protected function configure()
    {
        $this->setName('app:db-migrations');
        $this->addArgument('server', InputArgument::REQUIRED, 'The server.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $serverVersions = [
            self::SERVER_DEV,
            self::SERVER_STAGING,
            self::SERVER_EU,
            self::SERVER_EU_PLUS,
            self::SERVER_EU_3
        ];

        $server = $input->getArgument('server');

        if (!in_array($server, $serverVersions)) {
            throw new \Exception($server . ' is not a valid server name');
        }

        /** @var \AppBundle\Services\Schedule\DBMigrations $scheduleHandler */
        $scheduleHandler = $this->getContainer()->get('service.schedule_db_migrations');
        $results         = $scheduleHandler->migrate($server);
        die($results);
    }

}