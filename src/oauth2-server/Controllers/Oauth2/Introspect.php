<?php

namespace Vuba\OIDC\Controller;

use Silex\Application;

class Introspect
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
          $routing->post('/introspect', array(new self(), 'introspect'))->bind('introspect');
    }

    /**
     * The user is directed here by the client in order to authorize the client app
     * to access his/her data
     */
    public function introspect(Application $app)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        $server = $app['oauth_server'];

         // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        // validate the authorize request.  if it is invalid, redirect back to the client with the errors in tow
        if ($server->validateIntrospectRequest($app['request'], $response)) {
            return $server->getResponse();
        }
    }
}
