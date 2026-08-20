<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TasksChannelTest extends TestCase
{
    use RefreshDatabase;

    private function authorizeChannel(User $user, string $channel): TestResponse
    {
        return $this->actingAs($user)->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => $channel,
        ]);
    }

    public function test_the_owner_with_verified_email_can_join_the_task_channel(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->authorizeChannel($user, 'private-tasks.'.$task->getKey())->assertOk();
    }

    public function test_other_users_cannot_join_the_task_channel(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $this->authorizeChannel($user, 'private-tasks.'.$task->getKey())->assertForbidden();
    }

    public function test_unverified_owners_cannot_join_the_task_channel(): void
    {
        $user = User::factory()->unverified()->create();
        $task = Task::factory()->for($user)->create();

        $this->authorizeChannel($user, 'private-tasks.'.$task->getKey())->assertForbidden();
    }
}
