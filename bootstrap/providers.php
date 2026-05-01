<?php

use App\Modules\Chat\ChatServiceProvider;
use App\Modules\Core\CoreServiceProvider;
use App\Modules\Extension\ExtensionServiceProvider;
use App\Modules\People\PeopleServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    ChatServiceProvider::class,
    CoreServiceProvider::class,
    ExtensionServiceProvider::class,
    PeopleServiceProvider::class,
    AppServiceProvider::class,
    HorizonServiceProvider::class,
];
