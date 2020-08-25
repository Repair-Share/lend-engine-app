Install lend engine
===================

Software
--------
* Install a local copy of lendengine software
```
git clone https://github.com/lend-engine/lend-engine-app.git
```
Or download the zip file from github

* Add this line to app/config/parameters.yml.dist
```
    CLOUDAMQP_URL: amqp://lendengine:lendengine@localhost/lendengine
```

Database
--------
* Create mysql user and _core database
* Run tenant_setup.sql in _core database. This creates the _core.account table
* Create extra schema unit_test and an extra target schema e.g. lendengine
* Insert an extra row in account table for the account to be created using the just created schema e.g. lendengine
  * stub: unique name for this installation e.g. repairsharele
  * name: Organisation name e.g. Repair & Share
  * db_schema: newly created target schema e.g. lendengine
  * owner_name: your name. Will be split in first and last name, so should contain at least 2 words delimited by space
  * owner_email: your email, will be used to login into the system e.g. me@reparishare.be
  * status: 'DEPLOYING'
  * plan: 'plus'
  * domain: domain of your lend engine application e.g. lendengine.repairshare.be
  * server_name: server running lend engine e.g. localhost
  * org_email: your organisation contact email e.g. info@repairshare.be
```
INSERT INTO `account` (`stub`, `name`, `db_schema`, `owner_name`, `owner_email`, `status`, `plan`, `domain`, `server_name`, `org_email`)
VALUES
	('repairsharele','Repair & Share','lendengine','Me TheDeployer','me@repairshare.be','DEPLOYING',
  'plus','lendengine.repairshare.be','localhost','info@repairshare.be');
```

Apache configuration
--------------------
Create vhost
```
<VirtualHost *:80>
       DocumentRoot "/path/to/lend-engine-app/web"
       ServerName lendengine.localhost
       SetEnv SYMFONY_ENV dev
       SetEnv LE_SERVER_NAME localhost
       SetEnv SYMFONY__POSTMARK_API_KEY dummyApiKey
       SetEnv DEV_DB_USER lendengine
       SetEnv DEV_DB_PASS lendengine
       SetEnv APP_ENV dev
       <Directory "/path/to/lend-engine-app">
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
       </Directory>
</VirtualHost>
```

Install steps on Linux
----------------------

Install appropriate PHP version

Reference: https://www.digitalocean.com/community/tutorials/how-to-run-multiple-php-versions-on-one-server-using-apache-and-php-fpm-on-ubuntu-18-04#:~:text=The%20ondrej%2Fphp%20PPA%20will,%2Drepository%20ppa%3Aondrej%2Fphp
```
$ sudo apt-get install software-properties-common -y
$ sudo add-apt-repository ppa:ondrej/php
$ sudo apt-get update -y
$ sudo apt-get install php7.2 php7.2-fpm php7.2-mysql libapache2-mod-php7.2 -y
$ php7.2 -v
$ sudo apt-get install php7.2-mbstring php7.2-curl php7.2-dom php7.2-xml php7.2-gd php7.2-intl php7.2-mysql
```
mysql driver
```
$ sudo vi /etc/php/7.2/apache2/php.ini
-> uncomment pdo_mysql
$ sudo service apache2 restart
```
Install dependencies
```
$ php7.2 /usr/bin/composer install
```

Note: the application ignores any .env file
==> env variables should be set directly on environment, not in a .env file

**To run on production:**
```
export RDS_URL=xxx
export SYMFONY_ENV=prod
export LE_SERVER_NAME=yourhost
export SYMFONY__POSTMARK_API_KEY=yourKey
```

**To run on dev:**
```
export DEV_DB_USER=lendengine
export DEV_DB_PASS=lendengine
export SYMFONY_ENV=dev
export LE_SERVER_NAME=localhost
export SYMFONY__POSTMARK_API_KEY=dummyKey
```
See also src/AppBundle/Account/CustomConnectionFactory

**Optional**

*Get symfony client*
```
$ wget https://get.symfony.com/cli/installer -O - | bash
```

*Check migration status*
```
$ php7.2 bin/console doctrine:migration:status
```
When executed on command line, this checks the migration status of unit_test scheme. 
Doctrine migrations doc:
https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html

*Install mysql client*
```
$ sudo apt install mysql-client-core-8.0
```

*Install net-tools*
```
$ sudo apt install net-tools
```

Install steps on windows
------------------------

* Install XAMPP (https://www.apachefriends.org/index.html) with appropriate PHP 
version (7.2)
* Install composer (https://getcomposer.org/download/)
* Install appropriate extra extension (enable them in php.ini)
  * extension=intl
  * extension=sockets
  * extension=pdo_mysql
  * extension=mbstring
  * extension=curl
  * extension=gd2
  * extension=mysqli
  * +bz2, fileinfo, gettext?

Note: the application ignores any .env file
==> env variables should be set directly on environment, not in a .env file

**To run on production:**
```
set RDS_URL=xxx
set SYMFONY_ENV=prod
set LE_SERVER_NAME=yourhost
set SYMFONY__POSTMARK_API_KEY=yourKey
```

**To run on dev:**
```
set DEV_DB_USER=lendengine
set DEV_DB_PASS=lendengine
set SYMFONY_ENV=dev
set LE_SERVER_NAME=localhost
set SYMFONY__POSTMARK_API_KEY=dummyKey
set APP_ENV=dev
```
See also src/AppBundle/Account/CustomConnectionFactory


**Install dependencies**

$ composer install

**Optional**

Install Symfony client from (https://symfony.com/download)
You can then start a server for development purposes with (a row with stub set to 'dev' need to exist in _core.account)
```
set DEV_DB_USER=lendengine
set DEV_DB_PASS=lendengine
set SYMFONY_ENV=dev
set LE_SERVER_NAME=localhost
set SYMFONY__POSTMARK_API_KEY=dummyKey
set APP_ENV=dev
symfony server:start
```

Common
------

**Load page in browser and deploy:**
```
http://lendengine.localhost/deploy
```
Copy the generated password

DB migration is triggered by AppBundle\Controller\UpdateController.deployNewDatabase

**Generate cache:**
Should be autogenerated when debug is set to true. For prod, this should be generated upfront
```
php bin/console cache:warmup --env=prod --no-debug
```

**Check configuration:**
```
php bin/console doctrine:ensure-production-settings --no-debug --env=prod
```

** console commands**
To list all available console commands run
```
php bin/console
```
This requires the server configuration file to exist (e.g. app/config/server/localhost.yml). 
If this file is missing, create it as a copy of app/config/server/dev.yml

**Sample log (on windows cmd prompt):**
```
C:\lend-engine-app>set DEV_DB_USER=lendengine
C:\lend-engine-app>set DEV_DB_PASS=lendengine
C:\lend-engine-app>set SYMFONY_ENV=dev
C:\lend-engine-app>set LE_SERVER_NAME=localhost
C:\lend-engine-app>set SYMFONY__POSTMARK_API_KEY=dummyKey
C:\lend-engine-app>php bin/console cache:warmup --env=prod --no-debug
 // Warming up the cache for the prod environment with debug false

 [OK] Cache for the "prod" environment (debug=false) was successfully warmed.

C:\lend-engine-app>php bin/console doctrine:ensure-production-settings --no-debug --env=prod

 [OK] Environment is correctly configured for production.

C:\lend-engine-app>
```

**Replace Amazon S3 by local storage:**

Update app/config/config.yml file to add local oneup_flysystem adapters and refer them in filesystems definition
(see also [https://github.com/thephpleague/flysystem] and [https://github.com/1up-lab/OneupUploaderBundle/blob/master/doc/flysystem_storage.md])
```
oneup_flysystem:
    adapters:
        product_adapter_local:
            local:
                directory: "%kernel.root_dir%/../web/images/products"
        file_adapter_local:
            local:
                directory: "%kernel.root_dir%/../web/images/files"
    filesystems:
        product_image_fs:
#            adapter: product_adapter
            adapter: product_adapter_local
            mount:   product_image_fs
        secure_file_fs:
#            adapter: file_adapter
            adapter: file_adapter_local
            mount:   secure_file_fs
```

Update app/config/parameters.yml(.dist) to update weburl where images are located
```
    #s3_bucket:         https://s3-us-west-2.amazonaws.com/lend-engine/
    s3_bucket:         http://127.0.0.1:8000/images/products/
```

This change might require a cache clear to take effect
```
php bin/console cache:clear --env=dev
```

**Note:**
After DB migration, the account is marked as 'TRIAL' and will expire after 1 month
You might want to update the _core.account record in database to update its status to 'LIVE'

See also src\AppBundle\Entity\Tenant.php for all possible values

Good to know:
------------
* FOS stands for Friends Of Symfony, a package
* image and file uploads provided by oneup_uploader bundle
* Storage to S3 is configured through flysystem (https://github.com/1up-lab/OneupUploaderBundle/blob/master/doc/flysystem_storage.md)
  It should be possible to update configuration for local storage instead
* DB migration is triggered by AppBundle\Controller\UpdateController.deployNewDatabase
* Routes in app are configured using annotations in controllers
* Connection setup is defined in AppBundle\Account\CustomConnectionFactory
* CLI commands always connect to unit_test scheme
* production config is based on (parsed) RDS_URL. If not defined, dev is assumed using host 127.0.0.1, scheme _core and user/pass from DEV_DB_USER and DEV_DB_PASS
* query to lookup account (in _core.account table):

```
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
              FROM account
              WHERE stub = '{$account_code}' OR domain = '{$domain}'
              AND server_name = '{$leServerName}'
              ORDER BY domain DESC
              LIMIT 1
```
With
* leServerName from env var LE_SERVER_NAME
* account_code from
  * server: first part of $_SERVER['HTTP_HOST'] (subdomain or part before first .)
  * CLI: unit_test
  * on localhost (ip starts with 127 or subdomain contains 'localhost'): dev
* domain from $_SERVER['HTTP_HOST']



When running on WSL2 (Windows 10)
---------------------------------
Port forwarding issue - solution below not working yet...
see also 
https://docs.microsoft.com/en-us/windows-server/networking/technologies/netsh/netsh-interface-portproxy#delete-v4tov4

Enable port forwarding to mysql server in powershell:
PS C:\lend-engine-app> netsh interface portproxy show all

PS C:\lend-engine-app> netsh interface portproxy add v4tov4 listenport=3306 connectaddress=172.18.147.157
The requested operation requires elevation (Run as administrator).

```
PS C:\lend-engine-app> netsh interface portproxy show all

Listen on ipv4:             Connect to ipv4:

Address         Port        Address         Port
--------------- ----------  --------------- ----------
*               3306        172.18.147.157  3306

PS C:\lend-engine-app>
```

Logs
----
**Migration status:**
```
C:\lend-engine-app>php bin/console doctrine:migrations:status
```
 == Configuration

    >> Name:                                               Application Migrations
    >> Database Driver:                                    pdo_mysql
    >> Database Name:                                      unit_test
    >> Configuration Source:                               manually configured
    >> Version Table Name:                                 migration_versions
    >> Version Column Name:                                version
    >> Migrations Namespace:                               Application\Migrations
    >> Migrations Directory:                               C:\lend-engine-app\app/DoctrineMigrations
    >> Previous Version:                                   Already at first version
    >> Current Version:                                    0
    >> Next Version:                                        (0000)
    >> Latest Version:                                     2020-06-16 20:51:44 (20200616205144)
    >> Executed Migrations:                                0
    >> Executed Unavailable Migrations:                    0
    >> Available Migrations:                               44
    >> New Migrations:                                     44

C:\lend-engine-app>