<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use League\Fractal\Manager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('League\Fractal\Manager', function($app) {
            $manager = new Manager;

            // Use the serializer of your choice.
            $manager->setSerializer(new \App\Http\Serializers\RootSerializer);

            return $manager;
        });
    }
}
