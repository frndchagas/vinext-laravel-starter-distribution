<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class TaskStatusChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $taskId,
        public string $state,
        public int $version,
        public string $occurredAt,
        public string $correlationId,
    ) {}

    public static function fromTask(Task $task): self
    {
        return new self(
            taskId: (string) $task->getKey(),
            state: $task->state->value,
            version: $task->version,
            occurredAt: now()->toIso8601ZuluString(),
            correlationId: $task->correlation_id,
        );
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tasks.'.$this->taskId);
    }

    public function broadcastAs(): string
    {
        return 'TaskStatusChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->taskId,
            'state' => $this->state,
            'version' => $this->version,
            'occurred_at' => $this->occurredAt,
            'correlation_id' => $this->correlationId,
        ];
    }
}
