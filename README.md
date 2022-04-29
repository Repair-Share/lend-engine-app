Lend Engine
===========

The Lend Engine is a platform for lending libraries to handle their items, members, memberships, payments and general management. It is available (hosted) via www.lend-engine.com, including a free version. There are no plans to extend Lend Engine to be available to host on your own servers, though the code is available as-is (without support) should you wish to try.

Feature suggestions and pull requests are welcome (with the caveat that we need extra unit tests for existing code too, so please help with that alongside any additional contributions).

**Requirements**

- PHP 7.2.x
- MySql 5.7.x
- An AWS S3 account for file uploads
- A Postmark account for outgoing emails

**Getting started**

1. Download the repo from https://github.com/lend-engine/lend-engine-app 
and then run `composer install` to add dependencies.
2. Run `tenant_setup.sql` - this adds a development account into the tenant list. Set your email.
3. Add your environment variables
4. If your dev server is running at localhost:8001, add that as the stub for the account.
5. ``CREATE DATABASE unit_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;``
6. ``UPDATE _core.account SET status = 'DEPLOYING' where stub = 'unit_test';``
7. Set `account.owner_name` and `account.owner_email` to yours.
8. Lastly, visit the deployment URL to create a new account 
``http://unit_test.localhost:8000/deploy``

So that all tenants share the same item types, for network-level reporting, we use 
a shared table: `_core.item_type`. Run 

``/web/plugins/type/itemTypes.sql``

to insert the latest set. These are taken from a Google product taxonomy.

**Email**

Emails are sent using RabbitMQ. You'll need to configure the settings, and then run a worker (or two) 
using ``bin/console rabbitmq:consumer mail_queue``

**Functional testing**

Create an empty database called unit_test.
DB setup is in ContactController which runs first.
Fixtures are loaded for contact and item.

Run ``phpunit``

**Server deployment**

Lend Engine will run on most LAMP environments. 
In production, we use Heroku. 
AWS for DB (mySQL 5.7), S3 for hosting images and uploaded files, and PostMark for emails. 
Credentials are stored in ENV variables.

**Tenant management**

Lend Engine is multi-tenanted. The routing to the relevant tenant is determined by the URL. 
`CustomConnectionFactory.php` looks for a matching account; where the account stub 
or the account domain matches the URL host.

Tenants are added to the `_core.account` table.

**Asset management**

We use Assetic to bundle JS and CSS files. Each time a JS/CSS file is changed, you'll 
need to re-bundle using :

```php bin/console assetic:dump --env=prod --no-debug```

Node is needed to run assetic, so you might need to install that first if you don't already have it.
You may also need to `npm install uglify-js -g`

The resource files bundles into the combined asset are in

```src/AppBundle/Resources/```

**Environment variables required**

```
SYNFONY_ENV=dev
LE_SERVER_NAME=dev/staging/prod etc
SYMFONY__POSTMARK_API_KEY=xxx
DEV_DB_USER=xxx
DEV_DB_PASS=xxx
CLOUDAMQP_URL=xxx
```

**Required to upload images to an Amazon AWS bucket**

```
SYMFONY__AWS_KEY=xxx
SYMFONY__AWS_SECRET=xxx
```

**ENV variables required if you are testing with Stripe**

It's likely that Stripe connection will only work using Lend Engine Stripe client credentials, 
as users are linked to the Lend Engine billing Stripe account as 'connected accounts'.

```
SYMFONY__STRIPE_CLIENT=xxx
SYMFONY__STRIPE_SECRET=xxx
STRIPE_PUBLIC_KEY_TEST=xxx
STRIPE_PRIVATE_KEY_TEST=xxx
STRIPE_SUBS_KEY_SECRET=xxx
STRIPE_SUBS_KEY_PUBLIC=xxx
```