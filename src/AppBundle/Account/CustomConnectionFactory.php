<?php

namespace AppBundle\Account;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\PDOException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
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

        if ($account_code == 127 || strstr($account_code, 'localhost')) {
            $account_code = 'dev';
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
            $serverName     = $result[0]['server_name'];
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
        /*if ($customDomain && isset($_SERVER['HTTP_HOST']) && !strstr($_SERVER['HTTP_HOST'], $customDomain)) {
            if (!isset($_GET['state'])) {
                header("Location: https://".$customDomain.'/?redirectedFromHTTP');
                die();
            }
        }

        // Redirect the http:// to https://
        if ($customDomain
            && getenv('APP_ENV') === 'prod'
            && $serverName !== 'lend-engine-staging'
            && $_SERVER['HTTP_HOST']
            && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https'
        ) {

            $url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

            if (!isset($_GET['redirectedFromHTTP'])) {

                if (strpos($_SERVER['REQUEST_URI'], '?')) {
                    $url .= '&redirectedFromHTTP';
                } else {
                    $url .= '?redirectedFromHTTP';
                }

                header('Location: ' . $url);
                die();

            }

        }*/

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

            $accountInfo = $this->loadWithCache($account_code, $domain, $leServerName);

            if ($accountInfo) {
                return $accountInfo;
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

    public function loadWithCache($accountCode, $domain, $leServerName, $refreshCache = false)
    {
        $account = null;

        $cachePool = new FilesystemAdapter();

        $cacheKey = md5('account_' . $accountCode . '_' . $domain . '_' . $leServerName);

        if ($refreshCache) {
            $cachePool->deleteItem($cacheKey);
        }

        $cache = $cachePool->getItem($cacheKey);

        if (!$cache->isHit()) {

            $stmt = $this->db->query("
                SELECT
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
                
                FROM 
                    account
              
                WHERE 
                    stub = '{$accountCode}' OR domain = '{$domain}'
                    AND server_name = '{$leServerName}'
              
                ORDER BY domain DESC
              
                LIMIT 1

              ");

            $account = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $cache->set(serialize($account));
            $cache->expiresAfter(3600); // 1 hour
            $cachePool->save($cache);
        }

        if ($cachePool->hasItem($cacheKey)) {
            $cacheObject = $cachePool->getItem($cacheKey);
            $account     = unserialize($cacheObject->get());
        }

        return $account;
    }

}