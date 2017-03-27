<?php

namespace Toplan\PhpSms;

use Illuminate\Support\ServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class PhpSmsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        if (function_exists('config_path')) {
            $publishPath = config_path('phpsms.php');
        } else {
            $publishPath = base_path('config/phpsms.php');
        }
        $this->publishes([
            __DIR__ . '/../config/phpsms.php' => $publishPath,
        ], 'config');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        if ($this->app instanceof LumenApplication) {
            $this->app->configure('phpsms');
        }
        $this->mergeConfigFrom(__DIR__ . '/../config/phpsms.php', 'phpsms');

        $this->app->singleton('Toplan\\PhpSms\\Sms', function () {
            Sms::scheme(config('phpsms.scheme', []));
            Sms::config(config('phpsms.agents', []));

            return new Sms(false);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Toplan\\PhpSms\\Sms'];
    }
}
