<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_promotes_an_existing_user_to_admin(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('app:grant-admin', ['email' => 'owner@example.com'])
            ->expectsOutputToContain('owner@example.com')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_it_is_idempotent(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com']);

        $this->artisan('app:grant-admin', ['email' => 'owner@example.com'])->assertSuccessful();
        $this->artisan('app:grant-admin', ['email' => 'owner@example.com'])->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function test_it_fails_for_an_unknown_email(): void
    {
        $this->artisan('app:grant-admin', ['email' => 'missing@example.com'])
            ->assertFailed();
    }
}
