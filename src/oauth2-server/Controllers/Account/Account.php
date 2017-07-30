<?php

namespace Vuba\OIDC\Controllers\Account;

use Silex\Application;
use Silex\Controller;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Config\Util;

class Account
{
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

        /*
        $routing->get('/logout', array(new self(), 'logout'))->bind('logout');
        $routing->get('/logout', array(new self(), 'logout'))->bind('logout');
        $routing->get('/logout', array(new self(), 'logout'))->bind('logout');
        $routing->get('/logout', array(new self(), 'logout'))->bind('logout');
        $routing->get('/logout', array(new self(), 'logout'))->bind('logout');
        */
    }

    public function login_get(Application $app)
    {
        // TODO return server info
        // Return account page

        $session = $app['session'];
        if(!empty($session->get('loggedUser'))){
            return $app['twig']->render('welcome.twig', array('loggedUser' => $app['session']->get('loggedUser')));
        }

        return $app['twig']->render('index.twig', array('form' => $this->renderLoginForm($app)->createView()));
    }

    /**
     * @param Application $app
     * @return mixed
     */

    public function login_post(Application $app){

        $session = $app['session'];


        //var_dump($app['request']);
        $form = $this->renderLoginForm($app);
        $form->handleRequest($app['request']);

        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $postedData = $form->getData();

            //var_dump($postedData);
            // ... perform some action, such as saving the task to the database
            // for example, if Task is a Doctrine entity, save it!
            // $em = $this->getDoctrine()->getManager();
            // $em->persist($task);
            // $em->flush();
        }
        $loginResult = true;

        if($loginResult){
            $session->invalidate();
            $session->set('loggedUser', $postedData['email']);
            $session->save();
            return $app['twig']->render('welcome.twig', array('loggedUser' => $app['session']->get('loggedUser')));
        }
        return $app['twig']->render('index.twig', array('form' => $this->renderLoginForm($app)->createView()));
    }

    public function edit_post(Application $app){

    }

    public function edit_get(Application $app){
        $session = $app['session'];
        if($session->get('loggedUser')) {
            return $app['twig']->render('edit.twig', array(
                'loggedUser' => $session->get('loggedUser'),
                'form' => $this->renderEdit($app)->createView()
                ));
        }
        //$app['url_generator']->generate('my-route-name');
        $returnUrl = $app['url_generator']->generate('login_get');
        return $app->redirect($returnUrl);
        //return $app['twig']->render('index.twig', array('form' => $this->renderLoginForm($app)->createView()));
    }

    public function register_post(Application $app){
        $registerResult = true;

        return $app['twig']->render('register.twig', array(
            'registerResult' => $registerResult,
            'form' => $this->renderRegister($app)->createView()
        ));
    }

    public function register_get(Application $app){
        return $app['twig']->render('register.twig', array(
            'registerResult' => null,
            'form' => $this->renderRegister($app)->createView()
        ));
    }

    private function renderLoginForm(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('email', TextType::class, array(
                'constraints' => new Assert\Email(),
                'label' => 'Email Adress',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder'=> 'Email address',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('password', PasswordType::class, array(
                'constraints' => new Assert\NotBlank(),
                'label' => 'Password',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder'=> 'Password',
                    'required' => true,
                    'autofocus' => true,
                )
            ))

            ->add('remember-me', CheckboxType::class)
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

    private function renderRegister(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('email', TextType::class, array(
                'constraints' => new Assert\Email(),
                'label' => 'Email Adress',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control',
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
            ->getForm();
    }

    private function renderEdit(Application $app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('DisplayName', TextType::class, array(
                'constraints' => new Assert\Email(),
                'label' => 'Display Name',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder'=> 'Display Name',
                    'required' => true,
                    'autofocus' => true,
                )
            ))
            ->add('Language', TextType::class, array(
                'constraints' => new Assert\Email(),
                'label' => 'Prefered language',
                'label_attr' => array(
                    'class' => 'sr-only'
                ),
                'attr' => array(
                    'class' => 'form-control',
                    'placeholder'=> 'Prefered language',
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

    public function logout(Application $app){
        $app['session']->invalidate();
        return $app['twig']->render('logout.twig');
    }
}
