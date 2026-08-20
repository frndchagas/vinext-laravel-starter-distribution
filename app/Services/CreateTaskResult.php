<?php

namespace App\Services;

final readonly class CreateTaskResult
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public string $taskId,
        public int $status,
        public array $body,
        public bool $replayed,
    ) {}
}
