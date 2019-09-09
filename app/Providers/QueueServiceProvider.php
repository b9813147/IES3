<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Extensions\Queue\Connectors\ResqueConnector;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerResqueConnector();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Register the Resque connector.
     *
     * @return void
     */
    protected function registerResqueConnector()
    {
        $manager = $this->app['queue'];
        $manager->addConnector('resque', function () {
            return new ResqueConnector();
        });
    }
}
