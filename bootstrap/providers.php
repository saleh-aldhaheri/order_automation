<?php

use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\HorizonServiceProvider;
use Bugsnag\BugsnagLaravel\BugsnagServiceProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    HorizonServiceProvider::class,
    BugsnagServiceProvider::class,
];
