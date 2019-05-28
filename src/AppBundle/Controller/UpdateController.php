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

        $em = $this->getDoctrine()->getManager();

        /** @var \AppBundle\Services\SettingsService $settingService */
        $settingService = $this->get('settings');

        $tenant = $settingService->getTenant();

        // May 15th 2019 upgrade user if they are using features which moved up a plan
//        if ($this->isUpgradeRequired($tenant)) {
//            try {
//                $tenant->setPlan('plus');
//                if ($this->getUser()->hasRole("ROLE_ADMIN")) {
//                    $this->addFlash("success", "<strong>Welcome back!</strong>
//<br>We've upgraded your account to the Plus plan as you're using features which have moved to that plan.
//<br>You won't be charged any extra.");
//                }
//                $this->sendUpgradeEmail($tenant);
//            } catch (\Exception $e) {
//
//            }
//        }

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
     * @param Tenant $tenant
     * @return bool
     */
    private function isUpgradeRequired(Tenant $tenant)
    {
        // We've had a week of automatic upgrades, further users will need to get in touch.
        return false;

        $em = $this->getDoctrine()->getManager();
        if ($tenant->getPlan() == "starter") {
            if ($em->getRepository('AppBundle:CheckOutPrompt')->findAll()) {
                return true;
            }
            if ($em->getRepository('AppBundle:CheckInPrompt')->findAll()) {
                return true;
            }
            if ($em->getRepository('AppBundle:ContactField')->findAll()) {
                return true;
            }
            if ($em->getRepository('AppBundle:ProductField')->findAll()) {
                return true;
            }
            if ($em->getRepository('AppBundle:FileAttachment')->findAll()) {
                return true;
            }
        }

        return false;
    }

    private function sendUpgradeEmail(Tenant $tenant)
    {
        try {
            $client = new PostmarkClient($this->getParameter('postmark_api_key'));

            $body = <<<EOB
You've been upgraded to Plus plan at no extra cost, as we've moved some of the features you are using up to the higher plan.

We detected you have one or more of the following in your account:

- Check in or check out prompts
- Item custom fields or attachments
- Member custom fields or attachments

Log in at http://{$tenant->getStub()}.lend-engine-app.com

If you've got any questions, please just reply to this email!

##

p.s. Take a look at some of the other features available:

- Upload your logo into the member site settings page.
- Print and scan barcode labels.
- A new report: 'Loan item detail'.
- Select more than one membership type to be available self-serve online (paid or unpaid).
- Group items that have the same name into one result on the member site (see member site settings).
- Add extra pages or menu links to your website.
- Add images to your website pages.
- Partial refunds are now possible.

And if you want full control over your member experience, with your own URL and removal of all Lend Engine branding, it's now possible on the new 'Business' plan.
EOB;

            $message = $this->renderView(
                'emails/template.html.twig',
                array(
                    'heading' => 'Your account has been upgraded to the Plus plan',
                    'message' => $body,
                )
            );

            $client->sendEmail(
                "Lend Engine <hello@lend-engine.com>",
                $tenant->getOwnerEmail(),
                "We've upgraded your Lend Engine account",
                $message
            );

            $toEmail = 'chris@lend-engine.com';
            $client->sendEmail(
                "Lend Engine <hello@lend-engine.com>",
                $toEmail,
                "We've upgraded your Lend Engine account ({$tenant->getName()})",
                $message
            );

        } catch (PostmarkException $ex) {
            $this->addFlash('error', 'Failed to send email:'.$ex->message.' : '.$ex->postmarkApiErrorCode);
        } catch (\Exception $generalException) {
            $this->addFlash('error', 'Failed to send email:'.$generalException->getMessage());
        }
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
        $this->addAdminUser();
        $this->addUser();
        $this->setOrganisationDetails();

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');
        $tenant = $settingsService->getTenant();

        // Complete the deployment and activate the trial
        $em = $this->getDoctrine()->getManager();

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
