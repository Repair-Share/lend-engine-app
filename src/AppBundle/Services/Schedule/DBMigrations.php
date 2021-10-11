<?php

namespace AppBundle\Services\Schedule;

use AppBundle\Services\Contact\ContactService;
use AppBundle\Services\EmailService;
use AppBundle\Services\SettingsService;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Driver\Mysqli\Driver;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use \Doctrine\DBAL\Connection;

class DBMigrations
{
    /** @var \Twig_Environment */
    private $twig;

    /** @var Container */
    private $container;

    /** @var \AppBundle\Services\SettingsService */
    private $settings;

    /** @var \AppBundle\Services\Contact\ContactService */
    private $contactService;

    /** @var EmailService */
    private $emailService;

    /** @var EntityManager */
    private $em;

    /** @var string */
    private $serverName;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        \Twig_Environment $twig,
        Container $container,
        SettingsService $settings,
        ContactService $contactService,
        EmailService $emailService,
        EntityManager $em,
        LoggerInterface $logger
    ) {
        $this->twig           = $twig;
        $this->container      = $container;
        $this->settings       = $settings;
        $this->contactService = $contactService;
        $this->emailService   = $emailService;
        $this->em             = $em;
        $this->logger         = $logger;

        if (!$this->serverName = getenv('LE_SERVER_NAME')) {
            throw new \Exception("LE_SERVER_NAME is not defined");
        }
    }

    /**
     * @param $server
     * @return string
     * @throws \Doctrine\ORM\ORMException
     */
    public function migrate($server)
    {
        // Help https://symfony.com/doc/3.4/console.html
        $expectedMigrationVersion = '20210927134214';

        $resultString = '';

        if (!$server) {
            throw new \Exception('Missing server name.');
        }

        $url = getenv('RDS_URL');

        if ($url) {
            $dbParts  = parse_url($url);
            $dbServer = $dbParts['host'];
            $username = $dbParts['user'];
            $password = $dbParts['pass'];
        } else {
            $dbServer = '127.0.0.1';
            $username = getenv('DEV_DB_USER');
            $password = getenv('DEV_DB_PASS');
        }

        $basicSql = '
        
            select
                db_schema,
                schema_version
                
            from
                _core.account
                
            where
                schema_version <> :expectedVersion
                and server_name = :serverName
        
        ';

        $sqlParams = [
            ':expectedVersion' => $expectedMigrationVersion,
            ':serverName'      => $server
        ];

        $stmt = $this->em->getConnection()->prepare($basicSql . ' limit 10');

        $stmt->execute($sqlParams);

        $schemas = $stmt->fetchAll();

        foreach ($schemas as $schema) {

            $tenantDbSchema        = $schema['db_schema'];
            $tenantDbSchemaVersion = $schema['schema_version'];

            $driver = new Driver();
            $params = [
                'driver'   => 'pdo_mysql',
                'host'     => $dbServer,
                'port'     => 3306,
                'dbname'   => $tenantDbSchema,
                'user'     => $username,
                'password' => $password
            ];
            $conn   = new Connection($params, $driver);
            $config = new Configuration($conn);

            $config->setMigrationsTableName('migration_versions');
            $config->setMigrationsNamespace('Application\\Migrations');
            $config->setMigrationsDirectory('app/DoctrineMigrations');
            $config->registerMigrationsFromDirectory($config->getMigrationsDirectory());

            $latestVersion = $config->getLatestVersion();

            $migration = new Migration($config);

            if ($tenantDbSchemaVersion !== $latestVersion) { // Migration required
                $migration->migrate();

                $resultString .= 'Migrated ' . $tenantDbSchema . ' schema to ' . $latestVersion . PHP_EOL;
            }

            // Update the tenant's schema version
            $raw = '
                update 
                    _core.account 
                     
                set
                    schema_version = :version
                     
                where
                    db_schema = :schemap
                    
            ';

            $s = $this->em->getConnection()->prepare($raw);

            $s->execute([
                ':version' => $latestVersion,
                ':schemap' => $tenantDbSchema
            ]);
        }

        return $resultString;

    }

}