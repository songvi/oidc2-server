<?php
$app->mount( '/oauth2', new Vuba\OIDC\Controller\Oauth2Controller());
$app->mount( '/account', new Vuba\OIDC\Controller\Account());
