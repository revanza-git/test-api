<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * API-only application: do not redirect to a login route.
     */
    protected function redirectTo($request): ?string
    {
        return null;
    }
}

