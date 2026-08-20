<?php

namespace App\Jobs;

use App\Enums\TaskState;
use App\Events\TaskStatusChanged;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProcessTask implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public string $processingToken;

    public function __construct(public string $taskId, ?string $processingToken = null)
    {
        $this->processingToken = $processingToken ?? (string) Str::uuid7();
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60];
    }

    public function uniqueId(): string
    {
        return $this->taskId;
    }

    public function handle(): void
    {
        $task = Task::find($this->taskId);

        if ($task === null || $task->state->isFinal()) {
            return;
        }

        $claimed = Task::query()
            ->whereKey($this->taskId)
            ->where('state', TaskState::Queued)
            ->update([
                'state' => TaskState::Processing,
                'processing_token' => $this->processingToken,
                'version' => DB::raw('version + 1'),
                'started_at' => now(),
            ]);

        $task->refresh();

        if ($claimed === 0 && ($task->state !== TaskState::Processing || $task->processing_token !== $this->processingToken)) {
            return;
        }

        if ($claimed === 1) {
            TaskStatusChanged::dispatch(...self::payload($task));
        }

        $delayMs = (int) config('tasks.simulated_delay_ms');

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        $output = [
            'word_count' => str_word_count($task->input),
            'reversed' => Str::reverse($task->input),
        ];

        DB::transaction(function () use ($output): void {
            $ownedTask = Task::query()
                ->whereKey($this->taskId)
                ->where('state', TaskState::Processing)
                ->where('processing_token', $this->processingToken)
                ->lockForUpdate()
                ->first();

            if ($ownedTask === null) {
                return;
            }

            $ownedTask->forceFill([
                'state' => TaskState::Completed,
                'processing_token' => null,
                'version' => $ownedTask->version + 1,
                'output' => $output,
                'finished_at' => now(),
            ])->save();

            TaskStatusChanged::dispatch(...self::payload($ownedTask));
        });
    }

    public function failed(?Throwable $exception): void
    {
        DB::transaction(function (): void {
            $task = Task::query()->whereKey($this->taskId)->lockForUpdate()->first();

            if ($task === null || $task->state->isFinal()) {
                return;
            }

            if ($task->state === TaskState::Processing && $task->processing_token !== $this->processingToken) {
                return;
            }

            $task->forceFill([
                'state' => TaskState::Failed,
                'processing_token' => null,
                'version' => $task->version + 1,
                'error_code' => 'task_failed',
                'finished_at' => now(),
            ])->save();

            TaskStatusChanged::dispatch(...self::payload($task));
        });
    }

    /**
     * @return array<int, mixed>
     */
    private static function payload(Task $task): array
    {
        $event = TaskStatusChanged::fromTask($task);

        return [
            $event->taskId,
            $event->state,
            $event->version,
            $event->occurredAt,
            $event->correlationId,
        ];
    }
}
