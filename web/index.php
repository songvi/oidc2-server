<?php

use Symfony\Component\Debug\Debug;


require_once __DIR__.'/../vendor/autoload.php';
Debug::enable();
$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../config/dev.php';
$app->run();