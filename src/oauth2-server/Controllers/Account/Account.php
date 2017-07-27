<?php

namespace Vuba\OIDC\Controller;

use Silex\Application;

class Account
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/account', array(new self(), 'getaccount'))->bind('getaccount');
        $routing->post('/account', array(new self(), 'postaccount'))->bind('postaccount');
    }

    public function getaccount(Application $app)
    {
        // TODO return server info
        // Return account page
        //
    }

    public function postaccount(Application $app){

    }

    public function postedit(Application $app){

    }

    public function getedit(Application $app){

    }
}
