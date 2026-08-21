<?php

namespace App\Services;

use App\Enums\TaskState;
use App\Exceptions\IdempotencyKeyReused;
use App\Http\Resources\TaskResource;
use App\Models\IdempotencyKey;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class CreateTask
{
    private const string OPERATION = 'tasks.create';

    public function __construct(private DispatchTask $dispatchTask) {}

    public function __invoke(User $user, string $input, string $idempotencyKey, string $correlationId): CreateTaskResult
    {
        $payloadHash = hash('sha256', $input);

        try {
            return DB::transaction(function () use ($user, $input, $idempotencyKey, $correlationId, $payloadHash): CreateTaskResult {
                $task = new Task;
                $task->fill([
                    'input' => $input,
                    'state' => TaskState::Queued,
                    'version' => 1,
                    'correlation_id' => $correlationId,
                ]);
                $task->user()->associate($user);
                $task->save();

                $body = TaskResource::make($task)->resolve();

                IdempotencyKey::create([
                    'user_id' => $user->getKey(),
                    'operation' => self::OPERATION,
                    'key' => $idempotencyKey,
                    'payload_hash' => $payloadHash,
                    'resource_id' => $task->getKey(),
                    'response_status' => 202,
                    'response_body' => $body,
                ]);

                $this->dispatchTask->afterCommit((string) $task->getKey());

                return new CreateTaskResult(taskId: (string) $task->getKey(), status: 202, body: $body, replayed: false);
            });
        } catch (UniqueConstraintViolationException $exception) {
            $existing = IdempotencyKey::query()
                ->where('user_id', $user->getKey())
                ->where('operation', self::OPERATION)
                ->where('key', $idempotencyKey)
                ->first();

            if ($existing === null) {
                throw $exception;
            }

            if ($existing->payload_hash !== $payloadHash) {
                throw new IdempotencyKeyReused;
            }

            $task = Task::query()->find($existing->resource_id);

            if ($task?->state === TaskState::Queued) {
                $this->dispatchTask->afterCommit((string) $task->getKey());
            }

            return new CreateTaskResult(
                taskId: $existing->resource_id,
                status: $existing->response_status,
                body: $existing->response_body,
                replayed: true,
            );
        }
    }
}
