<?php

namespace App\Exceptions;

class CommonError extends BaseError
{
    public function __construct($custom, $errorCode = 500)
    {
        $message = $custom;
        $code = $errorCode;
        parent::__construct($message, $code);
    }
}
