<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Contact;
use AppBundle\Entity\Membership;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\OutputWriter;

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
     * This function is called when a user logs in
     * Need to clear the cache due strange Heroku behaviour where not all proxies come up with wake-up
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

        // Make any DB updates required
        $this->updateSchema();

        $em = $this->getDoctrine()->getManager();

        // Mark loans as overdue
        /** @var \AppBundle\Repository\LoanRepository $repository */
        $repository = $em->getRepository('AppBundle:Loan');
        $repository->setLoansOverdue();

        // Remove historic opening hours
        /** @var \AppBundle\Repository\OpeningTimeExceptionRepository $repository */
        $repository = $em->getRepository('AppBundle:OpeningTimeException');
        $repository->removeHistoricOpeningHours();

        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * @Route("deploy", name="deploy")
     */
    public function deployNewDatabase()
    {
        // We should already have an empty database created from the marketing site
        // CREATE DATABASE xxx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
        // Run any migrations that need running
        $this->updateSchema();
        $this->addAdminUser();
        $this->addUser();
        $this->setOrganisationDetails();

        $name  = $this->get('session')->get('account_owner_name');
        $email = $this->get('session')->get('account_owner_email');
        $org   = $this->get('session')->get('account_name');
        $accountCode   = $this->get('session')->get('account_code');
        $this->subscribeToMailchimp($name, $email, $org, $accountCode);

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

        $orgName = $this->get('session')->get('account_name');
        $raw = "REPLACE INTO setting (setup_key, setup_value) VALUES ('org_name', :org_name)";

        /** @var \Doctrine\DBAL\Driver\PDOStatement $s */
        $s = $db->prepare($raw);

        $s->execute([':org_name' => $orgName]);

        return true;
    }

    /**
     * Add the first staff member (using details from _core)
     */
    public function addUser()
    {

        $pass  = $this->generatePassword();
        $name  = explode(' ', $this->get('session')->get('account_owner_name'));
        $email = $this->get('session')->get('account_owner_email');

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

        $message = "Your password is {$pass} - write it down now, we won't show it again. You can change it once you've logged in.";
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

        /** @var \AppBundle\Repository\TenantRepository $tenantRepo */
        $tenantRepo = $em->getRepository('AppBundle:Tenant');

        $stub = $this->get('session')->get('account_code');

        /** @var \AppBundle\Entity\Tenant $tenant */
        if (!$tenant = $tenantRepo->findOneBy(['stub' => $stub])) {
            $this->addFlash('error', "Could not find tenant with {$stub}");
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
