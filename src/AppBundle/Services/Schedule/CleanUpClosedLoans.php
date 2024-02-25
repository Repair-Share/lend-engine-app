<?php
// +++ KB-MAN 2024/02/25 Clean up closed loans and outdated reservations
/**
 * Addon for Digibank Mechelen
 */
namespace AppBundle\Services\Schedule;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use AppBundle\Services\Loan\LoanService;
use AppBundle\Services\EmailService;
use AppBundle\Services\SettingsService;
use Doctrine\ORM\EntityManager;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Doctrine\DBAL\Driver\PDOException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

class CleanUpClosedLoans
{
    /** @var \Twig_Environment  */
    private $twig;

    /** @var Container  */
    private $container;

    /** @var \AppBundle\Services\SettingsService */
    private $settings;

    /** @var \AppBundle\Services\Loan\LoanService */
    private $loanService;

    /** @var EmailService */
    private $emailService;

    /** @var EntityManager */
    private $em;

    /** @var string */
    private $serverName;

    /** @var LoggerInterface */
    private $logger;

    // Max date_in value for loan with status 'RESERVED' for loan to be removed
    private $maxReservedDateInDate;
    // Min date_in value for loan with status 'CLOSED' for loan to be removed
    private $maxClosedLoanDateInDate;

    public function __construct(\Twig_Environment $twig,
                                Container $container,
                                SettingsService $settings,
                                LoanService $loanService,
                                EmailService $emailService,
                                EntityManager $em, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->container = $container;
        $this->settings = $settings;
        $this->loanService = $loanService;
        $this->emailService = $emailService;
        $this->em = $em;
        $this->logger = $logger;
        // Get the current year
        $currentYear = date("Y");

        $this->maxReservedDateInDate = ($currentYear - 2) . "-12-31";
        $this->maxClosedLoanDateInDate = ($currentYear - 2) . "-12-31";

        if (!$this->serverName = getenv('LE_SERVER_NAME')) {
            throw new \Exception("LE_SERVER_NAME is not defined");
        }
    }

    /**
     * @return string
     * Delete closed loans older than x years, and reservation loans older than y years
     */
    public function processLoans(OutputInterface $output)
    {

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->container->get('service.tenant');

        $output->writeln('Processing closed loans clean up ...');

        $startTime = microtime(true);

        $resultString = '';

        $repo = $this->em->getRepository('AppBundle:Tenant');
        $tenants = $repo->findBy(['server' => $this->serverName, 'status' => 'LIVE']);

        $resultString .= 'Number of tenants = '.count($tenants).PHP_EOL;
        $this->logger->info('Number of tenants = '.count($tenants));

        foreach ($tenants AS $tenant) {

            /** @var $tenant \AppBundle\Entity\Tenant */
            $tenantDbSchema = $tenant->getDbSchema();
            $tenantStatus   = $tenant->getStatus();
            $tenantPlan     = $tenant->getPlan();

            $resultString .= '  '.$tenant->getName().', '.$tenantStatus;

            if ($tenantPlan == 'free') {
                $resultString .= '    ... skipping (free plan)'.PHP_EOL;
                continue;
            }

            $resultString .= PHP_EOL;
            $this->logger->info("Connecting to {$tenantDbSchema}");

            // Connect to the tenant to get memberships that need to expire
            try {

                $this->logger->info("Connected");

                $tenantEntityManager = $this->getTenantEntityManager($tenantDbSchema);

                // Set the settings class to get data from the right DB
                $this->settings->setTenant($tenant, $tenantEntityManager);
                $tenantService->setTenant($tenant);
                $this->loanService->setTenant($tenant, $tenantEntityManager);

                $senderName     = $tenantService->getSetting('org_name');
                $fromEmail      = $tenantService->getSenderEmail();
                $replyToEmail   = $tenantService->getReplyToEmail();
                $postmarkApiKey = $tenantService->getSetting('postmark_api_key');

                $loanRepo = $tenantEntityManager->getRepository('AppBundle:Loan');

                $closedLoans = $loanRepo->findLoans(0, 10, [
                    'status' => 'CLOSED', 
                    'date_to' => $this->maxClosedLoanDateInDate, 
                    'date_type' => 'date_in'
                ]);
                if (is_array($closedLoans) && isset($closedLoans['totalResults'])) {
                    $this->logger->info("Found CLOSED loans : " . $closedLoans['totalResults']);
                    $resultString .= "INFO: Found CLOSED loans : " . $closedLoans['totalResults'] . PHP_EOL;
                } else {
                    $this->logger->error("Find loans query for CLOSED loans failed");
                    $resultString .= "ERROR: Find loans query for CLOSED loans failed" . PHP_EOL;
                    continue;
                }

                $outdatedReservations = $loanRepo->findLoans(0, 10, [
                    'status' => 'RESERVED', 
                    'date_to' => $this->maxReservedDateInDate, 
                    'date_type' => 'date_in'
                ]);
                if (is_array($outdatedReservations) && isset($outdatedReservations['totalResults'])) {
                    $this->logger->info("Found outdated reservations (status RESERVED) : " . $outdatedReservations['totalResults']);
                    $resultString .= "Found outdated reservations (status RESERVED) : " . $outdatedReservations['totalResults'] . PHP_EOL;
                } else {
                    $this->logger->error("Find loans query for outdated reservations failed");
                    $resultString .= "ERROR: Find loans query for outdated reservations failed" . PHP_EOL;
                    continue;
                }
                try {
                    foreach($outdatedReservations['data'] as $reservation){
                        $resultString .= "Removing outdated reservation with id : " . $reservation->getId() . PHP_EOL;
                        $this->loanService->deleteLoan($reservation->getId());
                    }
                    foreach($closedLoans['data'] as $closedLoan){
                        $resultString .= "Removing closed loan with id : " . $closedLoan->getId() . PHP_EOL;
                        $this->loanService->deleteLoan($closedLoan->getId());
                    }
                } catch (\Exception $e2) {
                    $resultString .= "ERROR 235: ".$e2->getMessage().PHP_EOL;
                }                    

                $tenantEntityManager->getConnection()->close();

            } catch(\PDOException $ex) {
                echo "ERROR: Couldn't connect to database {$tenantDbSchema}" . PHP_EOL;
                $this->logger->error("ERROR: Couldn't connect to database {$tenantDbSchema}");
            }

            $tenantConnection = null;

            $timeElapsed = number_format(microtime(true) - $startTime, 4);
            $resultString .= '  T: '.$timeElapsed.PHP_EOL;

        }

        $timeElapsed = number_format(microtime(true) - $startTime, 4);
        $resultString .= '  Total T: '.$timeElapsed.PHP_EOL;

        // And then finally send a log.
//        $client = new PostmarkClient(getenv('SYMFONY__POSTMARK_API_KEY'));
//        $client->sendEmail(
//            "hello@lend-engine.com",
//            'chris@lend-engine.com',
//            "Clean up closed loans log / {$timeElapsed} sec.",
//            nl2br($resultString)
//        );

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
// --- KB-MAN 2024/02/25 Clean up closed loans and outdated reservations