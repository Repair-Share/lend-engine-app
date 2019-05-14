<?php

namespace AppBundle\Services;

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

class ScheduleHandler
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
    public function processLoanReminders()
    {

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

            $resultString .= '  '.$tenant->getName().', '.$tenantStatus.PHP_EOL;

            if ($tenantPlan == 'free') {
                $resultString .= '    ... skipping (free plan)'.PHP_EOL;
                continue;
            }

            if (!in_array($tenantStatus, ['LIVE'])) {
                $resultString .= '    ... skipping'.PHP_EOL;
                continue;
            }

            // Connect to the tenant to get loans
            try {

                $tenantEntityManager = $this->getTenantEntityManager($tenantDbSchema);

                // Set the settings class to get data from the right DB
                $this->settings->setTenant($tenant, $tenantEntityManager);

                $senderName     = $this->container->get('service.tenant')->getCompanyName();
                $replyToEmail   = $this->container->get('service.tenant')->getReplyToEmail();
                $fromEmail      = $this->container->get('service.tenant')->getSetting('from_email');
                $postmarkApiKey = $this->container->get('service.tenant')->getSetting('postmark_api_key');

                $automateThisEmail = $this->settings->getSettingValue('automate_email_loan_reminder');
                if ($automateThisEmail != 1) {
                    $resultString .= '    ... skipping : loan reminders not activated'.PHP_EOL;
                    continue;
                }

                try {

                    /** @var $loanRowRepo \AppBundle\Repository\LoanRowRepository */
                    $loanRowRepo = $tenantEntityManager->getRepository('AppBundle:LoanRow');

                    if ($dueLoanRows = $loanRowRepo->getLoanRowsDueTomorrow()) {

                        foreach ($dueLoanRows AS $loanRow) {

                            /** @var $loanRow \AppBundle\Entity\LoanRow */
                            $loan = $loanRow->getLoan();
                            $contact = $loan->getContact();
                            $item = $loanRow->getInventoryItem();

                            $resultString .= '  Loan: '.$loan->getId().' : '.$contact->getEmail(). PHP_EOL;
                            $resultString .= '  Item: '.$item->getName().PHP_EOL;
                            $resultString .= '  Due: '.$loanRow->getDueInAt()->format("Y-m-d").PHP_EOL;

                            $items = [$item];

                            try {
                                $toEmail = $contact->getEmail();

                                $client = new PostmarkClient($postmarkApiKey);

                                // Save and switch locale for sending the email
                                $sessionLocale = $this->container->get('translator')->getLocale();
                                $this->container->get('translator')->setLocale($contact->getLocale());

                                $message = $this->twig->render(
                                    'emails/loan_reminder.html.twig',
                                    array(
                                        'dueDate' => $loanRow->getDueInAt(),
                                        'items' => $items,
                                        'schema' => $tenantDbSchema
                                    )
                                );

                                $subject = $this->container->get('translator')->trans('le_email.reminder.subject', [
                                    'loanId' => $loan->getId()],
                                    'emails', $contact->getLocale()
                                );

                                $client->sendEmail(
                                    "{$senderName} <{$fromEmail}>",
                                    $toEmail,
                                    $subject,
                                    $message,
                                    null,
                                    null,
                                    true,
                                    $replyToEmail
                                );

                                // Revert locale for the UI
                                $this->container->get('translator')->setLocale($sessionLocale);

                            } catch (\Exception $generalException) {
                                $resultString .= "ERROR: Failed to send email : " . PHP_EOL . $generalException->getMessage();
                            }

                        }
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
     * @return string
     * Send an email for each membership that's expired
     */
    public function processMemberships(OutputInterface $output)
    {

        $output->writeln('Processing membership expiry notifications ...');

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

            $senderName     = $this->container->get('service.tenant')->getCompanyName();
            $replyToEmail   = $this->container->get('service.tenant')->getReplyToEmail();
            $fromEmail      = $this->container->get('service.tenant')->getSetting('from_email');
            $postmarkApiKey = $this->container->get('service.tenant')->getSetting('postmark_api_key');

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

                $automateThisEmail = $this->settings->getSettingValue('automate_email_membership');
                if ($automateThisEmail != 1) {
                    $resultString .= '    ... skipping : membership expiry not activated'.PHP_EOL;
                    continue;
                }

                try {

                    $membershipTypeRepo = $tenantEntityManager->getRepository('AppBundle:MembershipType');
                    $selfServeMemberships = $membershipTypeRepo->findBy(['isSelfServe' => true]);

                    // determine whether this account has self-serve memberships
                    $canSelfRenew = false;
                    if (count($selfServeMemberships) == 1) {
                        $canSelfRenew = true;
                    }

                    /** @var \AppBundle\Repository\MembershipRepository $membershipRepo */
                    $membershipRepo = $tenantEntityManager->getRepository('AppBundle:Membership');

                    if ($expiredMemberships = $membershipRepo->getExpiredMemberships()) {

                        foreach ($expiredMemberships AS $membership) {
                            /** @var $membership \AppBundle\Entity\Membership */

                            $resultString .= '  Contact: '.$membership->getContact()->getEmail(). PHP_EOL;
                            $resultString .= '  Expires: '.$membership->getExpiresAt()->format("Y-m-d").PHP_EOL;

                            // Expire the membership
                            $membership->setStatus(Membership::SUBS_STATUS_EXPIRED);
                            $tenantEntityManager->persist($membership);

                            // Remove active membership from contact
                            $contact = $membership->getContact();
                            $contact->setActiveMembership(null);
                            $tenantEntityManager->persist($contact);

                            // Flush the Em here
                            $tenantEntityManager->flush();

                            if ($toEmail = $contact->getEmail()) {

                                try {

                                    $emailClient = new PostmarkClient($postmarkApiKey);

                                    // Save and switch locale for sending the email
                                    $sessionLocale = $this->container->get('translator')->getLocale();
                                    $this->container->get('translator')->setLocale($contact->getLocale());

                                    $message = $this->twig->render(
                                        'emails/membership_expiry.html.twig',
                                        array(
                                            'expiresAt' => $membership->getExpiresAt(),
                                            'canSelfRenew' => $canSelfRenew,
                                            'tenant' => $tenant
                                        )
                                    );

                                    $subject = $this->container->get('translator')->trans('le_email.membership_expired.subject', [], 'emails', $contact->getLocale());

                                    $emailClient->sendEmail(
                                        "{$senderName} <{$fromEmail}>",
                                        $toEmail,
                                        $subject,
                                        $message,
                                        null,
                                        null,
                                        true,
                                        $replyToEmail
                                    );

                                    // Revert locale for the UI
                                    $this->container->get('translator')->setLocale($sessionLocale);

                                } catch (\Exception $generalException) {
                                    $resultString .= "ERROR: Failed to send email : " . $generalException->getMessage();
                                }
                            }

                        }
                    }

                } catch(\PDOException $ex) {
                    $resultString .= "ERROR: Failed to query" . PHP_EOL;
                    $this->logger->error("ERROR: Failed to query");

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
        $client = new PostmarkClient(getenv('SYMFONY__POSTMARK_API_KEY'));
        $client->sendEmail(
            "hello@lend-engine.com",
            'chris@lend-engine.com',
            "Membership expiry log / {$timeElapsed} sec.",
            nl2br($resultString)
        );

        return $resultString;

    }

    /**
     * @return string
     * Send an email for each reservation that is due for pickup tomorrow
     */
    public function processReservationReminders()
    {

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

            $senderName     = $this->container->get('service.tenant')->getCompanyName();
            $replyToEmail   = $this->container->get('service.tenant')->getReplyToEmail();
            $fromEmail      = $this->container->get('service.tenant')->getSetting('from_email');
            $postmarkApiKey = $this->container->get('service.tenant')->getSetting('postmark_api_key');

            $resultString .= '  '.$tenant->getName().', '.$tenantStatus;

            if ($tenantPlan == 'free') {
                $resultString .= '    ... skipping (free plan)'.PHP_EOL;
                continue;
            }

            $resultString .= PHP_EOL;

            // Connect to the tenant to get memberships that need to expire
            try {

                $tenantEntityManager = $this->getTenantEntityManager($tenantDbSchema);

                // Set the settings class to get data from the right DB
                $this->settings->setTenant($tenant, $tenantEntityManager);

                $automateThisEmail = $this->settings->getSettingValue('automate_email_reservation_reminder');
                if ($automateThisEmail != 1) {
                    $resultString .= '    ... skipping : reservation reminders not activated'.PHP_EOL;
                    continue;
                }

                try {

                    /** @var $loanRepo \AppBundle\Repository\LoanRepository */
                    $loanRepo = $tenantEntityManager->getRepository('AppBundle:Loan');

                    if ($dueReservations = $loanRepo->getReservationsDue()) {

                        foreach ($dueReservations AS $loan) {

                            /** @var $loan \AppBundle\Entity\Loan */
                            $contact = $loan->getContact();

                            $resultString .= '  Loan: '.$loan->getId().' : '.$contact->getEmail(). PHP_EOL;
                            $resultString .= '  Due: '.$loan->getTimeOut()->format("Y-m-d").PHP_EOL;

                            try {
                                $toEmail = $contact->getEmail();
                                $client = new PostmarkClient($postmarkApiKey);

                                // Save and switch locale for sending the email
                                $sessionLocale = $this->container->get('translator')->getLocale();
                                $this->container->get('translator')->setLocale($contact->getLocale());

                                $message = $this->twig->render(
                                    'emails/reservation_reminder.html.twig',
                                    array(
                                        'dueDate' => $loan->getTimeOut(),
                                        'loanId' => $loan->getId(),
                                        'loanRows' => $loan->getLoanRows(),
                                        'schema' => $tenantDbSchema
                                    )
                                );

                                $subject = $this->container->get('translator')->trans('le_email.reservation_reminder.subject',
                                    ['loanId' => $loan->getId()],
                                    'emails', $contact->getLocale()
                                );

                                $client->sendEmail(
                                    "{$senderName} <{$fromEmail}>",
                                    $toEmail,
                                    $subject,
                                    $message,
                                    null,
                                    null,
                                    true,
                                    $replyToEmail
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
        $client = new PostmarkClient(getenv('SYMFONY__POSTMARK_API_KEY'));
        $client->sendEmail(
            "hello@lend-engine.com",
            'chris@lend-engine.com',
            "Reservation reminders log / {$timeElapsed} sec.",
            nl2br($resultString)
        );

        return $resultString;

    }

    /**
     * Send an email to each member where a loan contains overdue items
     * @return string
     */
    public function processOverdueEmails()
    {
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

            $senderName     = $this->container->get('service.tenant')->getCompanyName();
            $replyToEmail   = $this->container->get('service.tenant')->getReplyToEmail();
            $fromEmail      = $this->container->get('service.tenant')->getSetting('from_email');
            $postmarkApiKey = $this->container->get('service.tenant')->getSetting('postmark_api_key');

            $resultString .= '  '.$tenant->getName().', '.$tenantStatus;

            if ($tenantPlan == 'free') {
                $resultString .= '    ... skipping (free plan)'.PHP_EOL;
                continue;
            }

            $resultString .= PHP_EOL;

            // Connect to the tenant to get overdue loan rows
            try {

                $tenantEntityManager = $this->getTenantEntityManager($tenantDbSchema);

                // Set the settings class to get data from the right DB
                $this->settings->setTenant($tenant, $tenantEntityManager);

                $overdueDays = $this->settings->getSettingValue('automate_email_overdue_days');
                if ($overdueDays == null || $overdueDays == 0) {
                    $resultString .= '    ... skipping : overdue reminders not activated'.PHP_EOL;
                    continue;
                }

                $resultString .= " ... finding items {$overdueDays} days overdue".PHP_EOL;

                try {

                    /** @var $loanRowRepo \AppBundle\Repository\LoanRowRepository */
                    $loanRowRepo = $tenantEntityManager->getRepository('AppBundle:LoanRow');

                    if ($overdueLoanRows = $loanRowRepo->getOverdueItems($overdueDays)) {

                        foreach ($overdueLoanRows AS $loanRow) {

                            /** @var $loanRow \AppBundle\Entity\LoanRow */
                            $loan    = $loanRow->getLoan();
                            $contact = $loan->getContact();

                            $resultString .= '  Loan: '.$loan->getId().' : '.$contact->getEmail(). PHP_EOL;
                            $resultString .= '  Due: '.$loanRow->getDueInAt()->format("Y-m-d").PHP_EOL;

                            try {
                                $toEmail = $contact->getEmail();
                                $client = new PostmarkClient($postmarkApiKey);

                                // Save and switch locale for sending the email
                                $sessionLocale = $this->container->get('translator')->getLocale();
                                $this->container->get('translator')->setLocale($contact->getLocale());

                                $message = $this->twig->render(
                                    'emails/overdue_reminder.html.twig',
                                    array(
                                        'loanId'   => $loan->getId(),
                                        'loanRows' => $loan->getLoanRows(),
                                        'schema'   => $tenantDbSchema,
                                        'tenant'   => $tenant
                                    )
                                );

                                $subject = $this->container->get('translator')->trans('le_email.overdue.subject',
                                    ['loanId' => $loan->getId()],
                                    'emails', $contact->getLocale()
                                );

                                $client->sendEmail(
                                    "{$senderName} <{$fromEmail}>",
                                    $toEmail,
                                    $subject,
                                    $message,
                                    null,
                                    null,
                                    true,
                                    $replyToEmail
                                );

                                $note = new Note();
                                $note->setContact($contact);
                                $note->setText("Automation : sent overdue email");
                                $tenantEntityManager->persist($note);

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

                $tenantEntityManager->flush();
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
        $client = new PostmarkClient(getenv('SYMFONY__POSTMARK_API_KEY'));
        $client->sendEmail(
            "hello@lend-engine.com",
            'chris@lend-engine.com',
            "Overdue emails log / {$timeElapsed} sec.",
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