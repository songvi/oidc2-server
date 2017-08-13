<?php

namespace Vuba\OIDC\Controllers\Account;

use Silex\Application;
use Silex\Controller;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Config\Util;
use Symfony\Component\HttpFoundation\Response;
use Vuba\AuthN\User\UserFSM;
use Vuba\AuthN\User\UserObject;

class Account
{
    const REGISTER_MESSAGE = "Sign up for free";
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
        $action_message = "hi, this is form message";
        $session = $app['session'];
        if(!empty($session->get('loggedUser'))){
            return $app['twig']->render('welcome.twig', array(
                'action_message' => "",
                'loggedUser' => $app['session']->get('loggedUser')));
        }

        return $app['twig']->render('index.twig', array(
            'form_name' => 'Login to my site',
            'action_message' => $action_message,
            'form' => $this->renderLoginForm($app)->createView(),
        ));
    }
    public function login_post(Application $app){

        $session = $app['session'];

        if($session->get('loggedUser')){
            return $app->redirect($app['url_generator']->generate('login_get'));
        }

        //var_dump($app['request']);
        $form = $this->renderLoginForm($app);
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
                $loginResult = $app['vuba.authn']->login($postedData['ematel'], $postedData['password']);
            }

            if($loginResult){
                $session->invalidate();
                $session->set('loggedUser', $postedData['ematel']);
                $session->save();
                return $app['twig']->render('welcome.twig', array(
                    'action_message' => "",
                    'loggedUser' => $app['session']->get('loggedUser')));
            }
        }

        $action_message = "User name or password incorrect! Please try again";
        return $app['twig']->render('index.twig', array(
            'form_name' => 'Login to my site',
            'action_message' => $action_message,
            'form' => $this->renderLoginForm($app)->createView()));
    }
    private function renderLoginForm(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('ematel', TextType::class, array(
                // TODO add validation email / tel
                'constraints' => new Assert\NotBlank(),
                'label' => 'Email Adress',
                'label_attr' => array(
                    'class' => 'sr-only',
                    'for' => 'form_username',
                ),
                'attr' => array(
                    'class' => 'form-control form-username oidc-form',
                    'placeholder'=> 'Email or telephone ...',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('password', PasswordType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Password',
                'label_attr' => array(
                    'class' => 'sr-only',
                    'for' => 'form_username',
                ),
                'attr' => array(
                    'class' => 'form-control form-password oidc-form',
                    'placeholder'=> 'Password',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            /*
            ->add('remember-me', CheckboxType::class, array(
                'attr' => array(
                    'required' => false,
                ),
                'required' => false,
            ))*/
            ->add('login', SubmitType::class, [
                'label' => 'Login',
                'attr' => array(
                    'class' => 'btn btn-lg btn-primary btn-block',
                )
            ])
            ->setAction($app['url_generator']->generate('login_post'))
            ->setMethod('POST')
            ->getForm();
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

        $form = $this->renderEdit($app);
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

            $result = $app['vuba.authn']->modify($session->get('loggedUser'), $kv);

            if($result){
                $userData = $app['vuba.authn']->loadUser($session->get('loggedUser'));
                $form = $this->renderEdit($app);
                $this->fillForm($form, $userData);

                return $app['twig']->render('edit.twig', array(
                    'form_name' => 'Modify your personal infos',
                    'action_message' => "Action done!",
                    'form' => $form->createView()
                ));
            }

        }

        $session = $app['session'];
        if($session->get('loggedUser')) {
            $userData = $app['vuba.authn']->loadUser($session->get('loggedUser'));
            $form = $this->renderEdit($app);
            $this->fillForm($form, $userData);
            return $app['twig']->render('edit.twig', array(
                'form_name' => 'Modify your personal infos',
                'action_message' => "Action failed, please try again!",
                'form' => $form->createView()
            ));
        }
    }

    private function fillForm(&$form, $userData){
        if (!empty($userData) && $userData instanceof UserObject){
            $form->get('Name')->setData($userData->getName());
            $form->get('FamilyName')->setData($userData->getFamilyName());
            $form->get('Avantar')->setData($userData->getProfile());
            $form->get('Theme')->setData($userData->getPreferredTheme());
            $form->get('BirthDate')->setData($userData->getBirthdate()->format('d/m/Y'));
            $form->get('Address')->setData($userData->getAddress());
            $form->get('Language')->setData($userData->getPreferredLang());
            $form->get('Locale')->setData($userData->getLocale());
        }
    }

    public function edit_get(Application $app){
        $session = $app['session'];
        if($session->get('loggedUser')) {
            $userData = $app['vuba.authn']->loadUser($session->get('loggedUser'));
            $form = $this->renderEdit($app);
            $this->fillForm($form, $userData);
            return $app['twig']->render('edit.twig', array(
                'form_name' => 'Modify your personal infos',
                'action_message' => "",
                'form' => $form->createView()
                ));
        }
        //$app['url_generator']->generate('my-route-name');
        $returnUrl = $app['url_generator']->generate('login_get');
        return $app->redirect($returnUrl);
        //return $app['twig']->render('index.twig', array('form' => $this->renderLoginForm($app)->createView()));
    }
    private function renderEdit(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('Name', TextType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Display Name',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Name',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('FamilyName', TextType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Display Name',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Family Name',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('Avantar', TextType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Avantar',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Avantar',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('Theme', TextType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Theme',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Prefered theme',
                    'required' => true,
                    'autofocus' => true,
                )
            ))

            ->add('BirthDate', TextType::class, array(
                //'constraints' => new Assert\Date(),
                'label' => 'Date of birth',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Date of birth',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('Address', TextType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Address',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Address',
                    'required' => true,
                    'autofocus' => true,
                )
            ))

            ->add('Language', TextType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Prefered language',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Prefered language',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('Locale', TextType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Locale',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Locale',
                    'required' => true,
                    'autofocus' => true,
                )
            ))

            ->add('save', SubmitType::class, [
                'label' => 'Save',
                'attr' => array(
                    'class' => 'btn btn-lg btn-primary btn-block',
                )
            ])
            ->setAction($app['url_generator']->generate('edit_post'))
            ->setMethod('POST')
            ->getForm();
    }

    /**
     * @param Application $app
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function register_post(Application $app){
        $form = $this->renderRegister($app);
        $form->handleRequest($app['request']);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $postedData = $form->getData();

            if(!empty($postedData) && isset($postedData['ematel']))
            {
                $registerResult = $app['vuba.authn']->register($postedData['ematel']);

                if ($registerResult) {
                    return $app->redirect($app['url_generator']->generate('active_get'));
                }
            }
        }
        return $app['twig']->render('register.twig', array(
            'form_name' => FormMessages::REGISTER_FORM_NAME,
            'action_message' => FormMessages::REGISTER_MSG_ERROR,
            'form' => $this->renderRegister($app)->createView()
        ));
    }
    public function register_get(Application $app){
        return $app['twig']->render('register.twig', array(
            'form_name' => FormMessages::REGISTER_FORM_NAME,
            'action_message' => FormMessages::REGISTER_MSG,
            'form' => $this->renderRegister($app)->createView()
        ));
    }
    private function renderRegister(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('ematel', TextType::class, array(
                // TODO Add validator Tel / Email
                'constraints' => new Assert\NotBlank(),
                'label' => 'Email Adress',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Email address',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('register', SubmitType::class, [
                'label' => 'Register',
                'attr' => array(
                    'class' => 'btn btn-lg btn-primary btn-block',
                )
            ])
            ->setAction($app['url_generator']->generate('register_post'))
            ->setMethod('POST')
            ->getForm();
    }


    public function active_get(Application $app){
        return $app['twig']->render('active.twig', array(
            'form_name' => FormMessages::FORGOTPW_ACTIVATION_FORM_NAME,
            'action_message' => FormMessages::FORGOTPW_ACTIVATION_MSG_NAME,
            'form' => $this->renderActivation($app)->createView()
        ));
    }

    public function active_post(Application $app){
        $session = $app['session'];
        //var_dump($app['request']);
        $form = $this->renderActivation($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $postedData = $form->getData();

            $userObject = $app['vuba.authn']->searchUser(array('activation_code' => $postedData['activationcode']));

            if($userObject instanceof UserObject){
                if($userObject->getState() == UserFSM::USER_WAIT_FOR_CONFIRMATION){
                    $session->set('activation_code', $postedData['activationcode']);
                    return $app->redirect($app['url_generator']->generate('activeNewpw_get'));
                }
            }
        }

    }

    public function activeNewpw_get(Application $app){
        return $app['twig']->render('activepwnew.twig', array(
            'form_name' => FormMessages::FORGOTPW_ACTIVATION_FORM_NAME,
            'action_message' => FormMessages::FORGOTPW_ACTIVATION_NEWPW_MSG,
            'form' => $this->renderActivePasswordCreation($app)->createView()
        ));
    }
    public function activeNewpw_post(Application $app){
        $session = $app['session'];
        //var_dump($app['request']);
        $form = $this->renderActivePasswordCreation($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            $result = false;
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $postedData = $form->getData();
            $activationCode = $session->get('activation_code');
            if(!empty($activationCode)) {
                $userObject = $app['vuba.authn']->searchUser(array('activation_code' => $activationCode));
                if(($userObject instanceof UserObject) &&
                    ($postedData['newpassword'] === $postedData['newpasswordconfirmation']))
                {
                    $result = $app['vuba.authn']->confirm($userObject->getExtuid(), $postedData['newpassword'],$activationCode);
                }
            }

            if($result){
                $session->invalidate();
                $session->set('loggedUser', $userObject->getExtuid());
                return $app->redirect($app['url_generator']->generate('login_get'));
            }
            $app->redirect($app['url_generator']->generate('login_get'));
        }
        $app->redirect($app['url_generator']->generate('login_get'));
    }
    private function renderActivation(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('activationcode', TextType::class, array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    // TODO check length
                    //new Assert\Length(array('min', 6)),
                ),
                'label' => 'Activation code',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Activation code',
                    'required' => true,
                    'autofocus' => true,
                )
            ))

            ->add('active', SubmitType::class, [
                'label' => 'Active',
                'attr' => array(
                    'class' => 'btn btn-lg btn-primary btn-block',
                )
            ])
            ->setAction($app['url_generator']->generate('active_post'))
            ->setMethod('POST')
            ->getForm();
    }

    public function renderActivePasswordCreation(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('newpassword', PasswordType::class, array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    // TODO check length
                    //new Assert\Length(array('min', 6)),
                ),
                'label' => 'New password',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'New password',
                    'required' => true,
                    'autofocus' => true,
                )
            ))

            ->add('newpasswordconfirmation', PasswordType::class, array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    // TODO check length
                    //new Assert\Length(array('min', 6)),
                ),
                'label' => 'Password confirmation',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Password confirmation',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('createpw', SubmitType::class, [
                'label' => 'Save',
                'attr' => array(
                    'class' => 'btn btn-lg btn-primary btn-block',
                )
            ])
            ->setAction($app['url_generator']->generate('activeNewpw_post'))
            ->setMethod('POST')
            ->getForm();
    }

    public function resetpw_get(Application $app){
        return $app['twig']->render('resetpw.twig', array(
            'form_name' => 'Do you want to change your password ?',
            'action_message' => "",
            'form' => $this->renderResetpw($app)->createView()
        ));
    }
    public function resetpw_post(Application $app){
        $session = $app['session'];
        //var_dump($app['request']);
        $form = $this->renderResetpw($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            $postedData = $form->getData();
            $result = false;
            if(!empty($postedData['oldpassword']) &&
            !empty($postedData['newpassword'] &&
                ($postedData['newpassword'] === $postedData['newpwconfirm']))
            )
             $result = $app['vuba.authn']->resetpw($session->get('loggedUser'), $postedData['oldpassword'], $postedData['newpwconfirm']);

            if($result){
                return $app->redirect($app['url_generator']->generate('login_get'));
            }
            $app->redirect($app['url_generator']->generate('resetpw_post'));
        }

        return $app['twig']->render('resetpw.twig', array(
            'form_name' => 'Do you want to change your password ?',
            'action_message' => "Action failed. Please try again",
            'form' => $this->renderResetpw($app)->createView()
        ));
    }
    private function renderResetpw(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('oldpassword', PasswordType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Old password oidc-form',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'Old password',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('newpassword', PasswordType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'New password',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'New password',
                    'required' => true,
                    'autofocus' => true,
                )
            ))

            ->add('newpwconfirm', PasswordType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'New password confirmation',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control oidc-form',
                    'placeholder'=> 'New password confirmation',
                    'required' => true,
                    'autofocus' => true,
                )
            ))

            ->add('changepw', SubmitType::class, [
                'label' => 'Change',
                'attr' => array(
                    'class' => 'btn btn-lg btn-primary btn-block',
                )
            ])
            ->setAction($app['url_generator']->generate('resetpw_post'))
            ->setMethod('POST')
            ->getForm();
    }

    private function renderForgotpw(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('ematel', TextType::class,array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Enter your email or telephone number',
                'label_attr' => array(
                    'class' => 'sr-only',
                    'for' => 'form_username',
                ),
                'attr' => array(
                    'class' => 'form-control form_username  oidc-form',
                    'placeholder' => 'Email or telephone number',
                    'required' => true,
                    'autofocus' => true,
                )))

            ->add('btnforgotpw', SubmitType::class, [
                'label' => 'Get activation code',
                'attr' => array(
                    'class' => 'btn btn-lg btn-primary btn-block',
                )
            ])
                ->setAction($app['url_generator']->generate('forgotpw_post'))
                ->setMethod('POST')
                ->getForm();
    }

    public function forgotpw_get(Application $app){
        return $app['twig']->render('forgotpw.twig', array(
            'form_name' => 'You don\'t remember your password ?',
            'action_message' => "Just enter email or telephone number for activation code",
            'form' => $this->renderForgotpw($app)->createView()
        ));
    }

    public function forgotpw_post(Application $app){
        $session = $app['session'];
        $session->set('count', 1);
        $form = $this->renderForgotpw($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            $postedData = $form->getData();
            if(!empty($postedData['ematel'])) {
                $result = $app['vuba.authn']->forgotpw($postedData['ematel']);
            }
            if($result){
                return $app->redirect($app['url_generator']->generate('active_get'));
            }
        }

        return $app['twig']->render('resetpw.twig', array(
            'form_name' => FormMessages::FORGOTPW_ACTIVATION_FORM_NAME,
            'action_message' => FormMessages::FORGOTPW_ACTIVATION_MSG_NAME,
            'form' => $this->renderForgotpw($app)->createView()
        ));
    }

    public function logout(Application $app){
        $app['session']->invalidate();
        return $app->redirect($app['url_generator']->generate('login_get'));
    }
}
