<?php

namespace Vuba\OIDC\Controllers\Account;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class RenderService{

    public static function render($app, $formName, $formDisplayName, $message, $twigFileName){
        switch (strtolower($formName)) {
            case 'login':
                $form = self::loginForm($app);
                break;
            case 'edit':
                $form = self::editForm($app);
                break;
            case 'register':
                $form = self::register($app);
                break;
            case 'active':
                $form = self::activate($app);
                break;
            case 'activepw':
                $form = self::activatePassword($app);
                break;
            case 'resetpw':
                $form = self::resetPW($app);
                break;
            case 'forgotpw':
                $form = self::forgotPW($app);
                break;
            case 'login':
                break;
            default:

                break;
        }

        return $app['twig']->render($twigFileName, array(
            'form_name' => $formDisplayName,
            'action_message' => $message,
            'form' => $form->createView(),
        ));
    }

    public static function renderForm($app, $form, $formDisplayName, $message, $twigFileName){
        return $app['twig']->render($twigFileName, array(
            'form_name' => $formDisplayName,
            'action_message' => $message,
            'form' => $form->createView(),
        ));
    }
    public static function loginForm($app){
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

    public static function editForm($app){
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

    public static function register($app){
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

    public static function activate($app){
        return $app['form.factory']->createBuilder(FormType::class)
            ->add('activation_code', TextType::class, array(
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

    public static function activatePassword($app){
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

    public static function resetPW($app){
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

    public static function forgotPW($app){
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
}