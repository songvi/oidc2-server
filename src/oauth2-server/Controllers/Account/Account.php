<?php

namespace Vuba\OIDC\Controllers\Account;

use Silex\Application;
use Symfony\Component\Config\Util;
use Vuba\AuthN\Exception\ActionNotAllowOnStateException;
use Vuba\AuthN\Exception\ActivationKeyInvalidException;
use Vuba\AuthN\Exception\LoginFailedException;
use Vuba\AuthN\Exception\UserNotFoundException;
use Vuba\AuthN\User\UserFSM;
use Vuba\AuthN\User\UserObject;

class Account
{
    const ACTIVE_STATE_INIT = 0;
    const ACTIVE_STATE_CODE = 1;
    const ACTIVE_STATE_PASSWORD = 2;
    const ACTIVE_USER_TYPE_EMAIL = 'email';
    const ACTIVE_USER_TYPE_TEL = 'tel';

    // Connects the routes in Silex
    public static function addRoutes($routing)
    {
        $routing->get('/', array(new self(), 'login_get'))->bind('login_get');
        $routing->post('/', array(new self(), 'login_post'))->bind('login_post');
        $routing->get('/edit', array(new self(), 'edit_get'))->bind('edit_get');
        $routing->post('/edit', array(new self(), 'edit_post'))->bind('edit_post');
        $routing->get('/logout', array(new self(), 'logout'))->bind('logout');
        $routing->get('/forgotpw', array(new self(), 'forgotpw_get'))->bind('forgotpw_get');
        $routing->post('/forgotpw', array(new self(), 'forgotpw_post'))->bind('forgotpw_post');
        $routing->get('/register', array(new self(), 'register_get'))->bind('register_get');
        $routing->post('/register', array(new self(), 'register_post'))->bind('register_post');
        $routing->get('/active', array(new self(), 'active_get'))->bind('active_get');
        $routing->post('/active', array(new self(), 'active_post'))->bind('active_post');
        $routing->get('/resetpw', array(new self(), 'resetpw_get'))->bind('resetpw_get');
        $routing->post('/resetpw', array(new self(), 'resetpw_post'))->bind('resetpw_post');
        $routing->post('/activepw', array(new self(), 'activeNewpw_post'))->bind('activeNewpw_post');
        $routing->get('/activepw', array(new self(), 'activeNewpw_get'))->bind('activeNewpw_get');
    }

    public function login_get(Application $app)
    {
        // TODO return server info
        // Return account page
        $action_message = "";
        $session = $app['session'];

        if(!empty($session->get('loggedUser'))){
            return $app['twig']->render('welcome.twig', array(
                'action_message' => "",
                'loggedUser' => $app['session']->get('loggedUser')));
        }

        return RenderService::render($app, 'login', $app['translator']->trans('msg000'), $action_message, 'index.twig');
    }

    /**
     *
     */
    public function login_post(Application $app){
        $action_message = "";

        $session = $app['session'];
        if($session->get('loggedUser')){
            return $app->redirect($app['url_generator']->generate('login_get'));
        }

        //var_dump($app['request']);
        $form = RenderService::loginForm($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $postedData = $form->getData();

            $loginResult = false;
            if(isset($postedData['ematel']) &&
                !empty($postedData['ematel']) &&
                isset($postedData['password'])&&
                !empty($postedData['password'])) {
                try {
                    $loginResult = $app['vuba.authn']->login($postedData['ematel'], $postedData['password'], array(), $app['monolog']);
                }
                catch(UserNotFoundException $e){
                    $app['monolog']->warning(sprintf('User %s not found', $postedData['ematel']));
                    $action_message = $app['translator']->trans('msg002', array('%uid%'=> $postedData['ematel']));
                }
                catch(LoginFailedException $e){
                    $app['monolog']->warning(sprintf('User %s logs failed', $postedData['ematel']));
                    $action_message = $app['translator']->trans('msg003', array('%uid%' => $postedData['ematel']));
                }
                catch(ActionNotAllowOnStateException $e){
                    $app['monolog']->warning(sprintf('User %s could not log in', $postedData['ematel']));
                    $action_message = $app['translator']->trans('msg004', array('%uid%' => $postedData['ematel']));
                }
            }

            if($loginResult){
                $session->invalidate();
                $session->set('loggedUser', $postedData['ematel']);
                $session->save();

                if (!empty($app['request']->query->get('authzcallback'))){
                    // TODO call back to authorize
                    /**/
                    return $app->redirect(base64_decode(($app['request']->query->get('authzcallback'))));
                }

                return $app['twig']->render('welcome.twig', array(
                    'action_message' => $app['translator']->trans('msg001', array('%uid%'=>$postedData['ematel'])),
                    'loggedUser' => $app['session']->get('loggedUser')));
            }
        }

        //$action_message = "User name or password incorrect! Please try again";
        return RenderService::render($app, 'login', $app['translator']->trans('msg000'), $action_message, 'index.twig');
    }


    /**
     * @param Application $app
     */
    public function edit_post(Application $app){
        $session = $app['session'];
        // if user has not logged in
        if (empty($session->get('loggedUser'))){
            return $app->redirect($app['url_generator']->generate('login_get'));
        }

        $form = RenderService::editForm($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            $postedData = $form->getData();

            $kv = array();
            $kv['name'] = $postedData['Name'];
            $kv['family_name'] = $postedData['FamilyName'];
            $kv['profile'] = $postedData['Avantar'];
            $kv['preferred_theme'] = $postedData['Theme'];
            $kv['birthdate'] = \DateTime::createFromFormat('d/m/Y', $postedData['BirthDate']);
            $kv['address'] = $postedData['Address'];
            $kv['preferred_lang'] = $postedData['Language'];
            $kv['locale'] = $postedData['Locale'];

            $result = $app['vuba.authn']->modify($session->get('loggedUser'), $kv, array(), $app['monolog']);

            if($result){
                $userData = $app['vuba.authn']->loadUser($session->get('loggedUser'), array(), $app['monolog']);
                $form = RenderService::editForm($app);
                $this->fillForm($form, $userData);
                return RenderService::renderForm($app, $form, $app['translator']->trans('msg010'), $app['translator']->trans('msg011'), 'edit.twig');
            }

        }

        $session = $app['session'];
        if($session->get('loggedUser')) {
            $userData = $app['vuba.authn']->loadUser($session->get('loggedUser'), array(), $app['monolog']);
            $form = RenderService::editForm($app);
            $this->fillForm($form, $userData);
            return RenderService::renderForm($app, $form, $app['translator']->trans('msg010'), $app['translator']->trans('msg012'), 'edit.twig');
        }
    }

    private function fillForm($form, $userData){
        if (!empty($userData) && $userData instanceof UserObject){
            if (!empty($userData->getName()))
                $form->get('Name')->setData($userData->getName());
            if (!empty($userData->getFamilyName()))
                $form->get('FamilyName')->setData($userData->getFamilyName());
            if (!empty($userData->getProfile()))
                $form->get('Avantar')->setData($userData->getProfile());
            if (!empty($userData->getPreferredTheme()))
                $form->get('Theme')->setData($userData->getPreferredTheme());
            if (!empty($userData->getBirthdate()))
                $form->get('BirthDate')->setData($userData->getBirthdate()->format('d/m/Y'));
            if (!empty($userData->getAddress()))
                $form->get('Address')->setData($userData->getAddress());
            if (!empty($userData->getPreferredLang()))
                $form->get('Language')->setData($userData->getPreferredLang());
            if (!empty($userData->getLocale()))
                $form->get('Locale')->setData($userData->getLocale());
        }
    }

    public function edit_get(Application $app){
        $session = $app['session'];
        if($session->get('loggedUser')) {
            $userData = $app['vuba.authn']->loadUser($session->get('loggedUser'), array(), $app['monolog']);
            $form =RenderService::editForm($app);
            $this->fillForm($form, $userData);
            return RenderService::renderForm($app, $form, $app['translator']->trans('msg010'), '', 'edit.twig');
        }
        $returnUrl = $app['url_generator']->generate('login_get');
        return $app->redirect($returnUrl);
    }

    /**
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function register_post(Application $app){
        $form = RenderService::register($app);
        $form->handleRequest($app['request']);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $postedData = $form->getData();

            if(!empty($postedData) && isset($postedData['ematel']))
            {
                $registerResult = $app['vuba.authn']->register($postedData['ematel'], array(), $app['monolog']);

                if ($registerResult) {
                    //$session = $app['session'];
                    //$session->set('active_user', $postedData['ematel']);
                    return $app->redirect($app['url_generator']->generate('active_get', array('active_user' => $postedData['ematel'])));
                }
            }
        }
        return RenderService::render($app, 'register', $app['translator']->trans('msg0501'), $app['translator']->trans('msg0502'), 'edit.twig');
    }

    /**
     *
     */
    public function register_get(Application $app){
        return RenderService::render($app, 'register', $app['translator']->trans('msg0501'), "", 'edit.twig');
    }

    public function active_get(Application $app){
        $session = $app['session'];
        //$session->set('active_state', self::ACTIVE_STATE_INIT);

        // Try to search user id in POST, GET, or SESSION
        $userId = $app['request']->query->get('active_user');
        if (empty($userId)){
            $userId = $app['request']->request->get('active_user');
        }

        // This case, user click on the link in email to go to activation page.
        $activationCode = $app['request']->query->get('activation_code');
        if (!empty($activationCode) && !empty($userId)){
            // Do activation here
            // Verify code and redirect to new password page
            // TODO
            $userObject = $app['vuba.authn']->loadUser($userId, array(), $app['monolog']);
            if($userObject instanceof UserObject){
                if($userObject->getState() == UserFSM::USER_WAIT_FOR_CONFIRMATION || $userObject->getState() == UserFSM::USER_STATE_NORMAL){
                    if ($userObject->getActivationCode() === $activationCode) {
                        return $app->redirect($app['url_generator']->generate('activeNewpw_get', array('active_user' => $app->escape($userId), 'activation_code' => $app->escape($activationCode))));
                    }
                    else {
                        //Return to
                        return RenderService::render($app, 'active', $app['translator']->trans('msg0202'), $app['translator']->trans('msg0201'), 'active.twig');
                    }
                }
            }
        }
        return RenderService::render($app, 'active', $app['translator']->trans('msg0001'), $app['translator']->trans('msg0001'), 'active.twig');
    }

    public function active_post(Application $app){
        $session = $app['session'];
        // Try to search user id in POST, GET, or SESSION
        $userId = $app['request']->query->get('active_user');
        if (empty($userId)){
            $userId = $app['request']->request->get('active_user');
        }

        $form = RenderService::activate($app);
        $form->handleRequest($app['request']);

        $postedData = $form->getData();
        $activationCode = $postedData['activation_code'];
        if(empty($activationCode)){
            $activationCode = $app['request']->query->get('activation_code');
        }
        if ($form->isSubmitted() &&
            $form->isValid() &&
            !empty($userId) &&
            !empty($activationCode)
        ) {
            $userObject = $app['vuba.authn']->loadUser($userId, array(), $app['monolog']);

            if($userObject instanceof UserObject){
                if($userObject->getState() == UserFSM::USER_STATE_NORMAL){
                    if ($userObject->getActivationCode() === $activationCode) {
                        return $app->redirect($app['url_generator']->generate('activeNewpw_get', array('active_user' => $app->escape($userId), 'activation_code' => $app->escape($activationCode))));
                    }
                    else{
                        //Return to
                        return RenderService::render($app, 'active', $app['translator']->trans('msg0001'), $app['translator']->trans('msg0201'), 'active.twig');
                    }
                }
            }
        }
        return RenderService::render($app, 'active', $app['translator']->trans('msg0001'), $app['translator']->trans('msg0001'), 'active.twig');
    }

    public function activeNewpw_get(Application $app){
        return RenderService::render($app, 'activepw', $app['translator']->trans('msg0001'), $app['translator']->trans('msg0001'), 'activepwnew.twig');
    }

    public function activeNewpw_post(Application $app){
        $session = $app['session'];
        //var_dump($app['request']);
        $userId = $app['request']->query->get('active_user');
        if (empty($userId)){
            $userId = $app['request']->request->get('active_user');
        }

        $activationCode = $app['request']->query->get('activation_code');
        if (empty($activationCode)){
            $activationCode = $app['request']->request->get('activation_code');
        }

        $form = RenderService::activatePassword($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid() && !empty($userId)) {
            $result = false;
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $postedData = $form->getData();
            if(!empty($activationCode)) {
                $userObject = $app['vuba.authn']->loadUser($userId, array(), $app['monolog']);
                if(($userObject instanceof UserObject) &&
                    ($postedData['newpassword'] === $postedData['newpasswordconfirmation']))
                {
                    try {
                        $result = $app['vuba.authn']->confirmForgotPw($userObject->getExtuid(), $postedData['newpassword'], $activationCode, array(), $app['monolog']);
                    }
                    catch(ActionNotAllowOnStateException $e){
                        return $app->redirect($app['url_generator']->generate('login_get'));
                    }
                    catch(ActivationKeyInvalidException $e){

                    }
                }
            }

            if($result){
                $session->invalidate();
                $session->set('loggedUser', $userObject->getExtuid());
                return $app->redirect($app['url_generator']->generate('login_get'));
            }
            return $app->redirect($app['url_generator']->generate('login_get'));
        }
        return $app->redirect($app['url_generator']->generate('login_get'));
    }

    public function resetpw_get(Application $app){
        return RenderService::render($app, 'resetpw', $app['translator']->trans('msg0401'), "", 'resetpw.twig');
    }

    public function resetpw_post(Application $app){
        $session = $app['session'];
        //var_dump($app['request']);
        $form = RenderService::resetPW($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            $postedData = $form->getData();
            $result = false;
            if(!empty($postedData['oldpassword']) &&
            !empty($postedData['newpassword'] &&
                ($postedData['newpassword'] === $postedData['newpwconfirm']))
            )
             $result = $app['vuba.authn']->resetpw($session->get('loggedUser'), $postedData['oldpassword'], $postedData['newpwconfirm'], array(), $app['monolog']);

            if($result){
                return $app->redirect($app['url_generator']->generate('login_get'));
            }
            $app->redirect($app['url_generator']->generate('resetpw_post'));
        }

        return RenderService::render($app, 'resetpw', $app['translator']->trans('msg0401'), $app['translator']->trans('msg0402'), 'resetpw.twig');
    }

    public function forgotpw_get(Application $app){
        return RenderService::render($app, 'forgotpw', $app['translator']->trans('msg0301'), $app['translator']->trans('msg0302'), 'forgotpw.twig');
    }

    public function forgotpw_post(Application $app){
        $session = $app['session'];
        $session->set('count', 1);
        $form = RenderService::forgotPW($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            $postedData = $form->getData();
            if(!empty($postedData['ematel'])) {
                $session->set('active_user', $postedData['ematel']);
                $result = $app['vuba.authn']->forgotpw($postedData['ematel'], array(), $app['monolog']);
            }
            if($result){
                return $app->redirect($app['url_generator']->generate('active_get', array('active_user' => $postedData['ematel'])));
            }
        }
        // Maybe user has done and in "waitforconfirmation" state
        // Just return to active page
        return $app->redirect($app['url_generator']->generate('login_get'));
    }

    public function logout(Application $app){
        $app['session']->invalidate();
        return $app->redirect($app['url_generator']->generate('login_get'));
    }
}
