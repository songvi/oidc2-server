<?php

namespace Vuba\OIDC\Controllers\Oauth2;

use OAuth2\Storage\UserCredentialsInterface;

class VuBaUserCredential implements  UserCredentialsInterface{

    protected $authn;
    protected $context;
    protected $logger;

    public function __construct($authn, $context, $logger){
        $this->authn = $authn;
        $this->context = $context;
        $this->logger = $logger;
    }
    public function checkUserCredentials($username, $password)
    {
        return $this->authn->login($username, $password, $this->context, $this->logger);
    }

    /**
     * @param string $username - username to get details for
     * @return array|false     - the associated "user_id" and optional "scope" values
     *                           This function MUST return FALSE if the requested user does not exist or is
     *                           invalid. "scope" is a space-separated list of restricted scopes.
     * @code
     *     return array(
     *         "user_id"  => USER_ID,    // REQUIRED user_id to be stored with the authorization code or access token
     *         "scope"    => SCOPE       // OPTIONAL space-separated list of restricted scopes
     *     );
     * @endcode
     */
    public function getUserDetails($username)
    {
        $user =  $this->authn->loadUser($username, $this->context, $this->logger);
        return array('user_id' => $user->getUuid());
    }
}