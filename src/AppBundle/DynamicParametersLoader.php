<?php
// Shouldn't need to do this but Symfony is not picking them up automatically

$container->setParameter('postmark_api_key', getenv('SYMFONY__POSTMARK_API_KEY'));
$container->setParameter('aws_key', getenv('SYMFONY__AWS_KEY'));
$container->setParameter('aws_secret', getenv('SYMFONY__AWS_SECRET'));

// Clickatell credentials
$container->setParameter('clickatell_username', getenv('SYMFONY__CT_USER'));
$container->setParameter('clickatell_password', getenv('SYMFONY__CT_PASS'));
$container->setParameter('clickatell_api_id', getenv('SYMFONY__CT_API'));

// Stripe card payments for Connect (client's account via oAuth)
$container->setParameter('stripe_client_id', getenv('SYMFONY__STRIPE_CLIENT'));

// Stripe card payments for subscription
$container->setParameter('billing_public_key', getenv('STRIPE_SUBS_KEY_PUBLIC'));
$container->setParameter('billing_secret_key', getenv('STRIPE_SUBS_KEY_SECRET'));

// Social FaceBook Twitter Google oAuth
$container->setParameter('facebook_client_id', getenv('FACEBOOK_CLIENT_ID'));
$container->setParameter('facebook_client_secret', getenv('FACEBOOK_CLIENT_SECRET'));
$container->setParameter('google_client_id', getenv('GOOGLE_CLIENT_ID'));
$container->setParameter('google_client_secret', getenv('GOOGLE_CLIENT_SECRET'));
$container->setParameter('twitter_consumer_key', 'E1fgYnTW52OW7PaGySShB3sDo');
$container->setParameter('twitter_consumer_secret', 'pTzJKAG2KUNk25d7rf6GfK0wap11HLZbbHE9B80j8CvNCLq8Jd');

// These are overwritten in src/AppBundle/DependencyInjection/Compiler/MailChimpApiCredentials.php
$container->setParameter('mailchimp_api_key', getenv('MAILCHIMP_API_KEY'));
$container->setParameter('mailchimp_list_id', getenv('MAILCHIMP_LIST_ID'));
$container->setParameter('mailchimp_default_list', "temp");

$container->setParameter('rollbar_access_token', getenv('ROLLBAR_ACCESS_TOKEN'));
$container->setParameter('rollbar_client_token', getenv('ROLLBAR_CLIENT_TOKEN'));

// Still required
$container->setParameter('locales', ["en", "fr"]);