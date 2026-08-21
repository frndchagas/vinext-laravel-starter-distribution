<?php

namespace Tests\Feature\Tasks;

use App\Enums\TaskState;
use App\Jobs\ProcessTask;
use App\Models\Task;
use App\Services\DispatchTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ReconcileTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_redispatches_old_queued_tasks_and_stale_processing_claims(): void
    {
        Bus::fake([ProcessTask::class]);
        config([
            'tasks.reconcile_queued_after_seconds' => 60,
            'tasks.reconcile_processing_after_seconds' => 600,
        ]);

        $queued = Task::factory()->create(['created_at' => now()->subMinutes(2)]);
        $processing = Task::factory()->create([
            'state' => TaskState::Processing,
            'processing_token' => '00000000-0000-7000-8000-000000000001',
            'started_at' => now()->subMinutes(11),
        ]);
        Task::factory()->create(['created_at' => now()]);
        Task::factory()->completed()->create();

        $this->artisan('tasks:reconcile')
            ->expectsOutputToContain('Reconciled 2 Task delivery candidates.')
            ->assertSuccessful();

        Bus::assertDispatched(ProcessTask::class, fn (ProcessTask $job) => $job->taskId === $queued->id && $job->processingToken !== $processing->processing_token);
        Bus::assertDispatched(ProcessTask::class, fn (ProcessTask $job) => $job->taskId === $processing->id && $job->processingToken === $processing->processing_token);
        Bus::assertDispatchedTimes(ProcessTask::class, 2);
    }

    public function test_it_fails_when_redispatching_a_candidate_fails(): void
    {
        config(['tasks.reconcile_queued_after_seconds' => 0]);
        Task::factory()->create(['created_at' => now()->subSecond()]);

        $dispatcher = $this->mock(DispatchTask::class);
        $dispatcher->shouldReceive('__invoke')->once()->andReturnFalse();

        $this->artisan('tasks:reconcile')->assertFailed();
    }
}
