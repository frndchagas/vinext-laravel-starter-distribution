<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders API errors as RFC 9457 Problem Details.
 */
final class ProblemDetails
{
    public static function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*') && ! $request->expectsJson()) {
            return null;
        }

        [$status, $detail, $extensions, $headers] = match (true) {
            $exception instanceof ValidationException => [
                $exception->status,
                'The request contains invalid fields.',
                ['errors' => $exception->errors()],
                [],
            ],
            $exception instanceof AuthenticationException => [401, 'Authentication is required.', [], []],
            $exception instanceof AuthorizationException => [403, $exception->getMessage(), [], []],
            $exception instanceof RecordsNotFoundException => [404, null, [], []],
            $exception instanceof HttpExceptionInterface => [
                $exception->getStatusCode(),
                $exception->getMessage() === '' ? null : $exception->getMessage(),
                [],
                $exception->getHeaders(),
            ],
            default => [500, config('app.debug') === true ? $exception->getMessage() : null, [], []],
        };

        $problem = [
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
        ];

        if ($exception instanceof ProvidesProblemCode) {
            $problem['code'] = $exception->problemCode();
        }

        if ($detail !== null) {
            $problem['detail'] = $detail;
        }

        return new JsonResponse(
            [...$problem, ...$extensions],
            $status,
            [...$headers, 'Content-Type' => 'application/problem+json'],
        );
    }
}
