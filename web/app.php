<?php

use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;

// Enable APC for autoloading to improve performance.
// You should change the ApcClassLoader first argument to a unique prefix
// in order to prevent cache key conflicts with other applications
// also using APC.
/*
$apcLoader = new ApcClassLoader(sha1(__FILE__), $loader);
$loader->unregister();
$apcLoader->register(true);
*/

unset($_SERVER["HTTP_FORWARDED"]);

/**
 * @var Composer\Autoload\ClassLoader
 */
$loader = require __DIR__.'/../app/autoload.php';
include_once __DIR__.'/../var/bootstrap.php.cache';

$kernel = new AppKernel('prod', false);

$kernel->loadClassCache();

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();

// Line required for Heroku (cache layer?) and SSL redirect
Request::setTrustedProxies(array($request->server->get('REMOTE_ADDR')));

$response = $kernel->handle($request);
//$response->headers->set('Access-Control-Allow-Origin', '*');
$response->send();
$kernel->terminate($request, $response);
