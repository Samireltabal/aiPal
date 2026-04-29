<?php

use App\Modules\Chat\ChatServiceProvider;
use App\Modules\Core\CoreServiceProvider;
use App\Modules\Extension\ExtensionServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    ChatServiceProvider::class,
    CoreServiceProvider::class,
    ExtensionServiceProvider::class,
    AppServiceProvider::class,
    HorizonServiceProvider::class,
];
