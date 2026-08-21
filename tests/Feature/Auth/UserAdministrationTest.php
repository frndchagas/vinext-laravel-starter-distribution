<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_member_cannot_list_or_manage_users(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');
        $target = User::factory()->create();

        $this->actingAs($member)
            ->getJson('/api/v1/admin/users')
            ->assertForbidden();

        $this->actingAs($member)
            ->patchJson("/api/v1/admin/users/{$target->id}/role", ['role' => 'admin'])
            ->assertForbidden();
    }

    public function test_an_admin_can_search_and_page_through_safe_user_metadata(): void
    {
        $admin = User::factory()->create(['email' => 'owner@example.com']);
        $admin->assignRole('admin');
        $target = User::factory()->unverified()->create([
            'name' => 'Search Target',
            'email' => 'target@example.com',
        ]);
        $target->assignRole('member');
        User::factory()->create(['email' => 'unrelated@example.com']);

        $response = $this->actingAs($admin)
            ->getJson('/api/v1/admin/users?search=TARGET&per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $target->id)
            ->assertJsonPath('data.0.name', 'Search Target')
            ->assertJsonPath('data.0.email', 'target@example.com')
            ->assertJsonPath('data.0.email_verified', false)
            ->assertJsonPath('data.0.roles', ['member'])
            ->assertJsonCount(1, 'data');

        $this->assertArrayHasKey('created_at', $response->json('data.0'));
        $this->assertArrayNotHasKey('two_factor_enabled', $response->json('data.0'));
        $this->assertArrayNotHasKey('permissions', $response->json('data.0'));
    }

    public function test_an_admin_can_promote_and_demote_another_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create();
        $target->assignRole('member');

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/users/{$target->id}/role", ['role' => 'admin'])
            ->assertOk()
            ->assertJsonPath('roles', ['admin']);

        $this->assertTrue($target->fresh()->hasExactRoles(['admin']));

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/users/{$target->id}/role", ['role' => 'member'])
            ->assertOk()
            ->assertJsonPath('roles', ['member']);

        $this->assertTrue($target->fresh()->hasExactRoles(['member']));
    }

    public function test_an_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/users/{$admin->id}/role", ['role' => 'member'])
            ->assertConflict()
            ->assertJsonPath('code', 'cannot_change_own_role');

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_the_last_admin_cannot_be_demoted(): void
    {
        $operator = User::factory()->create();
        $operator->givePermissionTo('users.manage');
        $lastAdmin = User::factory()->create();
        $lastAdmin->assignRole('admin');

        $this->actingAs($operator)
            ->patchJson("/api/v1/admin/users/{$lastAdmin->id}/role", ['role' => 'member'])
            ->assertConflict()
            ->assertJsonPath('code', 'last_admin');

        $this->assertTrue($lastAdmin->fresh()->hasRole('admin'));
    }

    public function test_role_input_is_restricted_to_the_canonical_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->patchJson("/api/v1/admin/users/{$target->id}/role", ['role' => 'owner'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }
}
