<?php

namespace AppBundle\Services\Schedule;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Note;
use AppBundle\Services\Contact\ContactService;
use AppBundle\Services\SettingsService;
use Doctrine\ORM\EntityManager;
use Postmark\PostmarkClient;
use Postmark\Models\PostmarkException;
use Doctrine\DBAL\Driver\PDOException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;

class ExpireMemberships
{
    /** @var \Twig_Environment  */
    private $twig;

    /** @var Container  */
    private $container;

    /** @var \AppBundle\Services\SettingsService */
    private $settings;

    /** @var \AppBundle\Services\Contact\ContactService */
    private $contactService;

    /** @var EntityManager */
    private $em;

    /** @var string */
    private $serverName;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(\Twig_Environment $twig,
                                Container $container,
                                SettingsService $settings, ContactService $contactService,
                                EntityManager $em, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->container = $container;
        $this->settings = $settings;
        $this->contactService = $contactService;
        $this->em = $em;
        $this->logger = $logger;

        if (!$this->serverName = getenv('LE_SERVER_NAME')) {
            throw new \Exception("LE_SERVER_NAME is not defined");
        }
    }

    /**
     * @return string
     * Send an email for each membership that's expired
     */
    public function processMemberships(OutputInterface $output)
    {

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->container->get('service.tenant');

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

                $senderName     = $tenantService->getSetting('org_name');
                $fromEmail      = $tenantService->getSenderEmail();
                $replyToEmail   = $tenantService->getReplyToEmail();
                $postmarkApiKey = $tenantService->getSetting('postmark_api_key');

                $sendEmailReminders = $this->settings->getSettingValue('automate_email_membership');

                $membershipTypeRepo = $tenantEntityManager->getRepository('AppBundle:MembershipType');
                $selfServeMemberships = $membershipTypeRepo->findBy(['isSelfServe' => true]);

                // Determine whether this account has self-serve memberships
                $canSelfRenew = false;
                if (count($selfServeMemberships) > 0) {
                    $canSelfRenew = true;
                }

                /** @var \AppBundle\Repository\MembershipRepository $membershipRepo */
                $membershipRepo = $tenantEntityManager->getRepository('AppBundle:Membership');

                // Get all memberships which have an expiry date in the past but are still active
                if ($expiredMemberships = $membershipRepo->getExpiredMemberships()) {

                    /** @var $membership \AppBundle\Entity\Membership */
                    foreach ($expiredMemberships AS $membership) {

                        // Save the contact
                        try {

                            $resultString .= '  Contact: '.$membership->getContact()->getEmail(). PHP_EOL;
                            $resultString .= '  Expires: '.$membership->getExpiresAt()->format("Y-m-d").PHP_EOL;

                            // Expire the membership
                            $membership->setStatus(Membership::SUBS_STATUS_EXPIRED);
                            $tenantEntityManager->persist($membership);

                            $contact = $membership->getContact();
                            $toEmail = $contact->getEmail();

                            // Remove active membership from contact
                            $contact->setActiveMembership(null);
                            $tenantEntityManager->persist($contact);

                            $resultString .= '  Expired membership for '.$membership->getContact()->getEmail(). PHP_EOL;

                            $tenantEntityManager->flush($contact);
                            $tenantEntityManager->flush($membership);

                            if ($toEmail && $sendEmailReminders == 1) {

                                try {

                                    $emailClient = new PostmarkClient($postmarkApiKey);

                                    // Save and switch locale for sending the email
                                    $sessionLocale = $this->container->get('translator')->getLocale();
                                    $this->container->get('translator')->setLocale($contact->getLocale());

                                    $this->contactService->setTenant($tenant, $tenantEntityManager);
                                    $token = $this->contactService->generateAccessToken($contact);

                                    $loginUri = $tenant->getDomain(true);
                                    $loginUri .= '/access?t='.$token.'&e='.urlencode($contact->getEmail());
                                    $loginUri .= '&r=/choose_membership';

                                    $message = $this->twig->render(
                                        'emails/membership_expiry.html.twig',
                                        [
                                            'expiresAt' => $membership->getExpiresAt(),
                                            'canSelfRenew' => $canSelfRenew,
                                            'tenant' => $tenant,
                                            'loginUri' => $loginUri
                                        ]
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
                        } catch (\Exception $e2) {
                            $resultString .= "ERROR 235: ".$e2->getMessage().PHP_EOL;
                        }

                    }
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