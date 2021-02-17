<?php

namespace App\Exceptions;

class UnauthorisedRequest extends BaseError
{
    public function __construct($custom = '')
    {
        $message = 'Unauthorised request made by ' . $custom;
        $code = 401;
        parent::__construct($message, $code);
    }
}