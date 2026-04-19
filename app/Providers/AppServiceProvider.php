<?php

namespace App\Providers;

use App\Console\Commands\AiTestCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                AiTestCommand::class,
            ]);
        }

    }
}
