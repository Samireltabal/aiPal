<?php

use App\Modules\Chat\ChatServiceProvider;
use App\Modules\Core\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    ChatServiceProvider::class,
    CoreServiceProvider::class,
    AppServiceProvider::class,
    HorizonServiceProvider::class,
];
