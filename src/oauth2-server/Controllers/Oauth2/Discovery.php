<?php

namespace Vuba\OIDC\Controller;

use Silex\Application;

class Discovery
{
    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/.well-known/webfinger', array(new self(), 'authorize'))->bind('discovery');
    }

    public function discovery(Application $app)
    {
        // TODO return server info
        // https://openid.net/specs/openid-connect-discovery-1_0.html
    }
}
