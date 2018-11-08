Lend Engine
===========

The Lend Engine is a platform for lending libraries to handle their items, members, memberships, payments and general management. It is available (hosted) via www.lend-engine.com, including a free version. There are no plans to extend Lend Engine to be available to host on your own servers, though the code is available as-is (without support) should you wish to try.

Feature suggestions and pull requests are welcome (with the caveat that we need extra unit tests for existing code too, so please help with that alongside any additional contributions).

**Unit / functional testing**

Create an empty database called unit_test.
DB setup is in ContactController which runs first.
Fixtures are loaded for contact and item.

**Server deployment**

Currently Lend Engine is only configured to run on Heroku with AWS for DB and PostMark for emails. Credentials are stored in ENV variables.

**ENV variables required**

SYNFONY_ENV=dev

LE_SERVER_NAME=dev|staging|plus|prod etc

SYMFONY__AWS_KEY=xxx

SYMFONY__AWS_SECRET=xxx

SYMFONY__POSTMARK_API_KEY=xxx

DEV_DB_USER=xxx

DEV_DB_PASS=xxx

SYMFONY__STRIPE_CLIENT=xxx

SYMFONY__STRIPE_SECRET=xxx

STRIPE_PUBLIC_KEY_TEST=xxx

STRIPE_PRIVATE_KEY_TEST=xxx

STRIPE_SUBS_KEY_SECRET=xxx

STRIPE_SUBS_KEY_PUBLIC=xxx
