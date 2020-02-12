Lend Engine
===========

The Lend Engine is a platform for lending libraries to handle their items, members, memberships, payments and general management. It is available (hosted) via www.lend-engine.com, including a free version. There are no plans to extend Lend Engine to be available to host on your own servers, though the code is available as-is (without support) should you wish to try.

Feature suggestions and pull requests are welcome (with the caveat that we need extra unit tests for existing code too, so please help with that alongside any additional contributions).

**Requirements**
- PHP 7.2
- MySql 5.xxx

**Getting started**
1. Download the repo from https://github.com/lend-engine/lend-engine-app
2. Run the tenant_setup.sql - this adds a development account into the tenant list. Set your email.
3. Add your environment variables
4. If your dev server is running at localhost:8001, add that as the stub for the account.
5. CREATE DATABASE unit_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
6. UPDATE _core.account SET status = 'DEPLOYING';
7. Visit the deployment URL to create a new account : http://localhost:8001/deploy

**Unit / functional testing**

Create an empty database called unit_test.
DB setup is in ContactController which runs first.
Fixtures are loaded for contact and item.

**Server deployment**

Lend Engine will run on most LAMP environments. 
In production, we use Heroku. 
AWS for DB (mySQL 5.7), S3 for hosting images and uploaded files, and PostMark for emails. 
Credentials are stored in ENV variables.

**Environment variables required**

SYNFONY_ENV=dev
LE_SERVER_NAME=dev|staging|plus|prod etc
SYMFONY__POSTMARK_API_KEY=xxx
DEV_DB_USER=xxx
DEV_DB_PASS=xxx

** Required to upload images to an Amazon AWS bucket **

SYMFONY__AWS_KEY=xxx
SYMFONY__AWS_SECRET=xxx

**ENV variables required if you are testing with Stripe**

SYMFONY__STRIPE_CLIENT=xxx
SYMFONY__STRIPE_SECRET=xxx
STRIPE_PUBLIC_KEY_TEST=xxx
STRIPE_PRIVATE_KEY_TEST=xxx
STRIPE_SUBS_KEY_SECRET=xxx
STRIPE_SUBS_KEY_PUBLIC=xxx
