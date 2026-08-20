<?php

namespace App\Exceptions;

use App\Support\ProvidesProblemCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IdempotencyKeyReused extends HttpException implements ProvidesProblemCode
{
    public function __construct()
    {
        parent::__construct(409, 'This idempotency key was already used with a different payload.');
    }

    public function problemCode(): string
    {
        return 'idempotency_key_reused';
    }
}
