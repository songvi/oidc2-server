<?php
namespace Vuba\OIDC\Controllers\Account;
use Symfony\Component\Validator\Constraint;

class Constrain extends Constraint{
    public function validatedBy()
    {
        return ContainsEmaTel::class;
    }
}