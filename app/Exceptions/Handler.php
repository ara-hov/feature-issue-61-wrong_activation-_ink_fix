<?php

namespace App\Exceptions;

use Laravel\Passport\Exceptions\OAuthServerException as PassportExceptionHandler;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Throwable $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $message = [
                'status' => false,
                'error_code' => 404,
                'errors' => ["We don't have that kind of route"]
            ];
            return response($message, 404);
        }

        if ($exception instanceof AuthenticationException) {
            $message = [
                'message' => $exception->getMessage()
            ];
            return response($message, 401);
        }

        if ($exception instanceof \Exception) {
            $message = [
                'status' => false,
                'message' => $exception->getMessage()
            ];
            return response($message, 500);
        }


        return parent::render($request, $exception);
    }
}
