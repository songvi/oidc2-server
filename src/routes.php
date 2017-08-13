<?php

use Vuba\OIDC\Controllers\Account\AccountController;
use Vuba\OIDC\Controllers\Oauth2\Oauth2Controller;
use Symfony\Component\HttpFoundation\Request;


$localeMiddleWare = function (\Symfony\Component\HttpFoundation\Request $request) use($app){
    $locale = 'en';
    // quick and dirty ... try to detect the favorised language - to be improved!
    if (!is_null($request->server->get('HTTP_ACCEPT_LANGUAGE'))) {
        // break up string into pieces (languages and q factors)
        preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
            $request->server->get('HTTP_ACCEPT_LANGUAGE'), $lang_parse);
        if (count($lang_parse[1]) > 0) {
            foreach ($lang_parse[1] as $lang) {
                if (false === (strpos($lang, '-'))) {
                    // only the country sign like 'de'
                    $locale = strtolower($lang);
                } else {
                    // perhaps something like 'de-DE'
                    $locale = strtolower(substr($lang, 0, strpos($lang, '-')));
                }
                break;
            }
        }
        if(!in_array($locale, $app['locales.supported']))
        {
            $locale = 'en';
        }
        $app['translator']->setLocale($locale);
        $app['monolog']->addDebug('Set locale to '.$locale);
    }
};

$sessionMiddleWare = function (Request $request) use ($app){
    //Session middleware
    // Do nothing
};

$redirectToLoginMiddleWare = function (Request $request) use($app) {
    // if(!empty($app) && isset($app['session']) && isset())
};

$app->mount( '/oauth2', new Oauth2Controller())
    ->before($localeMiddleWare);


$app->mount( '/account', new AccountController())
    ->before($localeMiddleWare);

