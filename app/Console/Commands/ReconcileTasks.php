<?php

namespace App\Console\Commands;

use App\Enums\TaskState;
use App\Models\Task;
use App\Services\DispatchTask;
use Illuminate\Console\Command;

class ReconcileTasks extends Command
{
    protected $signature = 'tasks:reconcile';

    protected $description = 'Redispatch non-final Tasks whose delivery may have been lost';

    public function handle(DispatchTask $dispatchTask): int
    {
        $queuedBefore = now()->subSeconds(max(0, (int) config('tasks.reconcile_queued_after_seconds')));
        $processingBefore = now()->subSeconds(max(0, (int) config('tasks.reconcile_processing_after_seconds')));
        $batchSize = max(1, (int) config('tasks.reconcile_batch_size'));

        $tasks = Task::query()
            ->where(function ($query) use ($processingBefore, $queuedBefore): void {
                $query
                    ->where(function ($query) use ($queuedBefore): void {
                        $query
                            ->where('state', TaskState::Queued->value)
                            ->where('created_at', '<=', $queuedBefore);
                    })
                    ->orWhere(function ($query) use ($processingBefore): void {
                        $query
                            ->where('state', TaskState::Processing->value)
                            ->whereNotNull('processing_token')
                            ->where('started_at', '<=', $processingBefore);
                    });
            })
            ->orderBy('created_at')
            ->limit($batchSize)
            ->get();

        $failures = 0;

        foreach ($tasks as $task) {
            $processingToken = $task->state === TaskState::Processing
                ? $task->processing_token
                : null;

            if (! $dispatchTask((string) $task->getKey(), $processingToken)) {
                $failures++;
            }
        }

        $this->components->info("Reconciled {$tasks->count()} Task delivery candidates.");

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
