<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contact;
use AppBundle\Entity\Membership;
use AppBundle\Entity\Tenant;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\OutputWriter;

use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

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
        /** @var \AppBundle\Repository\EventRepository $repository */
        $repository = $em->getRepository('AppBundle:Event');
        $repository->removeHistoricOpeningHours();

        /*   START DATA PATCH TO ADD ITEM ID INTO PAYMENTS FOR EXTENSIONS AND CHECK IN  */
        /** @var \AppBundle\Repository\InventoryItemRepository $itemRepo */
        $itemRepo = $this->getDoctrine()->getRepository('AppBundle:InventoryItem');
        $sql = "SELECT id,
reverse(
    substring(
    reverse(substring_index(REPLACE(p2.note, 'Extend ', ''), ' days', 1)),
      (POSITION(' ' IN reverse(substring_index(REPLACE(p2.note, 'Extend ', ''), ' days', 1)))+1)
    )
) as name
FROM payment p2 where p2.note REGEXP 'Extend (.*) [0-9]+ days.*'
";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $extensionPayments = $stmt->fetchAll();
        foreach ($extensionPayments AS $result) {
            $paymentId = $result['id'];
            $itemName = $result['name'];
            if ($item = $itemRepo->findOneBy(['name' => $itemName])) {
                $updateSql = "UPDATE payment SET item_id = {$item->getId()} WHERE id = {$paymentId}";
                $em->getConnection()->prepare($updateSql)->execute();
            }
        }

        $sql = "select id, item_id, REPLACE(note, 'Check-in fee for ', '') AS name from payment where note REGEXP 'Check-in fee for(.*)'";
        $stmt = $em->getConnection()->prepare($sql);
        $stmt->execute();
        $checkInPayments = $stmt->fetchAll();
        foreach ($checkInPayments AS $result) {
            $paymentId = $result['id'];
            $itemName = trim($result['name'], '.');
            if ($item = $itemRepo->findOneBy(['name' => $itemName])) {
                $updateSql = "UPDATE payment SET item_id = {$item->getId()} WHERE id = {$paymentId}";
                $em->getConnection()->prepare($updateSql)->execute();
            }
        }
        /*   END DATA PATCH   */

        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * @Route("deploy", name="deploy")
     */
    public function deployNewDatabase(Request $request)
    {
        // We should already have an empty database created from the marketing site
        // CREATE DATABASE xxx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        // Run any migrations that need running
        $this->updateSchema();

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
        }

        return $this->redirect($this->generateUrl('homepage'));
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
        $user->setUsername('hello@lend-engine.com');
        $user->setEmail('hello@lend-engine.com');
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
        $raw = "REPLACE INTO setting (setup_key, setup_value) VALUES ('org_name', '{$tenant->getName()}')";
        $s = $db->prepare($raw);
        $s->execute();

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
    public function updateSchema()
    {
        $to = null;
        $nl = '<br>';

        $db = $this->get('database_connection');

        $config = new Configuration($db);

        $config->setMigrationsTableName('migration_versions');
        $config->setMigrationsNamespace('Application\\Migrations');
        $config->setMigrationsDirectory('../app/DoctrineMigrations');
        $config->registerMigrationsFromDirectory($config->getMigrationsDirectory());

        $migration = new Migration($config);

        try {
            $migration->migrate($to);
            return true;
        } catch (\Exception $ex) {
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
