<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;

class BaseError implements ErrorFactory
{
    private $message;
    private $code;

    public function __construct($message, $code)
    {
        $this->message = $message;
        $this->code = $code;
    }

    public function response()
    {
        Log::alert('Exception: ' . $this->message);

        return $this->message;
    }

    public function error()
    {
        return $this->code;
    }
}