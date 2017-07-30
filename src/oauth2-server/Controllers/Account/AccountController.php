<?php

namespace Vuba\OIDC\Controllers\Account;

use Silex\Api\ControllerProviderInterface;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Application;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\Asset\VersionStrategy;
use Silex\Provider\CsrfServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once 'Account.php';

class AccountController implements ControllerProviderInterface
{
    public function setup(Application $app){

        // create session object and start it
        $app->register(new SessionServiceProvider(), array(
            'session.storage.options' => array(
                                        'session.name' => 'vubadev')
        ));


        if (!$app['session']->isStarted()) {
            $app['session']->start();
        }


        $app->register(new ValidatorServiceProvider());
        //$app->register(new SaxulumValidatorProvider());

        $app->register(new FormServiceProvider());
        $app->register(new CsrfServiceProvider());
        //$app->register(new RoutingServiceProvider());

        $app->register(new TwigServiceProvider(), array(
            'twig.path' => __DIR__.'/../../../../views',
            'twig.options' => array(
                'cache' => __DIR__.'/../../../../var/cache/twig'
            )
        ));

        $app->register(new AssetServiceProvider(),array(
            'assets.version' => 'v1',
            'assets.version_format' => '%s?version=%s',
            'assets.named_packages' => array(
                'css' => array(
                    'base_urls' => 'http://static.dev.php/css'),
                'images' => array(
                    'base_urls' => array('http://static.dev.php')),
                'js' => array(
                    'base_urls' => array('http://static.dev.php/js')),
            ),
        ));

        $app['asset_root'] = 'http://static.dev.php';
        // setup vuba\ auth-n  object


        $request = Request::createFromGlobals();
        $app['request'] = $request;
        $app['response'] = new Response();

    }
    public function connect(Application $app){
        $this->setup($app);
        $routing = $app['controllers_factory'];
        Account::addRoutes($routing);
        return  $routing;
    }
}
