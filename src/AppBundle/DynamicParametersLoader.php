<?php
// Shouldn't need to do this but Symfony is not picking them up automatically

$container->setParameter('postmark_api_key', getenv('SYMFONY__POSTMARK_API_KEY'));
$container->setParameter('aws_key', getenv('SYMFONY__AWS_KEY'));
$container->setParameter('aws_secret', getenv('SYMFONY__AWS_SECRET'));

// Stripe card payments for Connect (client's account via oAuth)
$container->setParameter('stripe_client_id', getenv('SYMFONY__STRIPE_CLIENT'));

// Stripe card payments for subscription
$container->setParameter('billing_public_key', getenv('STRIPE_SUBS_KEY_PUBLIC'));
$container->setParameter('billing_secret_key', getenv('STRIPE_SUBS_KEY_SECRET'));

// These are overwritten in src/AppBundle/DependencyInjection/Compiler/MailChimpApiCredentials.php
$container->setParameter('mailchimp_api_key', getenv('MAILCHIMP_API_KEY'));
$container->setParameter('mailchimp_list_id', getenv('MAILCHIMP_LIST_ID'));
$container->setParameter('mailchimp_default_list', "temp");

$container->setParameter('rollbar_access_token', getenv('ROLLBAR_ACCESS_TOKEN'));
$container->setParameter('rollbar_client_token', getenv('ROLLBAR_CLIENT_TOKEN'));

// Still required
$container->setParameter('locales', ["en", "fr"]);