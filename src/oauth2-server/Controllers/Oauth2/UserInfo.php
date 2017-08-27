<?php

//handleUserInfoRequest

namespace Vuba\OIDC\Controllers\Oauth2;

use Silex\Application;

class UserInfo
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->post('/userinfo', array(new self(), 'UserInfo'))->bind('userinfo');
    }

    /**
     * The user is directed here by the client in order to authorize the client app
     * to access his/her data
     */
    public function UserInfo(Application $app)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        $server = $app['oauth_server'];

        // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        // validate the authorize request.  if it is invalid, redirect back to the client with the errors in tow
        return $server->handleUserInfoRequest($app['request'], $response);
    }
}
