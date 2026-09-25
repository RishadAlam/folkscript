<?php

namespace Tests\Feature;

use App\Models\Series;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PragmaRX\Google2FA\Google2FA;

class AccountSecurityTest extends SecurityTestCase
{
    public function test_registration_normalizes_email_and_never_accepts_privileged_fields(): void
    {
        $this->post('/register', [
            'name' => 'New Reader', 'username' => 'new_reader', 'email' => 'NEW@EXAMPLE.TEST',
            'password' => 'SecurePassword123', 'password_confirmation' => 'SecurePassword123',
            'role' => 'super-admin', 'email_verified_at' => now()->toDateTimeString(), 'suspended_at' => null,
        ])->assertRedirect('/email/verify');
        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(['reader'], $user->getRoleNames()->all());
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_malformed_registration_email_returns_validation_error(): void
    {
        $this->postJson('/register', ['email' => ['bad@example.test']])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_reserved_names_weak_passwords_and_honeypot(): void
    {
        $this->postJson('/register', [
            'name' => 'Invalid Account', 'username' => 'admin', 'email' => 'new@example.test',
            'password' => 'weak', 'password_confirmation' => 'weak', 'website' => 'https://spam.example',
        ])->assertUnprocessable()->assertJsonValidationErrors(['username', 'password', 'website']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_login_accepts_case_insensitive_email_and_local_return_destination(): void
    {
        $user = $this->account(attributes: ['email' => 'reader@example.test']);
        $this->get('/login?return_to='.urlencode('/bookmarks#saved'))->assertOk();
        $this->post('/login', ['email' => 'READER@EXAMPLE.TEST', 'password' => 'SecurePassword123'])->assertRedirect('/bookmarks#saved');
        $this->assertAuthenticatedAs($user);
    }

    #[DataProvider('unsafeDestinations')]
    public function test_login_rejects_unsafe_return_destinations(string $destination): void
    {
        $this->get('/login?return_to='.urlencode($destination))->assertOk()->assertSessionMissing('url.intended');
    }

    public static function unsafeDestinations(): array
    {
        return [['https://example.test'], ['//example.test'], ['/\\example.test'], ["/\nexample.test"], ['javascript:alert(1)']];
    }

    public function test_login_errors_do_not_distinguish_missing_wrong_password_and_suspended_accounts(): void
    {
        $active = $this->account();
        $suspended = $this->account(attributes: ['suspended_at' => now()]);
        $errors = [];
        foreach ([['missing@example.test', 'SecurePassword123'], [$active->email, 'incorrect'], [$suspended->email, 'SecurePassword123']] as [$email, $password]) {
            $errors[] = $this->postJson('/login', compact('email', 'password'))->assertUnprocessable()->json('errors.email');
            $this->assertGuest();
        }
        $this->assertSame($errors[0], $errors[1]);
        $this->assertSame($errors[1], $errors[2]);
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/login', ['email' => 'missing@example.test', 'password' => 'incorrect'])->assertUnprocessable();
        }
        $this->postJson('/login', ['email' => 'missing@example.test', 'password' => 'incorrect'])->assertTooManyRequests();
    }

    public function test_signed_verification_cannot_verify_another_account_and_grants_writing_to_the_owner(): void
    {
        $user = $this->account(attributes: ['email_verified_at' => null]);
        $other = $this->account(attributes: ['email_verified_at' => null]);
        $foreignUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(5), ['id' => $other->id, 'hash' => sha1($other->email)]);
        $this->actingAs($user)->get($foreignUrl)->assertForbidden();
        $this->assertNull($other->fresh()->email_verified_at);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(5), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect('/dashboard');
        $this->assertTrue($user->fresh()->canWrite());
        $this->assertTrue($user->fresh()->hasRole('reader'));
    }

    public function test_unsigned_expired_and_wrong_email_verification_links_are_denied(): void
    {
        $user = $this->account(attributes: ['email_verified_at' => null]);
        $params = ['id' => $user->id, 'hash' => sha1($user->email)];
        $this->actingAs($user)->get(route('verification.verify', $params))->assertForbidden();
        $this->get(URL::temporarySignedRoute('verification.verify', now()->subMinute(), $params))->assertForbidden();
        $this->get(URL::temporarySignedRoute('verification.verify', now()->addMinute(), [...$params, 'hash' => sha1('wrong@example.test')]))->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_password_reset_requests_do_not_disclose_account_existence(): void
    {
        config(['mail.default' => 'smtp']);
        $user = $this->account();
        $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])->assertRedirect('/forgot-password');
        $message = session('status');
        Notification::assertSentTo($user, ResetPassword::class);
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'missing@example.test'])->assertRedirect('/forgot-password')->assertSessionHas('status', $message);
    }

    public function test_password_reset_revokes_tokens_and_rejects_reuse(): void
    {
        $user = $this->account();
        $user->createToken('Existing client', ['profile:read']);
        $token = Password::createToken($user);
        $data = ['email' => $user->email, 'token' => $token, 'password' => 'ReplacementPassword123', 'password_confirmation' => 'ReplacementPassword123'];
        $this->post('/reset-password', $data)->assertRedirect('/login');
        $this->assertTrue(Hash::check('ReplacementPassword123', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->post('/reset-password', $data)->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_malformed_reset_token_returns_validation_error(): void
    {
        $user = $this->account();
        Password::createToken($user);
        $this->postJson('/reset-password', ['email' => $user->email, 'token' => ['invalid'], 'password' => 'ReplacementPassword123', 'password_confirmation' => 'ReplacementPassword123'])
            ->assertUnprocessable()->assertJsonValidationErrors('token');
        $this->assertTrue(Hash::check('SecurePassword123', $user->fresh()->password));
    }

    public function test_profile_updates_cannot_change_security_fields_or_another_account(): void
    {
        $user = $this->account();
        $other = $this->account();
        $this->actingAs($user)->from('/settings')->patch('/settings', [
            'name' => 'Updated Reader', 'username' => 'updated_reader', 'id' => $other->id,
            'email' => 'attacker@example.test', 'role' => 'super-admin', 'password' => 'InjectedPassword123', 'newsletter_enabled' => false,
        ])->assertRedirect('/settings');
        $this->assertSame('Updated Reader', $user->fresh()->name);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertTrue(Hash::check('SecurePassword123', $user->fresh()->password));
        $this->assertSame(['reader'], $user->fresh()->getRoleNames()->all());
        $this->assertSame($other->name, $other->fresh()->name);
    }

    public function test_profile_rename_preserves_story_and_collection_redirects(): void
    {
        $user = $this->account('author', ['username' => 'original_writer']);
        $user->posts()->create(['title' => 'Story', 'slug' => 'story', 'body_html' => '<p>Story</p>']);
        Series::create(['author_id' => $user->id, 'title' => 'Collection', 'slug' => 'collection']);
        $this->actingAs($user)->patch('/settings', ['name' => $user->name, 'username' => 'renamed_writer'])->assertSessionHasNoErrors();
        foreach (['' => '', '/story' => '/story', '/series/collection' => '/series/collection'] as $suffix => $newSuffix) {
            $this->get('/@original_writer'.$suffix)->assertRedirect('/@renamed_writer'.$newSuffix)->assertStatus(301);
        }
    }

    public function test_profile_validation_preserves_data_and_rejects_unsafe_links(): void
    {
        $user = $this->account();
        $other = $this->account();
        $this->actingAs($user)->from('/settings')->patch('/settings', ['name' => 'Edited Name', 'username' => $other->username, 'website' => 'javascript:alert(1)'])
            ->assertRedirect('/settings')->assertSessionHasErrors(['username', 'website'])->assertSessionHasInput('name', 'Edited Name');
        $this->assertSame($user->username, $user->fresh()->username);
    }

    public function test_only_own_published_stories_can_be_pinned_and_the_pin_can_be_cleared(): void
    {
        $user = $this->account('author');
        $other = $this->account('author');
        $own = $user->posts()->create(['title' => 'Own', 'slug' => 'own', 'status' => 'published', 'published_at' => now()->subDay()]);
        $foreign = $other->posts()->create(['title' => 'Foreign', 'slug' => 'foreign', 'status' => 'published', 'published_at' => now()->subDay()]);
        $draft = $user->posts()->create(['title' => 'Draft', 'slug' => 'draft', 'status' => 'draft']);
        $this->actingAs($user);
        foreach ([$foreign->id, $draft->id, 999999] as $id) {
            $this->postJson('/settings/pinned-story', ['post_id' => $id])->assertUnprocessable()->assertJsonValidationErrors('post_id');
        }
        $this->post('/settings/pinned-story', ['post_id' => $own->id])->assertSessionHasNoErrors();
        $this->assertSame($own->id, $user->fresh()->pinned_post_id);
        $this->post('/settings/pinned-story', ['post_id' => null])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->pinned_post_id);
    }

    public function test_password_changes_require_current_password_and_revoke_existing_tokens(): void
    {
        $user = $this->account();
        $user->createToken('Existing client');
        $data = ['current_password' => 'wrong', 'password' => 'ReplacementPassword123', 'password_confirmation' => 'ReplacementPassword123'];
        $this->actingAs($user)->from('/settings')->put('/settings/password', $data)->assertSessionHasErrorsIn('passwordChange', 'current_password');
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->put('/settings/password', [...$data, 'current_password' => 'SecurePassword123'])->assertRedirect('/settings');
        $this->assertTrue(Hash::check('ReplacementPassword123', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[DataProvider('malformedPasswordActions')]
    public function test_malformed_password_confirmation_is_a_validation_error(string $method, string $path, array $data, string $field): void
    {
        $user = $this->account();
        $this->actingAs($user)->json($method, $path, $data)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertModelExists($user);
        $this->assertTrue(Hash::check('SecurePassword123', $user->fresh()->password));
    }

    public static function malformedPasswordActions(): array
    {
        return [
            ['POST', '/confirm-password', ['password' => ['invalid']], 'password'],
            ['PUT', '/settings/password', ['current_password' => ['invalid'], 'password' => 'ReplacementPassword123', 'password_confirmation' => 'ReplacementPassword123'], 'current_password'],
            ['DELETE', '/settings/account', ['password' => ['invalid'], 'confirmation' => 'DELETE'], 'password'],
        ];
    }

    public function test_tokens_require_recent_password_and_verification_and_cannot_be_revoked_across_accounts(): void
    {
        $user = $this->account();
        $other = $this->account();
        $foreign = $other->createToken('Foreign');
        $this->actingAs($user)->post('/settings/tokens', ['token_name' => 'Client'])->assertRedirect('/confirm-password');
        $this->confirmPassword()->post('/settings/tokens', ['token_name' => 'Client'])->assertSessionHas('token');
        $token = $user->tokens()->firstOrFail();
        $this->assertSame(['profile:read', 'posts:read'], $token->abilities);
        $this->assertTrue($token->expires_at->isFuture());
        $this->delete('/settings/tokens/'.$foreign->accessToken->id)->assertNotFound();
        $this->assertModelExists($foreign->accessToken);
        $this->delete('/settings/tokens/'.$token->id)->assertRedirect();
        $this->assertModelMissing($token);
        $user->forceFill(['email_verified_at' => null])->save();
        $this->post('/settings/tokens', ['token_name' => 'Unverified'])->assertRedirect('/email/verify');
    }

    public function test_token_limit_rejects_an_eleventh_token_without_deleting_existing_tokens(): void
    {
        $user = $this->account();
        for ($i = 0; $i < 10; $i++) {
            $user->createToken('Client '.$i);
        }
        $this->actingAs($user)->confirmPassword()->post('/settings/tokens', ['token_name' => 'One too many'])->assertSessionHasErrors('token_name');
        $this->assertSame(10, $user->tokens()->count());
    }

    public function test_two_factor_setup_requires_recent_password_and_a_valid_authenticator_code(): void
    {
        $user = $this->account();
        $this->actingAs($user)->post('/settings/two-factor')->assertRedirect('/confirm-password');
        $this->confirmPassword()->post('/settings/two-factor')->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_confirmed_at);
        $this->post('/settings/two-factor/confirm', ['code' => 'invalid'])->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', 'code');
        $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));
        $this->post('/settings/two-factor/confirm', compact('code'))->assertSessionHas('status');
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $original = $user->fresh()->recoveryCodes();
        $this->post('/settings/recovery-codes')->assertRedirect();
        $this->assertNotSame($original, $user->fresh()->recoveryCodes());
        $this->delete('/settings/two-factor')->assertRedirect();
        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_recovery_codes);
    }

    public function test_recovery_codes_are_hidden_after_password_confirmation_expires(): void
    {
        $user = $this->twoFactorAccount();
        $code = $user->recoveryCodes()[0];
        $this->actingAs($user)->get('/settings')->assertOk()->assertDontSee($code);
        $this->confirmPassword()->get('/settings')->assertOk()->assertSee($code);
    }

    public function test_two_factor_login_requires_a_valid_code_and_consumes_recovery_codes_once(): void
    {
        $user = $this->twoFactorAccount();
        $recovery = $user->recoveryCodes()[0];
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->postJson('/two-factor-challenge', ['recovery_code' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('recovery_code');
        $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertRedirect('/dashboard')->assertSessionMissing('login.id');
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains($recovery, $user->fresh()->recoveryCodes());
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        $this->postJson('/two-factor-challenge', ['recovery_code' => $recovery])->assertUnprocessable()->assertJsonValidationErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_two_factor_login_accepts_the_authenticator_and_rejects_replayed_codes(): void
    {
        $user = $this->twoFactorAccount();
        $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        $this->post('/two-factor-challenge', compact('code'))->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        $this->postJson('/two-factor-challenge', compact('code'))->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertGuest();
    }

    public function test_expired_two_factor_challenge_is_cleared_without_consuming_recovery_code(): void
    {
        $user = $this->twoFactorAccount();
        $recovery = $user->recoveryCodes()[0];
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        $this->withSession(['login.expires' => now()->subMinute()->timestamp])->post('/two-factor-challenge', ['recovery_code' => $recovery])
            ->assertRedirect('/login')->assertSessionMissing('login.id');
        $this->assertContains($recovery, $user->fresh()->recoveryCodes());
        $this->assertGuest();
    }

    public function test_password_reset_invalidates_an_in_progress_two_factor_challenge(): void
    {
        $user = $this->twoFactorAccount();
        $recovery = $user->recoveryCodes()[0];
        $this->post('/login', ['email' => $user->email, 'password' => 'SecurePassword123'])->assertRedirect('/two-factor-challenge');
        $this->post('/reset-password', ['email' => $user->email, 'token' => Password::createToken($user), 'password' => 'ReplacementPassword123', 'password_confirmation' => 'ReplacementPassword123'])
            ->assertRedirect('/login');
        $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertContains($recovery, $user->fresh()->recoveryCodes());
    }

    public function test_account_deletion_requires_password_and_explicit_confirmation(): void
    {
        $user = $this->account();
        $this->actingAs($user)->delete('/settings/account', ['password' => 'wrong', 'confirmation' => 'yes'])
            ->assertSessionHasErrorsIn('accountDeletion', ['password', 'confirmation']);
        $this->assertModelExists($user);
        $token = $user->createToken('Existing client');
        $this->delete('/settings/account', ['password' => 'SecurePassword123', 'confirmation' => 'DELETE'])->assertRedirect('/');
        $this->assertModelMissing($user);
        $this->assertModelMissing($token->accessToken);
        $this->assertGuest();
    }

    public function test_super_admin_cannot_delete_platform_ownership(): void
    {
        $user = $this->account('super-admin');
        $this->actingAs($user)->delete('/settings/account', ['password' => 'SecurePassword123', 'confirmation' => 'DELETE'])
            ->assertSessionHasErrorsIn('accountDeletion', 'confirmation');
        $this->assertModelExists($user);
    }

    public function test_unconfigured_and_unknown_oauth_providers_have_safe_errors(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);
        $this->get('/auth/google/redirect')->assertRedirect('/login')->assertSessionHas('status');
        $this->get('/auth/unknown/redirect')->assertNotFound();
    }

    #[DataProvider('undeliveredMailers')]
    public function test_verification_resend_and_password_reset_explain_unavailable_mail(string $mailer): void
    {
        config(['mail.default' => $mailer]);
        $user = $this->account(attributes: ['email_verified_at' => null]);
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status', fn ($status) => str_contains($status, 'unavailable'));
        Notification::assertNothingSent();
        $this->actingAs($user)->post('/email/verification-notification')->assertSessionHas('status', fn ($status) => str_contains($status, 'unavailable'));
        Notification::assertNothingSent();
    }

    #[DataProvider('undeliveredMailers')]
    public function test_registration_does_not_claim_delivery_when_mail_is_unavailable(string $mailer): void
    {
        config(['mail.default' => $mailer]);
        $this->post('/register', ['name' => 'New Reader', 'username' => 'new_reader', 'email' => 'new@example.test', 'password' => 'SecurePassword123', 'password_confirmation' => 'SecurePassword123'])
            ->assertRedirect('/email/verify')->assertSessionHas('status', fn ($status) => str_contains($status, 'unavailable'));
        $this->assertAuthenticated();
    }

    public static function undeliveredMailers(): array
    {
        return [['log'], ['array']];
    }

    public function test_oauth_does_not_link_an_existing_account_by_email(): void
    {
        $user = $this->account();
        $identity = (new SocialUser)->setRaw(['email_verified' => true])->map(['id' => 'provider-id', 'name' => 'Social Reader', 'email' => $user->email]);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($identity);
        $this->get('/auth/google/callback')->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $this->assertNull($user->fresh()->oauth_provider);
    }

    public function test_verified_new_oauth_identity_receives_only_reader_and_author_roles(): void
    {
        $identity = (new SocialUser)->setRaw(['email_verified' => true])->map(['id' => 'new-provider-id', 'name' => 'Social Reader', 'email' => 'social@example.test']);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($identity);
        $this->get('/auth/google/callback')->assertRedirect('/dashboard');
        $user = User::where('email', 'social@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertEqualsCanonicalizing(['reader', 'author'], $user->getRoleNames()->all());
    }

    public function test_existing_oauth_identity_still_requires_enabled_two_factor(): void
    {
        $user = $this->twoFactorAccount();
        $user->forceFill(['oauth_provider' => 'google', 'oauth_id' => 'existing-provider-id'])->save();
        $identity = (new SocialUser)->setRaw(['email_verified' => true])->map(['id' => 'existing-provider-id', 'name' => $user->name, 'email' => $user->email]);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($identity);
        $this->get('/auth/google/callback')->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->post('/two-factor-challenge', ['recovery_code' => $user->recoveryCodes()[0]])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    private function twoFactorAccount(): User
    {
        $user = $this->account();
        app(EnableTwoFactorAuthentication::class)($user);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        return $user->refresh();
    }
}
