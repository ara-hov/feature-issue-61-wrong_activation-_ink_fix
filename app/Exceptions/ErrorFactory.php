<?php

namespace App\Exceptions;

interface ErrorFactory
{
    public function response();

    public function error();
}