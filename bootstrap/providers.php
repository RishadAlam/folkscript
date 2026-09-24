<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\DomainObserverServiceProvider::class,
    App\Providers\PlatformSettingsServiceProvider::class,
];
