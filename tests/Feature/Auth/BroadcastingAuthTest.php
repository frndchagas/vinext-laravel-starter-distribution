<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class BroadcastingAuthTest extends TestCase
{
    use RefreshDatabase;

    private function authorizeChannel(User $user, string $channel): TestResponse
    {
        return $this->actingAs($user)->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => $channel,
        ]);
    }

    public function test_verified_users_can_join_their_private_channel(): void
    {
        $user = User::factory()->create();

        $response = $this->authorizeChannel($user, 'private-users.'.$user->getKey());

        $response->assertOk();
    }

    public function test_unverified_users_cannot_join_their_private_channel(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->authorizeChannel($user, 'private-users.'.$user->getKey());

        $response->assertForbidden();
    }

    public function test_users_cannot_join_another_users_channel(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->authorizeChannel($user, 'private-users.'.$other->getKey());

        $response->assertForbidden();
    }

    public function test_guests_cannot_authorize_channels(): void
    {
        $response = $this->postJson('/api/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => 'private-users.some-id',
        ]);

        $response->assertUnauthorized();
    }
}
