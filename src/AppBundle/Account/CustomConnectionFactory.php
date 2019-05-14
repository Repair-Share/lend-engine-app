<?php

namespace AppBundle\Account;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\PDOException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class CustomConnectionFactory extends ConnectionFactory
{

    private $db;
    private $server;
    private $database;
    private $username;
    private $password;

    /** @var Session  */
    private $session;

    function __construct(Session $session)
    {
        $this->session = $session;

        $connectionFound = false;

        // Production
        $url = getenv('RDS_URL');
        if ($url) {
            $dbparts = parse_url($url);
            $this->server   = $dbparts['host'];
            $this->database = '_core';
            $this->username = $dbparts['user'];
            $this->password = $dbparts['pass'];
            $connectionFound = true;
        }

        // Dev
        if ($connectionFound == false) {
            $this->server   = '127.0.0.1';
            $this->database = '_core';
            $this->username = getenv('DEV_DB_USER');
            $this->password = getenv('DEV_DB_PASS');
        }

        if (!$this->server || !$this->username || !$this->password) {
            throw new PDOException("Could not get DB account details.");
        }
    }

    public function createConnection(array $params, Configuration $config = null, EventManager $eventManager = null, array $mappingTypes = array())
    {

        if (isset($_GET['state']) && $_GET['state']) {
            // We're coming back from Stripe.com oAuth into the HTTPS Heroku domain so we don't have a subdomain
            $account_code = $_REQUEST['state'];
        } else if (isset($_SERVER['HTTP_HOST'])) {
            // We're running in a browser
            $d = explode(".", $_SERVER['HTTP_HOST']);
            $account_code = $d[0];
        } else {
            // We're running CLI (command console and unit tests)
            $account_code = 'dev';
        }

        $httpHost = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            $httpHost = $_SERVER['HTTP_HOST'];
        }

        if ($result = $this->getAccountInformation($account_code, $httpHost)) {

            $account_code   = $result[0]['stub'];
            $dbName         = $result[0]['db_schema'];
            $account_name   = $result[0]['name'];
            $owner_name     = $result[0]['owner_name'];
            $owner_email    = $result[0]['owner_email'];
            $trial_expires  = $result[0]['trial_expires_at'];
            $plan           = $result[0]['plan'];
            $subscriptionId = $result[0]['subscription_id'];
            $account_status = $result[0]['status'];
            $customDomain   = $result[0]['domain'];
            $server_name    = $result[0]['server_name'];
            $timeZone       = $result[0]['time_zone'];

            if ($result[0]['server_name'] != getenv('LE_SERVER_NAME')) {
//                throw new \Exception("{$account_code} You are trying to access a {$server_name} account from server ".getenv('LE_SERVER_NAME'));
            }

            if ($account_status == 'ARCHIVED' || $account_status == 'DELETED') {
                die("The account '{$account_code}' has expired.");
            }

        } else {
            die("We don't have an account for <strong>{$account_code}</strong> or <strong>'.$httpHost.'</strong>");
        }

        $params['driver']   = 'pdo_mysql';
        $params['host']     = $this->server;
        $params['port']     = 3306;
        $params['dbname']   = $dbName;
        $params['user']     = $this->username;
        $params['password'] = $this->password;

        // If we have an SSL domain for this user and it's not being used, redirect to it
        // But only if we're not coming back from Stripe which needs the SSL heroku domain
        if ($customDomain && isset($_SERVER['HTTP_HOST']) && !strstr($_SERVER['HTTP_HOST'], $customDomain)) {
            if (!isset($_GET['state'])) {
                header("Location: https://".$customDomain.'/?redirectedFromHTTP');
                die();
            }
        }

        // Hack to get around https://github.com/symfony/symfony/issues/13450
        /*
         * I defined a twig globals var in config.yml and I assigned a service.
         * This service had @session as an argument, which caused all the trouble.
         *
         * It would be better to get the tenant (Account) information with each request rather than from session vars
         *
         */
        // Since we don't have HTTP_HOST for unit testing
        if (isset($_SERVER['HTTP_HOST'])) {

            $this->session->set('account_code', $account_code);
            $this->session->set('account_name', $account_name);
            $this->session->set('account_owner_name', $owner_name);
            $this->session->set('account_owner_email', $owner_email);
            $this->session->set('account_status', $account_status);
            $this->session->set('account_schema', $dbName);
            $this->session->set('trial_expires_at', $trial_expires);
            $this->session->set('server_name', $server_name);
            $this->session->set('time_zone', $timeZone);
            $this->session->set('subscription_id', $subscriptionId);

            // This had to be replicated here as well as Entity/Tenant.php
            switch ($plan) {
                case 'free':
                    $plan = 'free';
                    break;
                case 'standard':
                case 'plan_Cv8Lg7fyOJSB0z': // standard monthly 5.00
                case 'plan_Cv6TbQ0PPSnhyL': // test plan
                case 'plan_Cv6rBge0LPVNin': // test plan
                case 'single':
                    $plan = 'starter';
                    break;
                case 'premium':
                case 'plus':
                case 'multiple':
                    $plan = 'plus';
                    break;
                case 'business':
                    $plan = 'business';
                    break;
            }
            $this->session->set('plan', $plan);
            // End

            if ($account_code == 'localhost:8000') {
                $this->session->set('account_domain', $account_code);
            } else if ($customDomain) {
                $this->session->set('account_domain', $customDomain);
            } else {
                $this->session->set('account_domain', $account_code.'.lend-engine-app.com');
            }

        }

        //continue with regular connection creation using new params
        return parent::createConnection($params, $config, $eventManager, $mappingTypes);

    }

    /**
     * @param $account_code
     * @param string $domain
     * @return array
     */
    function getAccountInformation($account_code, $domain = '')
    {
        try {
            $this->db = new \PDO(
                "mysql:host={$this->server};dbname={$this->database};charset=utf8mb4",
                $this->username,
                $this->password);
        } catch(PDOException $ex) {
            die("Couldn't connect to database {$this->database} with username {$this->username}.");
        }

        // Only check for accounts assigned to this server
        $leServerName = getenv('LE_SERVER_NAME');

        try {
            if ( $stmt = $this->db->query("SELECT
              stub,
              domain,
              db_schema,
              name,
              owner_name,
              owner_email,
              status,
              trial_expires_at,
              plan,
              server_name,
              time_zone,
              subscription_id
              FROM account
              WHERE stub = '{$account_code}' OR domain = '{$domain}'
              AND server_name = '{$leServerName}'
              LIMIT 1
              ") ){
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                die("Query failed for account {$account_code} on DB {$this->database}");
            }
        } catch(PDOException $ex) {
            die("Failed to run query");
        }
    }

}