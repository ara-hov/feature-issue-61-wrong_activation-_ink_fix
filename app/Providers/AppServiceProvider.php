<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // binds interface to repository
        foreach ([
                     "User" => ["User"],
                     "UserRoles" => ["UserRoles"],
                     "Comms" => ["Comms"],
                     "Events" => ["Events"],
                     "Entities" => ["Entities"],
                     "Aggregate" => ["Aggregate"],
                     "BidNegotiations" => ["BidNegotiations"],
                 ] as $_dir => $_names) {
            foreach ($_names as $_eachName) {
                $this->app->bind(
                    "App\Repositories\\" . $_dir . "\\" . $_eachName . "Interface",
                    "App\Repositories\\" . $_dir . "\\" . $_eachName . "Repository"
                );
                $this->app->alias("App\Repositories\\" . $_dir . "\\" . $_eachName . "Interface", $_eachName);
            }

        }


        // Without Interface
        $array = [
            'DoAuth' => ['DoAuth',]
        ];
        foreach ($array as $_dir => $_names) {
            foreach ($_names as $_eachName) {
                $this->app->alias('App\Repositories\\' . $_dir . '\\' . $_eachName . 'Repository', $_eachName);
            }
        }

        $this->app->singleton('Illuminate\Contracts\Routing\ResponseFactory', function ($app) {
            return new \Illuminate\Routing\ResponseFactory(
                $app['Illuminate\Contracts\View\Factory'],
                $app['Illuminate\Routing\Redirector']
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
