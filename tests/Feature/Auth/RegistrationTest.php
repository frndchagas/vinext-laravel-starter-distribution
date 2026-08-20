<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register_and_receive_the_member_role(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated();

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue(Str::isUuid($user->getKey()));
        $this->assertTrue($user->hasRole('member'));
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_registration_is_rejected_when_the_feature_is_disabled(): void
    {
        config(['features.registration' => false]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertForbidden();
        $response->assertHeader('Content-Type', 'application/problem+json');

        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_registration_validates_input_as_problem_details(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'other',
        ]);

        $response->assertUnprocessable();
        $response->assertHeader('Content-Type', 'application/problem+json');
        $response->assertJsonStructure(['type', 'title', 'status', 'errors' => ['name', 'email', 'password']]);
    }
}
