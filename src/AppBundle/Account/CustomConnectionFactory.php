<?php

namespace AppBundle\Account;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\PDOException;
use Symfony\Component\HttpFoundation\Request;

class CustomConnectionFactory extends ConnectionFactory
{

    private $db;
    private $server;
    private $database;
    private $username;
    private $password;

    function __construct()
    {
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
            throw new \Exception("Could not get database details. Please ensure ENV variables are set.");
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
            // This account has to exist on all servers (including production)
            // Because it's the one used as default for cache:clear as part of deployment
            $account_code = 'unit_test';
        }

        $httpHost = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            $httpHost = $_SERVER['HTTP_HOST'];
        }

        if ($result = $this->getAccountInformation($account_code, $httpHost)) {
            $account_code   = $result[0]['stub'];
            $dbName         = $result[0]['db_schema'];
            $account_status = $result[0]['status'];
            $customDomain   = $result[0]['domain'];
            if ($account_status == 'ARCHIVED' || $account_status == 'DELETED') {
                die("The account '{$account_code}' has expired.");
            }
        } else {
            $html = $this->noAccount($account_code, $httpHost);
            die($html);
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
              ORDER BY domain DESC
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

    private function noAccount($accountCode, $host)
    {
        $html = <<<EOL
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Error</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <style>
        body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif }
        .content {
            width: 100%;
            padding: 100px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="content">
    No Lend Engine account found for <strong>{$accountCode}</strong> or <strong>{$host}</strong>
</div>
</body>
</html>
EOL;

        return $html;

    }

}