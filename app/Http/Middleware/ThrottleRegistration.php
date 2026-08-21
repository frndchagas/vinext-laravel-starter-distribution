<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

final class ThrottleRegistration
{
    public function __construct(private readonly ThrottleRequests $throttleRequests) {}

    /**
     * Apply the registration limiter without changing the other Fortify routes.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('register.store')) {
            return $next($request);
        }

        return $this->throttleRequests->handle($request, $next, 'registration');
    }
}
