<?php

namespace Toplan\PhpSms;

use Illuminate\Support\ServiceProvider;

class PhpSmsServiceProvider extends ServiceProvider
{
    /**
     * bootstrap
     */
    public function boot()
    {
        //publish config files
        $this->publishes([
            __DIR__ . '/../config/phpsms.php' => config_path('phpsms.php'),
        ], 'config');
    }

    /**
     * register service provider
     */
    public function register()
    {
        //merge configs
        $this->mergeConfigFrom(
            __DIR__ . '/../config/phpsms.php', 'phpsms'
        );

        Sms::scheme(config('phpsms.scheme', []));
        Sms::config(config('phpsms.agents', []));

        $this->app->singleton('PhpSms', function () {
            return new Sms(false);
        });
    }
}
