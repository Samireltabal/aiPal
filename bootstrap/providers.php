<?php

use App\Modules\Chat\ChatServiceProvider;
use App\Modules\Core\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    CoreServiceProvider::class,
    ChatServiceProvider::class,
];
