<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_receive_problem_details_unauthorized(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized();
        $response->assertHeader('Content-Type', 'application/problem+json');
        $response->assertJsonStructure(['type', 'title', 'status']);
    }

    public function test_authenticated_users_receive_identity_roles_and_permissions(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->getJson('/api/v1/me');

        $response->assertOk();
        $response->assertJson([
            'id' => (string) $user->getKey(),
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => true,
        ]);
        $response->assertJsonFragment(['roles' => ['admin']]);

        $permissions = $response->json('permissions');
        $this->assertContains('users.view', $permissions);
        $this->assertContains('users.manage', $permissions);
        $this->assertContains('settings.manage', $permissions);
    }

    public function test_unverified_users_can_still_query_their_session(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/me');

        $response->assertOk();
        $response->assertJson(['email_verified' => false]);
    }
}
