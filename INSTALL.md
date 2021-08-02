Install lend engine
===================

These instructions should help you if you would like to run lend engine on your own server. 
It contains step by step instructions to install lend engine on [dokku](https://www.apachefriends.org/index.html), 
[LAMP](https://howtoubuntu.org/how-to-install-lamp-on-ubuntu) or [XAMPP](https://www.apachefriends.org/index.html) environments

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


Dokku
-----

**Install**

Install dokku on your target server.   
For detailed instructions, see (http://dokku.viewdocs.io/dokku/)  
The use of virtualhost naming for apps is assumed

For daily management, it is recommended to create an extra user
```
$ adduser newuser
$ usermod -aG sudo newuser
```
Add your public key to /home/newuser/.ssh/authorized_keys 

*Local linux install*

Install dokku and set password for new dokku user
```
$ wget https://raw.githubusercontent.com/dokku/dokku/v0.21.4/bootstrap.sh
$ sudo DOKKU_TAG=v0.21.4 bash bootstrap.sh
$ sudo passwd dokku
```

Setup SSH server to access dokku
```
$ sudo apt install openssh-server

# extract from https://github.com/dokku/dokku/issues/1813
# assuming you have ssh access via root
cat ~/.ssh/id_rsa.pub | ssh root@dokku.com "sudo sshcommand acl-add dokku admin"
# OR
# assuming you are logged in as a user with root
cat ~/.ssh/id_rsa.pub | sudo sshcommand acl-add dokku admin
```

*Windows 10 with WSL (experimental)*
- edit /etc/ssh/sshd_config and uncomment the Port line and ListenAddress (eventually set port to 2222 to avoid conflicts with win10 ssh port)
- Add host to SSH config

```
Host dokku
        Hostname localhost
        Port 2222
        User dokku
```
https://www.hanselman.com/blog/HowToSSHIntoWSL2OnWindows10FromAnExternalMachine.aspx  
https://gist.github.com/daehahn/497fa04c0156b1a762c70ff3f9f7edae?WT.mc_id=-blog-scottha

*Cloud server (VPS)*

The above instructions should help you to install dokku on any VPS server.  
One popular option is to use the 'one-click install' of [Digital Ocean](https://marketplace.digitalocean.com/apps/dokku)

**Plugins**
```
$ sudo dokku plugin:install https://github.com/dokku/dokku-mysql.git mysql
$ sudo dokku plugin:install https://github.com/dokku/dokku-letsencrypt.git
$ sudo dokku plugin:install https://github.com/dokku/dokku-rabbitmq.git rabbitmq
```

**App creation**  
Note: create one app for each lendengine instance. Replace 'myapp' by real app name and 'mydomain' by your fully qualified domain name
```
$ dokku apps:create myapp
$ dokku mysql:create lendenginedb
$ dokku mysql:link lendenginedb myapp
$ dokku rabbitmq:create lendenginemq
$ dokku rabbitmq:link lendenginemq myapp
$ dokku config:set myapp SYMFONY_ENV=prod LE_SERVER_NAME=yourServer SYMFONY__POSTMARK_API_KEY=yourKey
$ dokku config:set myapp RDS_URL="mysql://mysql:password@dokku-mysql-lendenginedb:3306/lendenginedb"
$ dokku config:set myapp APP_ENV=prod
$ dokku config:set myapp WEB_URL=http://myapp.mydomain
$ dokku config:set myapp WEB_CONCURRENCY=10      # limit the number of started processes to preserve enough memory for db and rabbitmq
$ dokku buildpacks:add myapp https://github.com/heroku/heroku-buildpack-apt
$ dokku buildpacks:add myapp https://github.com/heroku/heroku-buildpack-php
$ dokku storage:mount myapp /var/lib/dokku/data/storage/myapp/uploads:/app/web/uploads
$ dokku storage:mount myapp /var/lib/dokku/data/storage/myapp/logs:/app/var/logs
```
Note:  
Lookup mysql password from 'dokku config myapp' command. The value of RDS_URL should match DATABASE_URL  
-> **Update**: create a new db user for your app and use this new user in RDS_URL

For DEV environments, use this updated config
```
$ dokku config:set myapp SYMFONY_ENV=dev
$ dokku config:set myapp LE_SERVER_NAME=localhost
$ dokku config:set myapp SYMFONY__POSTMARK_API_KEY=dummyApiKey
$ dokku config:set myapp DEV_DB_USER=lendengine DEV_DB_PASS=lendengine
$ dokku config:set myapp APP_ENV=dev
$ dokku config:set myapp WEB_CONCURRENCY=5      # limit the number of started processes
$ dokku proxy:ports-add myapp http:8081:5000    # also listen on port 8081
```
Note:  
DEV deploys require extra packages. This might require an update of your composer.lock file

**Customize application**  
* Clone the git repository

```
$ git clone https://github.com/Repair-Share/lend-engine-app.git
$ git checkout -b myapp
$ git remote add myapp dokku@mydomain:myapp
```

Tip: create a branch and remote for each target lend engine instance
  
* Add Aptfile configuration file for heroku-buildpack-apt to add missing mbstring.so  php extension and mysqldump tool
```
# Aptfile
libonig-dev
libonig4
mysql-client
```

```
$ git add Aptfile
$ git commit -m "add heroku-buildpack-apt configuration file for install of mbstring.so php extension"
```

* Copy and commit server configuration file for your environment
```
$ cp app/config/server/lend-engine-eu.yml app/config/server/mydomain.yml
$ git add app/config/server/mydomain.yml
$ git commit -m "add mydomain config file"
```

**Database setup**  
The created mysql service comes with a default user and scheme, but we need an extra '_core' scheme to manage the different accounts
```
$ dokku mysql:enter lendenginedb
root@fb51ccb6b6e0:/# env      # to retrieve root password
root@fb51ccb6b6e0:/# mysql -p
Enter password:
mysql> create database _core;
mysql> grant all privileges on _core.* to 'mysql'@'%';
mysql> use _core;
# execute statements from tenant_setup.sql to create account and item_type tables
mysql> ... 
# insert extra row in account for our lendengine setup
mysql> INSERT INTO `account` (`stub`, `name`, `db_schema`, `owner_name`, `owner_email`, `status`, `plan`, `domain`, `server_name`, `org_email`, `created_at`)
VALUES
	('lendengine','Dev lend engine','lendenginedb','Me TheDeployer','me@repairshare.be','DEPLOYING',
  'plus','lendengine.repairshare.be','localhost','info@repairshare.be','2020-09-26 09:07:08');
mysql> grant all privileges on lendenginedb.* to 'mysql'@'%';
# create extra target db schema and corresponding user
mysql> create database extratargetdb;
mysql> create user 'myuser'@'%' identified by 'password';
mysql> grant all privileges on extratargetdb.* to 'myuser'@'%';
mysql> grant SELECT, INSERT, UPDATE on _core.* to 'myuser'@'%';
mysql> quit;
root@fb51ccb6b6e0:/# exit
```

See also [Database](#database) section for help on appropriate values

**RabbitMQ setup**

The rabbitmq service we created above comes with a default user lendenginemq and vhost lendenginemq  
We'll use those to send messages from lendengine app, but still need to configure target exchange and queues
```
$ dokku rabbitmq:enter lendenginemq
root@lendenginemq:/# rabbitmqadmin list vhosts -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqadmin declare exchange name=exchange_dev type=direct --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqadmin declare exchange name=exchange_prod type=direct --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqadmin declare queue name=exchange_prod --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqadmin declare queue name=exchange_dev --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqadmin declare binding source="exchange_prod" destination_type="queue" destination="exchange_prod" routing_key="" --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqadmin declare binding source="exchange_dev" destination_type="queue" destination="exchange_dev" routing_key="" --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# exit
```

*Show amq info*  
Use these commands to check your configuration
```
$ dokku rabbitmq:enter lendenginemq
root@lendenginemq:/# rabbitmqadmin list vhosts -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqadmin list exchanges --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqctl list_queues --vhost=$RABBITMQ_DEFAULT_VHOST
root@lendenginemq:/# rabbitmqadmin -f long -d 3 list queues --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqadmin publish exchange=exchange_prod routing_key="" payload="hello, world" --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS
root@lendenginemq:/# rabbitmqadmin get queue=exchange_prod ackmode=ack_requeue_true --vhost=$RABBITMQ_DEFAULT_VHOST -u $RABBITMQ_DEFAULT_USER -p $RABBITMQ_DEFAULT_PASS 
```

*Start processing email*

* Create postmark account.
* Start 'worker' container to read messages from queue
```
$ dokku config:set myapp SYMFONY__POSTMARK_API_KEY=<serverApiTokenFromPostmarkAccount>
$ dokku ps:scale myapp worker=1
```

Alternatively, manually start the worker. This can be usefull in troubleshooting as eventual error messages are easier to catch
```
$ php bin/console rabbitmq:consumer mail_queue
```

**Deploy**  
* Check ssh access
```
$ ssh dokku@<hostname>
```

* Deploy app to dokku server
```
$ git remote add myapp dokku@<hostname>:myapp
$ git push dokku
$ git push myapp localbranch:master
```

Browse to your server's deploy page:  
```
http://myapp.<hostname>/deploy
```

*Windows WSL2 setup (experimental)*  
Connection from Windows when using WSL2
- retrieve ip of WSL2 client with 'ifconfig'
- update C:\Windows\System32\drivers\etc\hosts file to add server/ip mapping

e.g.
```
172.29.79.206     lendengine.dokku 
```
Application should now be visible from windows too. Relaunch deploy

e.g.
http://lendengine.dokku:8081/deploy

Dokku issue: lendengine app still not visible
Workaround:
- expose port on all interfaces
```
$ dokku network:set lendengine bind-all-interfaces true
$ dokku ps:rebuild lendengine
$ docker ps    #  to check binding port number 
dokku@DESKTOP-Q9T1M3S:~/lendengine$ docker ps
CONTAINER ID        IMAGE                                                COMMAND                  CREATED             STATUS                PORTS                      NAMES
3de7f5237afa        dokku/lendengine:latest                              "/start web"             14 minutes ago      Up 14 minutes         0.0.0.0:32772->5000/tcp    lendengine.web.1
```
- Update upstream in nginx config
```
upstream lendengine-5000 {

  # server 172.17.0.3:5000;
  server 127.0.0.1:32772;
}
```
- reload nginx
```
$ sudo nginx -s reload
```

Port number is random and changes at each restart -> config to be updated again...

**Troubleshooting**  
When things go wrong, here are a few hints to get more info  
- Check logs nginx dokku proxy server:  
/var/log/nginx/lendengine-access.log and /var/log/nginx/lendengine-error.log
- Check logs lendengine app in dokku container:
```
dokku enter lendengine
cat /tmp/heroku.apache2_error.5000.log
cat /tmp/heroku.apache2_access.5000.log
ls /app/var/logs/          # application logs
```
- run curl directly from within container (on port 5000):
```
dokku enter lendengine
curl -v localhost:5000
```

Database
--------
Note:  
These instructions are valid for any type of install

* Create mysql user and _core database
* Run tenant_setup.sql in _core database. This creates the _core.account table
* Create extra schema unit_test and an extra target schema e.g. lendengine
* Insert an extra row in account table for the account to be created using the just created schema e.g. lendengine
  * stub: unique name for this installation e.g. repairsharele. Can be used to retrieve account when set to same value as subdomain (e.g. use repairsharele when domain is repairsharele.repairshare.be)
  * name: Organisation name e.g. Repair & Share
  * db_schema: newly created target schema e.g. lendengine
  * owner_name: your name. Will be split in first and last name, so should contain at least 2 words delimited by space
  * owner_email: your email, will be used to login into the system e.g. me@reparishare.be
  * status: 'DEPLOYING'
  * plan: 'plus' (or 'business' if you want to use Postmark for sending email)
  * domain: domain of your lend engine application e.g. lendengine.repairshare.be
  * server_name: server running lend engine e.g. localhost. Should match value of LE_SERVER_NAME env variable
  * org_email: your organisation contact email e.g. info@repairshare.be
```
INSERT INTO `account` (`stub`, `name`, `db_schema`, `owner_name`, `owner_email`, `status`, `plan`, `domain`, `server_name`, `org_email`, `created_at`)
VALUES
	('repairsharele','Repair & Share','lendengine','Me TheDeployer','me@repairshare.be','DEPLOYING',
  'plus','lendengine.repairshare.be','localhost','info@repairshare.be','2020-09-26 09:07:08');
```

LAMP setup
----------

* Make sure you are running the appropriate PHP version  
If needed, run multiple php versions in parallel  
Reference: https://www.digitalocean.com/community/tutorials/how-to-run-multiple-php-versions-on-one-server-using-apache-and-php-fpm-on-ubuntu-18-04
```
$ sudo apt-get install software-properties-common -y
$ sudo add-apt-repository ppa:ondrej/php
$ sudo apt-get update -y
$ sudo apt-get install php7.2 php7.2-fpm php7.2-mysql libapache2-mod-php7.2 -y
$ php7.2 -v
$ sudo apt-get install php7.2-mbstring php7.2-curl php7.2-dom php7.2-xml php7.2-gd php7.2-intl php7.2-mysql
```

* Enable mysql driver
```
$ sudo vi /etc/php/7.2/apache2/php.ini
-> uncomment pdo_mysql
$ sudo service apache2 restart
```
* Install dependencies
```
$ php7.2 /usr/bin/composer install
```

* Configure virtual host and environment variables

Note: the application ignores any .env file
==> env variables should be set directly on environment, not in a .env file

*Apache configuration for DEV*  
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

*Apache configuration for Production*  
Create vhost
```
<VirtualHost *:80>
       DocumentRoot "/path/to/lend-engine-app/web"
       ServerName lendengine.localhost
       SetEnv SYMFONY_ENV prod
       SetEnv LE_SERVER_NAME <servername>
       SetEnv SYMFONY__POSTMARK_API_KEY <postmarkApiKey>
       SetEnv RDS_URL <database_url>
       SetEnv APP_ENV prod
       <Directory "/path/to/lend-engine-app">
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
       </Directory>
</VirtualHost>
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

XAMPP setup (Windows 10)
------------------------

* Install [XAMPP](https://www.apachefriends.org/index.html) with appropriate PHP 
version (7.2)
* Install [composer](https://getcomposer.org/download/)
* Install appropriate extra extension (enable them in php.ini)
  * extension=intl
  * extension=sockets
  * extension=pdo_mysql
  * extension=mbstring
  * extension=curl
  * extension=gd2
  * extension=mysqli
  * +bz2, fileinfo, gettext?

* Configure virtual host and environment variables  
==> See instructions for LAMP

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

Note:  
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

**Console commands**  
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

Next steps
----------

**Replace Amazon S3 by local storage:**

Update app/config/config.yml file to add local oneup_flysystem adapters and refer them in filesystems definition
(see also [Flysystem](https://github.com/thephpleague/flysystem) 
and [Flysystem as storage layer](https://github.com/1up-lab/OneupUploaderBundle/blob/master/doc/flysystem_storage.md) )
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
    s3_bucket:         http://<FQDN>/images/products/
```

This change might require a cache clear to take effect
```
php bin/console cache:clear --env=dev
```

**Configuring email service:**

Emails are first written to an active MQ, and then forwarded to an email service (defaults to postmark)

* Rabbit MQ install  
See Rabbit MQ pages for instructions:
    * Rabbit Mq (https://www.rabbitmq.com/)
    * Rabbit Mq bundle (https://github.com/php-amqplib/RabbitMqBundle)


*For development environment:*    
To quickly start an active MQ server, you can use docker:
* Start Rabbit MQ in docker
    
    ```
    docker run -it --rm --name rabbitmq -p 5672:5672 -p 15672:15672 rabbitmq:3-management
    ```

* RabbitMQ Management page can be accessed from (user: guest, password:guest)
    ```
    http://localhost:15672
    ```
  * Create virtualhost on rabbitmq e.g. lendengine
  * Create exchanges 'exchange_dev' and/or 'exchange_prod' with type direct

* Set CLOUDAMQP_URL in parameters(-dist).yml

```CLOUDAMQP_URL: 'amqp://guest:guest@localhost:5672/lendengine'```

* Process emails
    * With Postmark  
    The default consumer pushes the messages to [Postmark](https://postmarkapp.com/) 
    and requires a Postmark API key to be set as SYMFONY__POSTMARK_API_KEY env var  
        * Create Postmark account
        * Setup Sender Signature
        * Request approval of your account to send messages to external domains
        * Switch lend engine plan to 'business'. This makes it possible to update 'From' email adres
        * Go to lend engine 'General' settings
        * Set your postmark API key in 'Postmark API key for outbound email'
        * Make sure '"From" email address for outbound email' matches your Sender Signature
        * Clear cache 
        ```
        php bin/console cache:clear --env=prod --no-debug
        ```
    
    * With SMTP server  
    For development purposes, the consumer can be replaced by a custom mailer src/AppBundle/Services/MailerDev.php
    which uses [PHPMailer](https://github.com/PHPMailer/PHPMailer) to push the messages to a [mailcatcher](https://mailcatcher.me/) running on port 1025
    
    To use this custom mailer, update the services.yml configuration file:
    ```
        service.mailer:
    #        class: AppBundle\Services\Mailer
            class: AppBundle\Services\MailerDev # send via PHPMailer instead
    ```
    
    * Running consumer
    ```
    php bin/console rabbitmq:consumer -m 50 mail_queue
    ```
    
    

* Install RabbitMq on windows

Open a cmd console
```
choco install rabbitmq
cd "C:\Program Files\RabbitMQ Server\rabbitmq_server-3.8.5\sbin"  # or open rabbitmq console from start menu
rabbitmq-plugins enable rabbitmq_management
```
Access management page from (http://{node-hostname}:15672/) and login (default user/pwd is guest/guest)  
  * Create virtualhost on rabbitmq e.g. lendengine
  * Create exchanges 'exchange_dev' and/or 'exchange_prod' with type direct
  * Create a queue (e.g. mail_queue) and bind it to the exchanges


**Updating account type**

After DB migration, the account is marked as 'TRIAL' and will expire after 1 month
You might want to update the _core.account record in database to update its status to 'LIVE'

See also src\AppBundle\Entity\Tenant.php for all possible values
```
$ dokku mysql:enter lendenginedb
root@fb51ccb6b6e0:/# env      # to retrieve root password
root@fb51ccb6b6e0:/# mysql -p
Enter password:
mysql> use _core;
mysql> UPDATE account SET status = 'LIVE' where stub = '<stub>';
mysql> commit;
mysql> quit;
root@fb51ccb6b6e0:/# exit
```

**Configuring DB backups**  
Tip: use [rclone](https://rclone.org) to replicate the backups to a safe storage

Set mysql backup script in /etc/cron.daily for daily backups of database

```
#!/bin/bash
LOGFILE=/var/log/dokku/backup_mysql.log

echo "Backing up Mysql databases from Dokku ..." >> $LOGFILE

dt=$(date +"%Y-%m-%d")

echo " today is $dt" >> $LOGFILE

BACKUP_PATH=/var/lib/dokku/data/backup/mysql/$(date +"%Y")/$(date +"%B")
# Uncomment this to use with rclone
# BACKUP_PATH=remote:dokku/mysql/$(date +"%Y")/$(date +"%B") 
TEMP_DIR=/tmp/backup
echo " creating $TEMP_DIR .." >> $LOGFILE
mkdir -p $TEMP_DIR >> $LOGFILE

dbs=$(dokku mysql:list | tail -n +2 | cut -f1 -d' ')

for db in $dbs
do
  echo " backing up $db ..." >> $LOGFILE
  mkdir -p $TEMP_DIR/$db >> $LOGFILE
  f=$TEMP_DIR/$db/$dt-$db.sql
  rm -f $f
  dokku mysql:export $db > $f
  gzip -f $f
  echo " backup file created at $f.gz" >> $LOGFILE

  mkdir -p $BACKUP_PATH/$db >> $LOGFILE
  cp $f.gz $BACKUP_PATH/$db >> $LOGFILE
  # Uncomment this to use with rclone
  # rclone copy $f.gz $BACKUP_PATH/$db >> $LOGFILE
  rm -f $f.gz
  echo " backup file $f.gz transferred to $BACKUP_PATH/$db" >> $LOGFILE
done
echo "Mysql backup completed" >> $LOGFILE
```

Similarly, backups of persisted data (e.g. uploaded files) can be configured
```
#!/bin/bash
LOGFILE=/var/log/dokku/backup_data.log
echo "Backing up data from dokku apps..." >> $LOGFILE

dt=$(date +"%Y-%m-%d")

echo " today is $dt" >> $LOGFILE

DATA_PATH=/var/lib/dokku/data/storage
BACKUP_PATH=/var/lib/dokku/data/backup/data/$(date +"%Y")/$(date +"%B")
# Uncomment this to use with rclone
# BACKUP_PATH=remote:dokku/data/storage

echo " creating $BACKUP_PATH .." >> $LOGFILE
mkdir -p $BACKUP_PATH

cp -pr $DATA_PATH $BACKUP_PATH
# Uncomment this to use with rclone
# rclone sync $DATA_PATH $BACKUP_PATH -P >> $LOGFILE

echo " backup of $DATA_PATH to $BACKUP_PATH completed" >> $LOGFILE
```

**Setup SSL**

To enable SSL, generate a certificate with letsencrypt
```
$ dokku config:set --no-restart myapp DOKKU_LETSENCRYPT_EMAIL=your@email.tld
$ dokku letsencrypt myapp
```

The generated certificate has a validity of 3 months. It is recommended to renew it 1 month before expiration
```
$ dokku letsencrypt:auto-renew myapp
```

You can configure a cron job to do this automatically by creating a file /etc/cron.weekly/dokku-letsencryt-renewal:
```
#!/bin/bash
LOGFILE=/var/log/dokku/letsencrypt-renewal.log
echo "Triggering letsencrypt certificate renewal..." >> $LOGFILE

dt=$(date +"%Y-%m-%d")

echo " today is $dt" >> $LOGFILE

dokku letsencrypt:auto-renew >> $LOGFILE
echo " certificate renewal completed" >> $LOGFILE
```

Make sure to create the file with execution permission
```
sudo chmod 755 /etc/cron.weekly/dokku-letsencrypt-renewal
```

Migration
---------

**User passwords**

When migrating from a legacy system, you might want to preserve the user passwords. 
Lend Engine uses bcrypt algorithm to generate passwords through the [friends of symfony](https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Resources/doc/index.rst) user bundle 

The algorithm is configured in security_http.yml or security_https.yml:
 security.encoders.FOS\UserBundle\Model\UserInterface
 
Possible to implement a PasswordUpgrader? See [PasswordHashUpgrader](https://gist.github.com/stof/cda5cad681e4fef092631a7a93c40ef7)  on gist

BCrypt hash can be generated with PHP builtin *password_hash* function