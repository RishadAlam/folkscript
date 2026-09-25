<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use PHPUnit\Framework\Attributes\DataProvider;

class TwoFactorSecurityTest extends SecurityTestCase
{
    #[DataProvider('oauthAuthenticationModes')]
    public function test_social_sign_in_does_not_confirm_a_password_that_was_never_entered(bool $withTwoFactor): void
    {
        $user = $this->account(attributes: ['oauth_provider' => 'google', 'oauth_id' => 'existing-provider-id']);
        if ($withTwoFactor) {
            $this->enableTwoFactor($user);
        }
        $identity = (new SocialUser)->setRaw(['email_verified' => true])->map([
            'id' => 'existing-provider-id', 'name' => $user->name, 'email' => $user->email,
        ]);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($identity);

        // A stale confirmation must not become confirmation for this OAuth login.
        $response = $this->withSession(['auth.password_confirmed_at' => time()])->get('/auth/google/callback');
        if ($withTwoFactor) {
            $response->assertRedirect('/two-factor-challenge');
            $this->assertGuest();
            $this->post('/two-factor-challenge', ['recovery_code' => $user->recoveryCodes()[0]])->assertRedirect('/dashboard');
        } else {
            $response->assertRedirect('/dashboard');
        }

        $this->assertAuthenticatedAs($user);
        $this->post('/settings/two-factor')->assertRedirect('/confirm-password');
        $this->post('/settings/tokens', ['token_name' => 'Automation'])->assertRedirect('/confirm-password');
        $this->post('/confirm-password', ['password' => 'SecurePassword123'])->assertRedirect();
        $this->get('/settings?section=security')->assertOk()->assertViewHas('hasRecentPasswordConfirmation', true);
    }

    public static function oauthAuthenticationModes(): array
    {
        return ['provider only' => [false], 'provider and two-factor' => [true]];
    }

    public function test_password_and_two_factor_sign_in_preserve_recent_password_confirmation(): void
    {
        $user = $this->account();
        $this->enableTwoFactor($user);
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        $this->post('/two-factor-challenge', ['recovery_code' => $user->recoveryCodes()[0]])->assertRedirect('/dashboard');

        $this->get('/settings?section=security')->assertOk()->assertViewHas('hasRecentPasswordConfirmation', true);
        $this->assertAuthenticatedAs($user);
    }

    public function test_two_factor_challenge_is_rate_limited_without_consuming_recovery_codes(): void
    {
        $user = $this->account();
        $this->enableTwoFactor($user);
        $recoveryCodes = $user->recoveryCodes();
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/two-factor-challenge', ['recovery_code' => 'invalid'])->assertUnprocessable();
        }
        $this->postJson('/two-factor-challenge', ['recovery_code' => $recoveryCodes[0]])->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertSame($recoveryCodes, $user->fresh()->recoveryCodes());
        $this->assertGuest();
    }

    public function test_suspension_during_a_challenge_prevents_authentication(): void
    {
        $user = $this->account();
        $this->enableTwoFactor($user);
        $recovery = $user->recoveryCodes()[0];
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        $user->forceFill(['suspended_at' => now()])->save();

        $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertRedirect('/login')->assertSessionMissing('login.id');
        $this->assertContains($recovery, $user->fresh()->recoveryCodes());
        $this->assertGuest();
    }

    public function test_incomplete_setup_does_not_lock_a_user_out_of_their_account(): void
    {
        $user = $this->account();
        app(EnableTwoFactorAuthentication::class)($user);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);

        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/settings/recovery-codes')->assertForbidden();
    }

    public function test_challenge_page_requires_a_live_sign_in_session(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect('/login');

        $user = $this->account();
        $this->enableTwoFactor($user);
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        $this->get('/two-factor-challenge')->assertOk()->assertSee('Verify your sign-in');
        $this->withSession(['login.expires' => now()->subSecond()->timestamp])->get('/two-factor-challenge')
            ->assertRedirect('/login')->assertSessionMissing('login.id');
        $this->assertGuest();
    }

    public function test_manual_setup_key_is_shown_only_during_password_confirmed_enrollment(): void
    {
        $user = $this->account();
        app(EnableTwoFactorAuthentication::class)($user);
        $secret = decrypt($user->two_factor_secret);

        $this->actingAs($user)->get('/settings?section=security')->assertOk()->assertDontSee($secret);
        $this->confirmPassword()->get('/settings?section=security')->assertOk()->assertSee('Setup key')->assertSee($secret);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $this->get('/settings?section=security')->assertOk()->assertDontSee('Setup key')->assertDontSee($secret);
    }

    #[DataProvider('protectedTwoFactorActions')]
    public function test_all_two_factor_management_actions_require_recent_password_confirmation(string $method, string $path): void
    {
        $user = $this->account();
        $this->enableTwoFactor($user);
        $secret = $user->two_factor_secret;
        $recoveryCodes = $user->recoveryCodes();

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time() - config('auth.password_timeout', 10800) - 1]);
        $this->call($method, $path, ['code' => '123456'])->assertRedirect('/confirm-password');
        $this->assertSame($secret, $user->fresh()->two_factor_secret);
        $this->assertSame($recoveryCodes, $user->fresh()->recoveryCodes());
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
    }

    public static function protectedTwoFactorActions(): array
    {
        return [
            'begin setup' => ['POST', '/settings/two-factor'],
            'confirm setup' => ['POST', '/settings/two-factor/confirm'],
            'disable' => ['DELETE', '/settings/two-factor'],
            'replace recovery codes' => ['POST', '/settings/recovery-codes'],
        ];
    }

    private function enableTwoFactor(User $user): void
    {
        app(EnableTwoFactorAuthentication::class)($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        $user->refresh();
    }
}
