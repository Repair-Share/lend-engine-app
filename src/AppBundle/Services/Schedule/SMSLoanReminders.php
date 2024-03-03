<?php

namespace AppBundle\Services\Schedule;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use AppBundle\Services\Contact\ContactService;
use AppBundle\Services\EmailService;
use AppBundle\Services\SettingsService;
use Doctrine\ORM\EntityManager;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Doctrine\DBAL\Driver\PDOException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

class SMSLoanReminders
{
    /** @var \Twig_Environment  */
    private $twig;

    /** @var Container  */
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

    public function __construct(\Twig_Environment $twig,
                                Container $container,
                                SettingsService $settings,
                                ContactService $contactService,
                                EmailService $emailService,
                                EntityManager $em,
                                LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->container = $container;
        $this->settings = $settings;
        $this->contactService = $contactService;
        $this->emailService = $emailService;
        $this->em = $em;
        $this->logger = $logger;

        if (!$this->serverName = getenv('LE_SERVER_NAME')) {
            throw new \Exception("LE_SERVER_NAME is not defined");
        }
    }

    /**
     * @return string
     */
    public function processLoanSMS()
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
            $tenantPlan     = $tenant->getPlan();

            $resultString .= $tenant->getName().', '.$tenantStatus.PHP_EOL;

            if ($tenantPlan == 'free') {
                $resultString .= '    ... skipping (free plan)'.PHP_EOL;
                continue;
            }

            if (!in_array($tenantStatus, ['LIVE'])) {
                $resultString .= '    ... skipping'.PHP_EOL;
                continue;
            }

            // Connect to the tenant to get loan items which are due back tomorrow
            try {

                $tenantEntityManager = $this->getTenantEntityManager($tenantDbSchema);

                // Set the settings class to get data from the right DB
                $this->settings->setTenant($tenant, $tenantEntityManager);
                $tenantService->setTenant($tenant);

                $senderName     = $tenantService->getCompanyNameAsSender();
                $replyToEmail   = $tenantService->getReplyToEmail();
                $fromEmail      = $tenantService->getSenderEmail();
                $postmarkApiKey = $tenantService->getSetting('postmark_api_key');

                $remindBeforeNDays = (int)$this->settings->getSettingValue('automate_email_loan_reminder');
                if (!$remindBeforeNDays) {
                    $resultString .= '    ... skipping : loan reminders not activated'.PHP_EOL;
                    continue;
                }

                try {

                    /** @var $loanRowRepo \AppBundle\Repository\LoanRowRepository */
                    $loanRowRepo = $tenantEntityManager->getRepository('AppBundle:LoanRow');

                    if ($dueLoanRows = $loanRowRepo->getLoanRowsDueInXDays($remindBeforeNDays)) {

                        foreach ($dueLoanRows AS $loanRow) {

                            /** @var $loanRow \AppBundle\Entity\LoanRow */
                            $loan = $loanRow->getLoan();
                            $contact = $loan->getContact();
                            $item = $loanRow->getInventoryItem();

                            $resultString .= '  Loan: '.$loan->getId().' : '.$contact->getEmail(). PHP_EOL;
                            $resultString .= '  Item: '.$item->getName().PHP_EOL;
                            $resultString .= '  Due: '.$loanRow->getDueInAt()->format("Y-m-d").PHP_EOL;

                            $items = [$item];

                            if ($toEmail = $contact->getEmail()) {

                                $this->contactService->setTenant($tenant, $tenantEntityManager);
                                $token = $this->contactService->generateAccessToken($contact);

                                try {

                                    // Save and switch locale for sending the email
                                    $sessionLocale = $this->container->get('translator')->getLocale();
                                    $this->container->get('translator')->setLocale($contact->getLocale());

                                    $loginUri = $tenant->getDomain(true);
                                    $loginUri .= '/access?t='.$token.'&e='.urlencode($contact->getEmail());
                                    $loginUri .= '&r=/loan/'.$loan->getId();

                                    $message = $this->twig->render(
                                        'emails/loan_reminder.html.twig',
                                        [
                                            'dueDate' => $loanRow->getDueInAt(),
                                            'items' => $items,
                                            'schema' => $tenantDbSchema,
                                            'loginUri' => $loginUri
                                        ]
                                    );

                                    $subject = $this->container->get('translator')->trans('le_email.reminder.subject', [
                                        '%loanId%' => $loan->getId()],
                                        'emails', $contact->getLocale()
                                    );

                                    $this->emailService->postmarkApiKey = $postmarkApiKey;
                                    $this->emailService->senderName = $senderName;
                                    $this->emailService->fromEmail = $fromEmail;
                                    $this->emailService->replyToEmail = $replyToEmail;
                                    $this->emailService->send(
                                        $contact->getEmail(), $contact->getName(), $subject, $message
                                    );

                                    // Revert locale for the UI
                                    $this->container->get('translator')->setLocale($sessionLocale);

                                } catch (\Exception $generalException) {
                                    $resultString .= "ERROR: Failed to send email : " . PHP_EOL . $generalException->getMessage();
                                }

                            }

                        }
                    } else {
                        $resultString .= '  No loan rows due in '.$remindBeforeNDays.' days '.PHP_EOL;
                    }

                    $tenantEntityManager->getConnection()->close();

                } catch(\PDOException $ex) {
                    $resultString .= "ERROR: Failed to query" . PHP_EOL;
                }

            } catch(\PDOException $ex) {
                echo "ERROR: Couldn't connect to database {$tenantDbSchema}" . PHP_EOL;
            }

            $timeElapsed = number_format(microtime(true) - $startTime, 4);
            $resultString .= '  T: '.$timeElapsed.PHP_EOL;

        }

        $timeElapsed = number_format(microtime(true) - $startTime, 4);
        $resultString .= '  Total T: '.$timeElapsed.PHP_EOL;

        // And then finally send a log.
        $client = new PostmarkClient(getenv('SYMFONY__POSTMARK_API_KEY'));
        $client->sendEmail(
            "hello@lend-engine.com",
            'chris@lend-engine.com',
            "Loan reminder log / {$timeElapsed} sec.",
            nl2br($resultString)
        );

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