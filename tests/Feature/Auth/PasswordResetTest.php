<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_link_points_to_the_spa_with_the_token_and_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);

        $response->assertOk();
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $url = $notification->toMail($user)->actionUrl;

            $this->assertIsString($url);
            $this->assertStringStartsWith(config('app.url').'/reset-password?', $url);

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            $this->assertSame($notification->token, $query['token'] ?? null);
            $this->assertSame($user->email, $query['email'] ?? null);

            return true;
        });
    }

    public function test_password_can_be_reset_with_a_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $response = $this->postJson('/api/v1/auth/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-secret-password',
                'password_confirmation' => 'new-secret-password',
            ]);

            $response->assertOk();

            $this->assertTrue(Hash::check('new-secret-password', $user->fresh()->password));

            return true;
        });
    }
}
