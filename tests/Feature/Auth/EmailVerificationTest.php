<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_can_be_verified_through_the_signed_link(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => sha1((string) $user->email)],
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/dashboard?verified=1');
    }

    public function test_verification_notification_can_be_resent(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/auth/email/verification-notification');

        $response->assertStatus(202);
    }

    public function test_unverified_users_cannot_access_verified_only_routes(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'verified'])->get('/api/v1/verified-only', fn () => ['ok' => true]);

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/verified-only');

        $response->assertForbidden();
        $response->assertHeader('Content-Type', 'application/problem+json');
    }

    public function test_verified_users_can_access_verified_only_routes(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'verified'])->get('/api/v1/verified-only', fn () => ['ok' => true]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/verified-only');

        $response->assertOk();
    }
}
