<?php
namespace Vuba\OIDC\Controllers\Account;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class ContainsEmaTel extends Constraint
{
    public $message = 'The format of "{{ string }}" is incorrect: it can be an email address or telephone number';
}
