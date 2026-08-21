<?php

namespace App\Services;

use App\Jobs\ProcessTask;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\DB;
use Throwable;

class DispatchTask
{
    public function afterCommit(string $taskId, ?string $processingToken = null): void
    {
        DB::afterCommit(fn () => $this($taskId, $processingToken));
    }

    public function __invoke(string $taskId, ?string $processingToken = null): bool
    {
        $job = new ProcessTask($taskId, $processingToken);

        try {
            $pendingDispatch = new PendingDispatch($job);
            unset($pendingDispatch);

            return true;
        } catch (Throwable $exception) {
            $this->releaseUniqueLock($job);
            report($exception);

            return false;
        }
    }

    private function releaseUniqueLock(ProcessTask $job): void
    {
        try {
            (new UniqueLock(app(Repository::class)))->release($job);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
