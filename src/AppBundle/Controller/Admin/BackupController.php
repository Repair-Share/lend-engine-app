<?php

namespace AppBundle\Controller\Admin;

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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Filesystem\Filesystem;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class BackupController extends Controller
{

    /**
     * Generate a backup
     * @Route("admin/backup", name="full_backup")
     */
    public function backup()
    {
        if (!$this->getUser()->hasRole("ROLE_SUPER_USER")){
            $this->addFlash("error", "You don't have permission to export backups.");
            return $this->redirectToRoute('homepage');
        }

        $url = getenv('RDS_URL');
        if ($url) {
            $dbparts = parse_url($url);
            $server   = $dbparts['host'];
            $username = $dbparts['user'];
            $password = $dbparts['pass'];
        } else  {
            $server   = '127.0.0.1';
            $username = getenv('DEV_DB_USER');
            $password = getenv('DEV_DB_PASS');
        }

        /** @var \AppBundle\Services\SettingsService $settingsService */
        $settingsService = $this->get('settings');
        $tenant = $settingsService->getTenant();

        $dbname = $tenant->getDbSchema();

        $mysqldump = exec('which mysqldump');

        if (!$mysqldump) {
            return new JsonResponse(["No mysqldump found on server to perform backup"]);
        }

        $path = '../temp/';
        $fileName = $tenant->getDbSchema().'_'.microtime(true).'.sql';
        $filePath = $path.$fileName;

        $command = "$mysqldump -B -h {$server} -u $username --password=$password $dbname > $filePath";
        exec($command);

        if ($fileContent = fopen($filePath, 'r')) {
            $response = new BinaryFileResponse($filePath);

            $disposition = $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $fileName
            );

            $response->headers->set('Content-Disposition', $disposition);

            // The file will get deleted next time the [Heroku] dyno reboots
            // If you're not using Heroku you need to find a way to periodically or programmatically remove backups
            return $response;
        } else {
            return new JsonResponse(["File not found at {$path}"]);
        }

    }

}
