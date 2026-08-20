<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_users_tasks_with_a_cursor(): void
    {
        $user = User::factory()->create();
        Task::factory()->count(3)->for($user)->create();
        Task::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/tasks?per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $this->assertNotNull($response->json('meta.next_cursor'));

        $next = $this->actingAs($user)->getJson('/api/v1/tasks?per_page=2&cursor='.$response->json('meta.next_cursor'));
        $next->assertOk();
        $next->assertJsonCount(1, 'data');
        $this->assertNull($next->json('meta.next_cursor'));
    }

    public function test_users_cannot_read_someone_elses_task(): void
    {
        $user = User::factory()->create();
        $other = Task::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/tasks/'.$other->getKey());

        $response->assertForbidden();
    }

    public function test_the_owner_reads_the_task_with_iso_timestamps(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->completed()->for($user)->create();

        $response = $this->actingAs($user)->getJson('/api/v1/tasks/'.$task->getKey());

        $response->assertOk();
        $response->assertJson(['id' => (string) $task->getKey(), 'state' => 'completed']);
        $this->assertStringEndsWith('Z', $response->json('created_at'));
    }
}
