<?php

namespace AppBundle\Services\Schedule;

use AppBundle\Entity\Maintenance;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use AppBundle\Services\SettingsService;
use Doctrine\ORM\EntityManager;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Doctrine\DBAL\Driver\PDOException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

class UpdateMaintenance
{
    /** @var \Twig_Environment  */
    private $twig;

    /** @var Container  */
    private $container;

    /** @var \AppBundle\Services\SettingsService */
    private $settings;

    /** @var EntityManager */
    private $em;

    private $serverName;

    private $logger;

    public function __construct(\Twig_Environment $twig, Container $container, SettingsService $settings, EntityManager $em, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->container = $container;
        $this->settings = $settings;
        $this->em = $em;
        $this->logger = $logger;

        if (!$this->serverName = getenv('LE_SERVER_NAME')) {
            throw new \Exception("LE_SERVER_NAME is not defined");
        }
    }

    /**
     * @return string
     */
    public function updateMaintenance()
    {
        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->container->get('service.tenant');

        $startTime = microtime(true);

        $resultString = '';

        // Connect to core and get tenants on this server
        $repo = $this->em->getRepository('AppBundle:Tenant');
        $tenants = $repo->findBy(['server' => $this->serverName, 'status' => 'LIVE']);

        $resultString .= 'Number of tenants = '.count($tenants).PHP_EOL;

        foreach ($tenants AS $tenant) {

            /** @var $tenant \AppBundle\Entity\Tenant */
            $tenantDbSchema = $tenant->getDbSchema();
            $tenantStatus   = $tenant->getStatus();

            $resultString .= $tenant->getName().', '.$tenantStatus.PHP_EOL;

            if (!in_array($tenantStatus, ['LIVE', 'TRIAL'])) {
                $resultString .= '    ... skipping'.PHP_EOL;
                continue;
            }

            // Connect to the tenant to get all maintenance events
            try {

                $tenantEntityManager = $this->getTenantEntityManager($tenantDbSchema);

                // Set the settings class to get data from the right DB
                $this->settings->setTenant($tenant, $tenantEntityManager);
                $tenantService->setTenant($tenant);

                $senderName     = $tenantService->getSetting('org_name');
                $replyToEmail   = $tenantService->getReplyToEmail();
                $fromEmail      = $tenantService->getSenderEmail();
                $postmarkApiKey = $tenantService->getSetting('postmark_api_key');
                $client = new PostmarkClient($postmarkApiKey);

                /** @var \AppBundle\Repository\MaintenanceRepository $maintenanceRepo */
                $maintenanceRepo = $tenantEntityManager->getRepository('AppBundle:Maintenance');

                /** @var \AppBundle\Repository\ContactRepository $contactRepo */
                $contactRepo = $tenantEntityManager->getRepository('AppBundle:Contact');

                $overdueMaintenance = [];
                $maintenanceByOwner = [];

                try {

                    if ($overdueMaintenance = $maintenanceRepo->getOverdueByDate()) {
                        /** @var \AppBundle\Entity\Maintenance $m */
                        foreach ($overdueMaintenance AS $m) {

                            // Set it as overdue
                            $m->setStatus(Maintenance::STATUS_OVERDUE);

                            $tenantEntityManager->persist($m);
                            $tenantEntityManager->flush();

                            $resultString .= $m->getId().PHP_EOL;

                        }
                    }

                } catch(\PDOException $ex) {
                    $resultString .= "ERROR: Failed to query" . PHP_EOL;
                }

                // Send an email to admin

                if ($overdueMaintenance) {

                    try {

                        $toEmail = $tenantService->getSetting('org_email');
                        $message = $this->twig->render(
                            'emails/maintenance_due.html.twig',
                            [
                                'maintenance' => $overdueMaintenance,
                                'domain' => $tenantService->getAccountDomain()
                            ]
                        );

                        $client->sendEmail(
                            "{$senderName} <{$fromEmail}>",
                            $toEmail,
                            "Maintenance is due for one or more library items",
                            $message,
                            null,
                            null,
                            true,
                            $replyToEmail
                        );

                        $resultString .= "Sent email to ".$toEmail.PHP_EOL;

                    } catch(\PDOException $ex) {
                        $resultString .= "ERROR: Failed to email " . PHP_EOL;
                    }

                    /** @var \AppBundle\Entity\Maintenance $m */
                    foreach ($overdueMaintenance AS $m) {
                        if ($owner = $m->getAssignedTo()) {
                            if (!isset($maintenanceByOwner[$owner->getId()])) {
                                $maintenanceByOwner[$owner->getId()] = [];
                            }
                            $maintenanceByOwner[$owner->getId()][] = $m;
                        }
                    }

                }


                foreach ($maintenanceByOwner AS $ownerId => $maintenances) {

                    if ($contact = $contactRepo->find($ownerId)) {

                        try {

                            $message = $this->twig->render(
                                'emails/maintenance_due.html.twig',
                                [
                                    'assignee' => $contact,
                                    'maintenance' => $maintenances,
                                    'domain' => $tenantService->getAccountDomain()
                                ]
                            );

                            $client->sendEmail(
                                "{$senderName} <{$fromEmail}>",
                                $contact->getEmail(),
                                $contact->getFirstName()." : maintenance is due for one or more library items",
                                $message,
                                null,
                                null,
                                true,
                                $replyToEmail
                            );

                            $resultString .= "Sent email to assignee ".$contact->getEmail().PHP_EOL;

                        } catch(\PDOException $ex) {
                            $resultString .= "ERROR: Failed to email to assignee " . PHP_EOL;
                        }

                    }

                }

                $tenantEntityManager->getConnection()->close();

            } catch(\PDOException $ex) {
                echo "ERROR: Couldn't connect to database {$tenantDbSchema}" . PHP_EOL;
            }


            $timeElapsed = number_format(microtime(true) - $startTime, 4);
            $resultString .= '  T: '.$timeElapsed.PHP_EOL;

        }

        $timeElapsed = number_format(microtime(true) - $startTime, 4);
        $resultString .= '  Total T: '.$timeElapsed.PHP_EOL;

        return $resultString;

    }

    /**
     * @param $dbName
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    private function getTenantEntityManager($dbName)
    {

        if ($url = getenv('RDS_URL')) {
            $dbparts = parse_url($url);
            $server   = $dbparts['host'];
            $username = $dbparts['user'];
            $password = $dbparts['pass'];
        } else {
            $server = '127.0.0.1';
            $username = getenv('DEV_DB_USER');
            $password = getenv('DEV_DB_PASS');
        }

        $conn = array(
            'driver'   => 'pdo_mysql',
            'port'     => 3306,
            'host'     => $server,
            'user'     => $username,
            'password' => $password,
            'dbname'   => $dbName
        );

        $em = EntityManager::create(
            $conn,
            $this->em->getConfiguration(),
            $this->em->getEventManager()
        );

        return $em;
    }

}