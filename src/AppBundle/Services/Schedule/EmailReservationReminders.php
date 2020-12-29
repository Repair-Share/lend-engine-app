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

class EmailReservationReminders
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
                                EntityManager $em, LoggerInterface $logger)
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
     * Send an email for each reservation that is due for pickup tomorrow
     */
    public function processReservationReminders()
    {
        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->container->get('service.tenant');

        $startTime = microtime(true);

        $resultString = '';

        $repo = $this->em->getRepository('AppBundle:Tenant');
        $tenants = $repo->findBy(['server' => $this->serverName, 'status' => 'LIVE']);

        $resultString .= 'Number of tenants = '.count($tenants).PHP_EOL;

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

            // Connect to the tenant to get reservations that are due to start tomorrow
            try {

                $tenantEntityManager = $this->getTenantEntityManager($tenantDbSchema);

                // Set the settings class to get data from the right DB
                $this->settings->setTenant($tenant, $tenantEntityManager);
                $tenantService->setTenant($tenant);

                $senderName     = $tenantService->getCompanyName();
                $fromEmail      = $tenantService->getSenderEmail();
                $replyToEmail   = $tenantService->getReplyToEmail();
                $postmarkApiKey = $tenantService->getSetting('postmark_api_key');

                $remindDays = (int)$this->settings->getSettingValue('automate_email_reservation_reminder');
                if (!$remindDays) {
                    $resultString .= '    ... skipping : reservation reminders not activated'.PHP_EOL;
                    continue;
                }

                try {

                    /** @var $loanRepo \AppBundle\Repository\LoanRepository */
                    $loanRepo = $tenantEntityManager->getRepository('AppBundle:Loan');

                    if ($dueReservations = $loanRepo->getReservationsDue($remindDays)) {

                        foreach ($dueReservations AS $loan) {

                            /** @var $loan \AppBundle\Entity\Loan */
                            $contact = $loan->getContact();

                            $resultString .= '  Loan: '.$loan->getId().' : '.$contact->getEmail(). PHP_EOL;
                            $resultString .= '  Due: '.$loan->getTimeOut()->format("Y-m-d").PHP_EOL;

                            try {
                                $toEmail = $contact->getEmail();

                                $this->contactService->setTenant($tenant, $tenantEntityManager);
                                $token = $this->contactService->generateAccessToken($contact);

                                $loginUri = $tenant->getDomain(true);
                                $loginUri .= '/access?t='.$token.'&e='.urlencode($contact->getEmail());
                                $loginUri .= '&r=/loan/'.$loan->getId();

                                // Save and switch locale for sending the email
                                $sessionLocale = $this->container->get('translator')->getLocale();
                                $this->container->get('translator')->setLocale($contact->getLocale());

                                $message = $this->twig->render(
                                    'emails/reservation_reminder.html.twig',
                                    array(
                                        'dueDate' => $loan->getTimeOut(),
                                        'loanId' => $loan->getId(),
                                        'loanRows' => $loan->getLoanRows(),
                                        'schema' => $tenantDbSchema,
                                        'loginUri' => $loginUri
                                    )
                                );

                                if (!$subject = $this->settings->getSettingValue('email_reservation_reminder_subject')) {
                                    $subject = $this->container->get('translator')->trans('le_email.reservation_reminder.subject',
                                        ['loanId' => $loan->getId()],
                                        'emails', $contact->getLocale()
                                    );
                                }

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

                } catch(\PDOException $ex) {
                    $resultString .= "ERROR: Failed to query" . PHP_EOL;
                }

                $tenantEntityManager->getConnection()->close();

            } catch(\PDOException $ex) {
                echo "ERROR: Couldn't connect to database {$tenantDbSchema}" . PHP_EOL;
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
//            "Reservation reminders log / {$timeElapsed} sec.",
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