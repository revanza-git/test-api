<?php

namespace App\Providers;

use App\Events\UserCreated;
use App\Listeners\SendUserCreatedEmails;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        UserCreated::class => [
            SendUserCreatedEmails::class,
        ],
    ];
}

