<?php

namespace Vuba\OIDC\Controllers\Account;

class FormMessages
{

    const LOGIN_FORM_NAME = "Login to my site";
    const LOGIN_MSG_FAILED = "Login failed, please try again";
    const LOGIN_MSG_BLOCK = "Login failed, please try again later";

    const IP_LOCKED = "Your can't go further, please try again later";

    const REGISTER_FORM_NAME    = "Sign up for free";
    const REGISTER_MSG_ERROR    = "There is an error in registration proccess, please try again later";
    const REGISTER_MSG_OK       = "You are successfully registered";
    const REGISTER_MSG          = "Enter email or telephone number to get activation code";

    const FORGOTPW_FORM_NAME = "You do not remember your password?";
    const FORGOTPW_MSG = "";

    const FORGOTPW_ACTIVATION_FORM_NAME = "Re activation your account";
    const FORGOTPW_ACTIVATION_MSG_NAME = "Active your account by entering code";
    const FORGOTPW_ACTIVATION_NEWPW_MSG = "Create new password";


}
