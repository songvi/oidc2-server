<?php

namespace Vuba\OIDC\Controller;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class UserManagementController implements ControllerProviderInterface
{
    public function setup(Application $app){
        // setup vuba\ auth-n  object
    }
    public function connect(Application $app){
        $this->setup($app);

        $routing = $app['controllers_factory'];




        return  $routing;
    }
}
