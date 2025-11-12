<?php

namespace NumaxLab\Lunar\Redsys;

use Illuminate\Support\ServiceProvider;
use Lunar\Facades\Payments;

class LunarRedsysServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Payments::extend(RedsysPayment::DRIVER_NAME, function ($app) {
            return $app->make(RedsysPayment::class);
        });

        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');

        $this->publishes([
            __DIR__.'/../config/redsys.php' => config_path('lunar/redsys.php'),
        ], ['lunar']);
    }
}
