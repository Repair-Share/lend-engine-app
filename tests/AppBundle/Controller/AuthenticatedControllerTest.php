<?php

namespace Tests\AppBundle\Controller;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Client;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Input\StringInput;

abstract class AuthenticatedControllerTest extends \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
{

    protected static $application;

    /**
     * @var Client
     */
    protected $client = null;

    /**
     * @var TestHelpers
     */
    protected $helpers;

    public function setUp()
    {
        $this->client = $this->createAuthorizedClient();
        $this->helpers = new TestHelpers();
    }

    public function getContainer()
    {
        $kernel = static::createKernel();
        $kernel->boot();

        return $kernel->getContainer();
    }

    /**
     * @return Client
     */
    protected function createAuthorizedClient()
    {
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'tech@lend-engine.com',
            'PHP_AUTH_PW'   => 'unit_test',
        ]);

        return $client;
    }

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
    }

    /**
     * Called once from the first test (ContactControllerTest)
     * @throws \Doctrine\ORM\Tools\ToolsException
     */
    public function loadTestDatabase()
    {
        fwrite(STDERR, print_r("Loading test database 'unit_test' ... ".PHP_EOL, TRUE));

        $kernel = static::createKernel();
        $kernel->boot();

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $kernel->getContainer()->get('doctrine')->getManager();

        $schemaTool = new SchemaTool($em);

        // Drop and recreate tables for all entities
        $schemaTool->dropDatabase();

        // Create the DB using migrations
        $db = $kernel->getContainer()->get('database_connection');

        $config = new Configuration($db);

        $config->setMigrationsTableName('migration_versions');
        $config->setMigrationsNamespace('Application\\Migrations');

        $appPath = $kernel->getContainer()->getParameter('kernel.root_dir');
        $config->setMigrationsDirectory($appPath.'/DoctrineMigrations');

        $config->registerMigrationsFromDirectory($config->getMigrationsDirectory());

        $migration = new Migration($config);

        try {
            $migration->migrate(null);
        } catch (\Exception $ex) {
            echo 'ERROR: ' . $ex->getMessage() . PHP_EOL;
            die();
        }

        self::runCommand('doctrine:fixtures:load -n --append');
    }

    protected function tearDown()
    {

    }

    protected static function runCommand($command)
    {
        $command = sprintf('%s --quiet', $command);

        return self::getApplication()->run(new StringInput($command));
    }

    protected static function getApplication()
    {
        if (null === self::$application) {
            $client = static::createClient();

            self::$application = new Application($client->getKernel());
            self::$application->setAutoExit(false);
        }

        return self::$application;
    }
}