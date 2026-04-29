<?php

declare(strict_types=1);

namespace App\Modules\Extension;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class ExtensionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        RateLimiter::for('extension', function (Request $request) {
            $key = optional($request->user())->id ?: $request->ip();

            return [Limit::perMinute(60)->by("extension:{$key}")];
        });
    }
}
