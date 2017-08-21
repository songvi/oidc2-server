<?php

use Silex\Application;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Symfony\Component\Translation\Loader\YamlFileLoader;

$app = new Application();
$app->register(new RoutingServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app->register(new Silex\Provider\LocaleServiceProvider());
//$app->register(new Silex\Provider\SessionServiceProvider());


$app->register(new TranslationServiceProvider(), array(
    'locale_fallbacks' => array('en'),
    'translator.domains' => array(),
));
$app['locales.supported'] = ['vi', 'en', 'fr', 'de'];

$app['debug'] = true;

$app->register(new MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../var/logs/silex_dev.log',
));

$app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());

    $translator->addResource('yaml', __DIR__.'/locales/en.yml', 'en');
    $translator->addResource('yaml', __DIR__.'/locales/de.yml', 'de');
    $translator->addResource('yaml', __DIR__.'/locales/fr.yml', 'fr');
    $translator->addResource('yaml', __DIR__.'/locales/fr.yml', 'vi');

    return $translator;
});

require __DIR__.'/routes.php';

return $app;