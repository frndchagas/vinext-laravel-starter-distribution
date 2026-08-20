<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-Id');

        if (! is_string($correlationId) || ! Str::isUuid($correlationId)) {
            $correlationId = (string) Str::uuid7();
        }

        Context::add('correlation_id', $correlationId);
        $request->attributes->set('correlation_id', $correlationId);

        $response = $next($request);

        $response->headers->set('X-Correlation-Id', $correlationId);

        return $response;
    }
}
