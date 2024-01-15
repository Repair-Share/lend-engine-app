<?php

/**
 * Called when user is logging in to ensure that any missed database migrations are run
 *
 */
namespace AppBundle\Controller;

use AppBundle\Entity\Contact;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Tenant;
use AppBundle\Services\Schedule\DBMigrations;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\OutputWriter;

use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class UpdateController extends Controller
{

    /**
     * @Route("clear_cache", name="clear_cache")
     */
    public function clearCache()
    {
        $command = $this->get('app.cache.clear');
        $dynamicEnvMode = $this->getParameter('kernel.environment');
//        $staticEnvMode = 'dev'; // to use develop mode
//        $staticEnvMode = 'prod --no-debug'; // to use production mode
        $input = new ArgvInput(array('--env=' . $dynamicEnvMode ));
        $output = new ConsoleOutput();
        $command->run($input, $output);

        $this->addFlash('success', 'Cleared cache');

        return $this->redirect($this->generateUrl('homepage'));
    }

    /**
     * This is called when a user logs in
     * Used to also clear the cache due strange Heroku behaviour where not all proxies come back with wake-up
     * @Route("update", name="auto_update")
     */
    public function updateDatabase()
    {
        if ($user = $this->getUser()) {
            if ($user->getId() > 1) {
                // Update core DB with last login time if not logging in as super admin
                $this->updateCoreLoginTime();
            }
        }

        // Make any DB updates required running database migrations
        $this->updateSchema();

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        // Mark loans as overdue
        /** @var \AppBundle\Repository\LoanRepository $repository */
        $repository = $em->getRepository('AppBundle:Loan');
        $repository->setLoansOverdue();

        // Remove historic opening hours
        /** @var \AppBundle\Services\Event\EventService $eventService */
        $eventService = $this->get('service.event');
        $eventService->removePastEvents();

        return $this->redirect($this->generateUrl('home', ['auto_updated' => true]));
    }

    /**
     * @Route("deploy", name="deploy")
     */
    public function deployNewDatabase(Request $request)
    {
        try {

            /** @var \AppBundle\Services\TenantService $tenantService */
            $tenantService = $this->container->get('service.tenant');

            if (isset($_GET['poll'])) {

                // Check that the db schema is still deploying
                // Note: $tenantService->getSchemaVersion() uses cached version, so we use
                //       $tenantService->getTenant()->getSchemaVersion() to refresh the cache
                if ($tenantService->getTenant()->getStatus() === Tenant::STATUS_DEPLOYING) {
                    throw new \Exception('DB is still deploying');
                }

            }

            if ($tenantService->getTenant()->isMigrationInProgress()) {
                throw new \Exception('DB migration is still in progress');
            }

            // We should already have an empty database created from the marketing site
            // CREATE DATABASE xxx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            // Run any migrations that need running
            $this->updateSchema(true);

            /** @var \AppBundle\Services\SettingsService $settingsService */
            $settingsService = $this->get('settings');
            $tenant = $settingsService->getTenant();

            // Complete the deployment and activate the trial
            // We can hit this URL multiple times, so don't re-set the trial
            if ($tenant->getStatus() == Tenant::STATUS_DEPLOYING) {
                $em = $this->getDoctrine()->getManager();

                $this->addAdminUser();
                $this->addUser();
                $this->setOrganisationDetails();

                $tenant->setStatus("TRIAL");
                $trialExpiresAt = new \DateTime();
                $trialExpiresAt->modify("+30 days");
                $tenant->setTrialExpiresAt($trialExpiresAt);
                $em->persist($tenant);
                $em->flush();

                $name  = $tenant->getOwnerName();
                $email = $tenant->getOwnerEmail();
                $org   = $tenant->getName();
                $accountCode = $settingsService->getTenant()->getStub();

                $this->subscribeToMailchimp($name, $email, $org, $accountCode);

                $this->notifyOfNewAccount($tenant->getName(), $tenant->getDomain());
            }

            return $this->redirect($this->generateUrl('homepage'));

        } catch (\Exception $e) {
            return $this->render('maintenance/db_deploying.html.twig');
        }
    }

    /**
     * Add the root user (to a new account)
     */
    public function addAdminUser()
    {
        $manager = $this->get('fos_user.user_manager');

        /** @var \AppBundle\Entity\Contact $user */
        $user = $manager->createUser();

        //@TODO set the primary admin email to a server env variable for flexible deployment
        $user->setFirstName('Admin');
        $user->setLastName('Admin');
        $user->setUsername('tech@lend-engine.com');
        $user->setEmail('tech@lend-engine.com');
        $user->addRole("ROLE_ADMIN");
        $user->addRole("ROLE_SUPER_USER");
        $user->setEnabled(true);

        $newPw = rand(100,1000);
        $user->setPlainPassword($newPw);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

    }

    /**
     * @return bool
     */
    private function setOrganisationDetails()
    {
        /** @var \Doctrine\DBAL\Driver\PDOConnection $db */
        $db = $this->get('database_connection');

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->get('settings');
        $tenant = $tenantService->getTenant();

        /** @var \Doctrine\DBAL\Driver\PDOStatement $s */
        $tenantName = $tenant->getName();

        $raw = "REPLACE INTO setting (setup_key, setup_value) VALUES ('org_name', :tenantName)";
        $s = $db->prepare($raw);
        $s->execute([
            ':tenantName' => $tenantName
        ]);

        $raw = "REPLACE INTO setting (setup_key, setup_value) VALUES ('org_email', '{$tenant->getOwnerEmail()}')";
        $s = $db->prepare($raw);
        $s->execute();

        return true;
    }

    /**
     * Add the first staff member (using details from _core)
     */
    public function addUser()
    {

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');

        $ownerName  = $settingsService->getTenant()->getOwnerName();
        $email = $settingsService->getTenant()->getOwnerEmail();

        $pass  = $this->generatePassword();
        $name  = explode(' ', $ownerName);

        $firstName = $name[0];
        $lastName  = '';
        if ($name[1]) {
            $lastName = $name[1];
        }

        $manager = $this->get('fos_user.user_manager');

        /** @var \AppBundle\Entity\Contact $user */
        $user = $manager->createUser();

        $user->setFirstName( $firstName );
        $user->setLastName( $lastName );
        $user->setUsername( $email );
        $user->setEmail( $email );
        $user->addRole("ROLE_ADMIN");
        $user->addRole("ROLE_SUPER_USER");
        $user->setEnabled(true);
        $user->setPlainPassword($pass);

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        try {
            $client = new PostmarkClient($this->getParameter('postmark_api_key'));
            $message = $this->renderView(
                'emails/activated.html.twig',
                [
                    'email' => $email,
                    'password' => $pass
                ]
            );
            $client->sendEmail(
                "Lend Engine <hello@lend-engine.com>",
                $email,
                "Your Lend Engine account has been activated",
                $message
            );
        } catch (\Exception $generalException) {
            $this->addFlash('error', 'Failed to send email:' . $generalException->getMessage());
        }

        $message = "Your password is {$pass} - record it now, we won't show it again. You can change it once you've logged in.";
        $this->addFlash('success', $message);

    }

    /**
     * @Route("update_schema", name="update_schema")
     */
    public function nudgeSchema()
    {
        $this->updateSchema();
        die("Updated.");
    }

    /**
     * @return bool
     */
    public function updateSchema($throwError = false)
    {
        $to = null;
        $nl = '<br>';

        /** @var \AppBundle\Services\TenantService $tenantService */
        $tenantService = $this->container->get('service.tenant');

        if ($tenantService->getTenant()->isMigrationInProgress()) {
            throw new \Exception('DB migration is still in progress');
        }

        $db = $this->get('database_connection');

        $config = new Configuration($db);

        $config->setMigrationsTableName('migration_versions');
        $config->setMigrationsNamespace('Application\\Migrations');
        $config->setMigrationsDirectory('../app/DoctrineMigrations');
        $config->registerMigrationsFromDirectory($config->getMigrationsDirectory());

        $migration = new Migration($config);

        try {
            DBMigrations::updateMigrationStarted($this->getDoctrine()->getManager(), $db->getDatabase());
            $migration->migrate($to);
            DBMigrations::updateMigrationCompleted($this->getDoctrine()->getManager(), $db->getDatabase());
            return true;
        } catch (\Exception $ex) {

            if ($throwError) {
                throw new \Exception($ex->getMessage());
            }

            echo 'ERROR: ' . $ex->getMessage() . $nl;
            die();
        }
    }

    /**
     * @return string
     */
    private function generatePassword()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @param $accountName
     * @param $domain
     */
    private function notifyOfNewAccount($accountName, $domain)
    {
        /** @var \AppBundle\Services\EmailService $emailService */
        $emailService = $this->get('service.email');

        $subject = "New account : ".$accountName;
        $message = <<<EOM
Account {$accountName} has been deployed.
{$domain}
EOM;

        $emailService->send('chris@lend-engine.com', 'Lend Engine', $subject, $message);
    }

    /**
     * @param $name
     * @param $email
     * @param $org
     * @param $accountCode
     * @return bool
     */
    private function subscribeToMailchimp($name, $email, $org, $accountCode)
    {

        $mailChimpApiKey = $this->getParameter('mailchimp_api_key');
        $mailChimpListId = $this->getParameter('mailchimp_list_id');

        if ($name && $email && $mailChimpApiKey && $mailChimpListId) {

            $mailchimp = $this->get('hype_mailchimp');
            $mailchimp->setApiKey($mailChimpApiKey);
            $mailchimp->setListID($mailChimpListId);

            $name_parts = explode(' ', $name);
            if (isset($name_parts[1])) {
                $lname = $name_parts[1];
            } else {
                $lname = '';
            }
            $mergeVars = [
                'fname' => $name_parts[0],
                'lname' => $lname,
                'org'   => $org,
                'account' => $accountCode
            ];

            try {
                $mailchimp->getList()->addMerge_vars($mergeVars)->subscribe(
                    $email,
                    'html',
                    false, // doubleoptin
                    true
                );
            } catch (\Exception $e) {

            }

        }

        return true;
    }

    /**
     * @return bool
     */
    private function updateCoreLoginTime()
    {
        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');

        /** @var \AppBundle\Entity\Tenant $tenant */
        if (!$tenant = $settingsService->getTenant()) {
            $this->addFlash('error', "Could not find tenant");
            return false;
        }

        $tenant->setLastAccessAt( new \DateTime() );
        $em->persist($tenant);

        try {
            $em->flush();
        } catch (\Exception $e) {
            $this->addFlash('error', "Could not update last access date : ".$e->getMessage());
        }

        return true;
    }

}
