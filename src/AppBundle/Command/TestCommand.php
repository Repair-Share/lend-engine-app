<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('test_command');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        error_log("hello, this is a test!");
        $output->writeln('About to process ...');
        die("Completed".PHP_EOL);
    }
}