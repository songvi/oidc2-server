<?php

namespace Vuba\OIDC\Controllers\Oauth2;

use OAuth2\Storage\PdoVuba;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use OAuth2\HttpFoundationBridge\Response as BridgeResponse;
use OAuth2\Server as OAuth2Server;
use Vuba\AuthN\Service\ConfServiceYaml;
use OAuth2\Storage\Memory;
use OAuth2\OpenID\GrantType\AuthorizationCode;
use OAuth2\GrantType\UserCredentials;
use OAuth2\GrantType\RefreshToken;
use Vuba\AuthN\AuthN;

class Oauth2Controller implements  ControllerProviderInterface
{
    public function setup(Application $app){
        // ensure our Sqlite database exists

        if (!file_exists($sqliteFile = __DIR__.'/../../../data/oauth.sqlite')) {
            $this->generateSqliteDb();
        }

        $config = new ConfServiceYaml(__DIR__.'/../../../../config/config.yml');
        $authn = new AuthN($config);
        $app['vuba.authn'] = $authn;

        // create PDO-based sqlite storage
        $storage = new PdoVuba(array('dsn' => 'sqlite:'.$sqliteFile));
        $userCredential = new VuBaUserCredential($app['vuba.authn'], array(), $app['monolog']);
        $storage->setUserCredentialService($userCredential);

        // create array of supported grant types
        $grantTypes = array(
            'authorization_code' => new AuthorizationCode($storage),
            'user_credentials'   => new UserCredentials($storage),
            'refresh_token'      => new RefreshToken($storage, array(
                'always_issue_new_refresh_token' => true,
            )),
        );

        // instantiate the oauth server
        $server = new OAuth2Server($storage, array(
            'enforce_state' => true,
            'allow_implicit' => true,
            'use_openid_connect' => true,
            'issuer' => $_SERVER['HTTP_HOST'],
        ),$grantTypes);

        $server->addStorage($this->getKeyStorage(), 'public_key');

        // add the server to the silex "container" so we can use it in our controllers (see src/OAuth2Demo/Server/Controllers/.*)
        $app['oauth_server'] = $server;

        /**
         * add HttpFoundataionBridge Response to the container, which returns a silex-compatible response object
         * @see (https://github.com/bshaffer/oauth2-server-httpfoundation-bridge)
         */
        $app['oauth_response'] = new BridgeResponse();
    }

    public function connect(Application $app){
        $this->setup($app);
        $routing = $app['controllers_factory'];
        /* Set corresponding endpoints on the controller classes */
        Authorize::addRoutes($routing);
        Token::addRoutes($routing);
        Resource::addRoutes($routing);
        Introspect::addRoutes($routing);

        return $routing;
    }
    private function generateSqliteDb()
    {
        include_once($this->getProjectRoot().'/data/rebuild_db.php');
    }

    private function getKeyStorage()
    {
        $publicKey  = file_get_contents($this->getProjectRoot().'/data/pubkey.pem');
        $privateKey = file_get_contents($this->getProjectRoot().'/data/privkey.pem');

        // create storage
        $keyStorage = new Memory(array('keys' => array(
            'public_key'  => $publicKey,
            'private_key' => $privateKey,
        )));

        return $keyStorage;
    }

    private function getProjectRoot()
    {
        return dirname(dirname(dirname(__DIR__)));
    }
}
