<?php

declare(strict_types=1);

namespace App\Modules\Core;

use Illuminate\Support\ServiceProvider;

final class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeModuleConfigs();
    }

    public function boot(): void {}

    private function mergeModuleConfigs(): void
    {
        $configPath = __DIR__.'/Config';

        if (! is_dir($configPath)) {
            return;
        }

        foreach (glob("{$configPath}/*.php") as $file) {
            $key = basename($file, '.php');
            $this->mergeConfigFrom($file, $key);
        }
    }
}
