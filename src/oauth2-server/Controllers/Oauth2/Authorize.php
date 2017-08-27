<?php

namespace Vuba\OIDC\Controllers\Oauth2;

use Silex\Application;

class Authorize
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/authorize', array(new self(), 'authorize'))->bind('authorize');
        $routing->post('/authorize', array(new self(), 'authorizeFormSubmit'))->bind('authorize_post');
    }

    /**
     * The user is directed here by the client in order to authorize the client app
     * to access his/her data
     */
    public function authorize(Application $app)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        $server = $app['oauth_server'];

         // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        // validate the authorize request.  if it is invalid, redirect back to the client with the errors in tow
        if (!$server->validateAuthorizeRequest($app['request'], $response)) {
            return $server->getResponse();
        }

        // display the "do you want to authorize?" form
        /*
        return $app['twig']->render('server/authorize.twig', array(
            'client_id' => $app['request']->query->get('client_id'),
            'response_type' => $app['request']->query->get('response_type')
        ));*/

        $session = $app['session'];
        $user = $session->get('loggedUser');
        if (!empty($user)){
            return $server->handleAuthorizeRequest($app['request'], $response, true);
        }
        // redirect to /account/
        // TODO
        return $app->redirect($app['url_generator']->generate('login_get', array(
            'authzcallback' => base64_encode($app['request']->getRequestUri())
        )));
    }

    /**
     * This is called once the user decides to authorize or cancel the client app's
     * authorization request
     */
    public function authorizeFormSubmit(Application $app)
    {
        // get the oauth server (configured in src/OAuth2Demo/Server/Server.php)
        $server = $app['oauth_server'];

         // get the oauth response (configured in src/OAuth2Demo/Server/Server.php)
        $response = $app['oauth_response'];

        // check the form data to see if the user authorized the request
        $authorized = (bool) $app['request']->request->get('authorize');

        // call the oauth server and return the response
        return $server->handleAuthorizeRequest($app['request'], $response, $authorized);
    }
}
