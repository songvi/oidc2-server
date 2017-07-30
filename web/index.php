<?php

use Symfony\Component\Debug\Debug;


require_once __DIR__.'/../vendor/autoload.php';
Debug::enable();
$app = require __DIR__.'/../src/app.php';
//require __DIR__.'/../config/dev.php';

// create an http foundation request implementing OAuth2\RequestInterface
$request = OAuth2\HttpFoundationBridge\Request::createFromGlobals();
$app['request'] = $request;
$app->run($request);