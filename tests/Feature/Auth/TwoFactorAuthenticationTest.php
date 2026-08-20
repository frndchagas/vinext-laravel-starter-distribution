<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_enable_confirm_use_and_disable_two_factor_authentication(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/auth/user/two-factor-authentication')
            ->assertStatus(423)
            ->assertJson(['message' => 'Password confirmation required.']);

        $this->postJson('/api/v1/auth/user/confirm-password', [
            'password' => 'current-password',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/user/two-factor-authentication')->assertOk();

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);

        $this->getJson('/api/v1/auth/user/two-factor-qr-code')
            ->assertOk()
            ->assertJsonStructure(['svg', 'url']);

        $secret = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
        $code = app(Google2FA::class)->getCurrentOtp($secret);

        $this->postJson('/api/v1/auth/user/confirmed-two-factor-authentication', [
            'code' => $code,
        ])->assertOk();

        $user->refresh();
        $this->assertNotNull($user->two_factor_confirmed_at);

        $recoveryResponse = $this->getJson('/api/v1/auth/user/two-factor-recovery-codes')
            ->assertOk()
            ->assertJsonCount(8);
        $recoveryCode = $recoveryResponse->json('0');

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJson([
                'two_factor_enabled' => true,
                'two_factor_confirmed' => true,
            ]);

        $this->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'current-password',
        ])->assertOk()->assertJson(['two_factor' => true]);

        $this->assertGuest();

        $this->postJson('/api/v1/auth/two-factor-challenge', [
            'recovery_code' => $recoveryCode,
        ])->assertNoContent();

        $this->getJson('/api/v1/me')->assertOk();

        $this->postJson('/api/v1/auth/user/confirm-password', [
            'password' => 'current-password',
        ])->assertCreated();

        $this->deleteJson('/api/v1/auth/user/two-factor-authentication')->assertOk();

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_recovery_codes);
        $this->assertNull($user->two_factor_confirmed_at);
    }

    public function test_an_invalid_two_factor_challenge_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);

        $this->actingAs($user);
        $this->postJson('/api/v1/auth/user/confirm-password', [
            'password' => 'current-password',
        ])->assertCreated();
        $this->postJson('/api/v1/auth/user/two-factor-authentication')->assertOk();

        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $this->postJson('/api/v1/auth/logout')->assertNoContent();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'current-password',
        ])->assertOk()->assertJson(['two_factor' => true]);

        $this->postJson('/api/v1/auth/two-factor-challenge', [
            'code' => '000000',
        ])->assertUnprocessable();

        $this->assertGuest();
    }
}
