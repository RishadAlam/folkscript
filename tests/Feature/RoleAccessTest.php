<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;

class RoleAccessTest extends SecurityTestCase
{
    #[DataProvider('accountRoles')]
    public function test_role_boundaries_for_account_and_administration_pages(string $role, int $adminStatus, int $peopleStatus): void
    {
        $this->actingAs($this->account($role))->get('/settings')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/admin')->assertStatus($adminStatus);
        $this->get('/admin?view=people')->assertStatus($peopleStatus);
        $this->get('/admin?view=activity')->assertStatus($peopleStatus);
    }

    public static function accountRoles(): array
    {
        return [
            'reader' => ['reader', 403, 403],
            'author' => ['author', 403, 403],
            'editor' => ['editor', 200, 403],
            'admin' => ['admin', 200, 200],
            'super admin' => ['super-admin', 200, 200],
        ];
    }

    #[DataProvider('protectedAccountActions')]
    public function test_guests_cannot_read_or_mutate_account_actions(string $method, string $path): void
    {
        $this->call($method, $path)->assertRedirect('/login');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public static function protectedAccountActions(): array
    {
        return [
            ['GET', '/settings'], ['PATCH', '/settings'], ['POST', '/settings/pinned-story'],
            ['PUT', '/settings/password'], ['DELETE', '/settings/account'],
            ['POST', '/settings/two-factor'], ['POST', '/settings/two-factor/confirm'],
            ['DELETE', '/settings/two-factor'], ['POST', '/settings/recovery-codes'],
            ['POST', '/settings/tokens'], ['DELETE', '/settings/tokens/1'],
            ['POST', '/email/verification-notification'], ['POST', '/confirm-password'],
            ['GET', '/admin'], ['POST', '/support/stop'],
        ];
    }

    #[DataProvider('accountRoles')]
    public function test_unverified_roles_cannot_open_admin_or_change_access(string $role, int $unusedAdmin, int $unusedPeople): void
    {
        $actor = $this->account($role, ['email_verified_at' => null]);
        $target = $this->account();
        $this->actingAs($actor)->get('/admin')->assertForbidden();
        $this->patch('/admin/users/'.$target->id, ['role' => 'editor', 'suspended' => true])->assertForbidden();
        $this->assertSame(['reader'], $target->fresh()->getRoleNames()->all());
        $this->assertNull($target->fresh()->suspended_at);
    }

    #[DataProvider('accountRoles')]
    public function test_only_administrators_can_change_lower_account_access(string $role, int $unusedAdmin, int $unusedPeople): void
    {
        $actor = $this->account($role);
        $target = $this->account();
        $token = $target->createToken('Existing client');
        $response = $this->actingAs($actor)->patch('/admin/users/'.$target->id, ['role' => 'editor', 'suspended' => true]);
        if (in_array($role, ['admin', 'super-admin'], true)) {
            $response->assertRedirect('/admin?view=people');
            $this->assertTrue($target->fresh()->hasRole('editor'));
            $this->assertNotNull($target->fresh()->suspended_at);
            $this->assertModelMissing($token->accessToken);
            $this->assertDatabaseHas('activity_log', ['description' => 'Updated account access', 'causer_id' => $actor->id, 'subject_id' => $target->id]);
            $this->patch('/admin/users/'.$target->id, ['role' => 'author', 'suspended' => false])->assertRedirect();
            $this->assertNull($target->fresh()->suspended_at);
            $this->assertTrue($target->fresh()->hasRole('author'));
        } else {
            $response->assertForbidden();
            $this->assertSame(['reader'], $target->fresh()->getRoleNames()->all());
            $this->assertNull($target->fresh()->suspended_at);
            $this->assertModelExists($token->accessToken);
        }
    }

    public function test_admin_cannot_grant_admin_or_modify_admin_and_owner_accounts(): void
    {
        $actor = $this->account('admin');
        $target = $this->account();
        $admin = $this->account('admin');
        $owner = $this->account('super-admin');
        $this->actingAs($actor)->patch('/admin/users/'.$target->id, ['role' => 'admin'])->assertSessionHasErrorsIn('access-'.$target->id, 'role');
        $this->patch('/admin/users/'.$admin->id, ['role' => 'reader', 'suspended' => true])->assertForbidden();
        $this->patch('/admin/users/'.$owner->id, ['role' => 'reader', 'suspended' => true])->assertForbidden();
        $this->patch('/admin/users/'.$actor->id, ['role' => 'reader'])->assertUnprocessable();
        $this->assertTrue($admin->fresh()->hasRole('admin'));
        $this->assertTrue($owner->fresh()->hasRole('super-admin'));
        $this->assertSame(['reader'], $target->fresh()->getRoleNames()->all());
    }

    public function test_owner_can_grant_admin_but_cannot_transfer_owner_role_through_user_action(): void
    {
        $actor = $this->account('super-admin');
        $target = $this->account();
        $owner = $this->account('super-admin');
        $this->actingAs($actor)->patch('/admin/users/'.$target->id, ['role' => 'admin'])->assertRedirect();
        $this->assertTrue($target->fresh()->hasRole('admin'));
        $this->patch('/admin/users/'.$target->id, ['role' => 'super-admin'])->assertSessionHasErrorsIn('access-'.$target->id, 'role');
        $this->patch('/admin/users/'.$owner->id, ['role' => 'reader'])->assertForbidden();
        $this->assertTrue($owner->fresh()->hasRole('super-admin'));
    }

    public function test_suspended_sessions_cannot_read_accounts_or_mutate_roles(): void
    {
        $actor = $this->account('super-admin', ['suspended_at' => now()]);
        $target = $this->account();
        $this->actingAs($actor)->get('/settings')->assertForbidden();
        $this->patch('/admin/users/'.$target->id, ['role' => 'admin'])->assertForbidden();
        $this->assertSame(['reader'], $target->fresh()->getRoleNames()->all());
    }

    public function test_suspended_sessions_can_still_sign_out(): void
    {
        $actor = $this->account(attributes: ['suspended_at' => now()]);
        $this->actingAs($actor)->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_admin_filters_reject_malformed_values_and_missing_account_is_not_found(): void
    {
        $this->actingAs($this->account('admin'));
        foreach (['view[]=people', 'view=unknown', 'view=people&users_page=-1', 'view=people&user_status=unknown', 'user_role=unknown', 'user_role[]=reader', 'q[]=invalid'] as $query) {
            $this->get('/admin?'.$query)->assertBadRequest();
        }
        $this->patch('/admin/users/999999', ['role' => 'reader'])->assertNotFound();
    }

    public function test_role_filters_match_the_highest_role_and_combine_with_account_status(): void
    {
        $actor = $this->account('admin');
        $reader = $this->account('reader');
        $writer = $this->account('author');
        $writer->assignRole('reader');
        $suspendedWriter = $this->account('author', ['suspended_at' => now()]);
        $suspendedWriter->assignRole('reader');
        $this->actingAs($actor)->get('/admin?view=people&user_role=reader')
            ->assertOk()->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$reader->id]);
        $this->get('/admin?user_role=author&user_status=active')
            ->assertOk()->assertViewHas('section', 'people')
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$writer->id]);
        $this->get('/admin?view=people&user_role=author&user_status=suspended')
            ->assertOk()->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$suspendedWriter->id]);
    }

    public function test_managed_reader_role_survives_email_verification_and_role_changes_are_audited(): void
    {
        $actor = $this->account('admin');
        $reader = $this->account('reader', ['email_verified_at' => null]);
        $this->actingAs($actor)->patch('/admin/users/'.$reader->id.'?user_role=reader&user_status=unverified', [
            'role' => 'reader', 'suspended' => '0',
        ])->assertRedirect('/admin?view=people&user_status=unverified&user_role=reader');
        $this->assertNotNull($reader->fresh()->access_role_assigned_at);
        $this->assertDatabaseHas('activity_log', ['description' => 'Updated account access', 'subject_id' => $reader->id]);
        $this->post('/logout')->assertRedirect('/');
        $verification = URL::temporarySignedRoute('verification.verify', now()->addMinutes(20), ['id' => $reader->id, 'hash' => sha1($reader->email)]);
        $this->actingAs($reader->fresh())->get($verification)
            ->assertRedirect('/bookmarks')->assertSessionHas('status', 'Email verified. Your reading list is ready.');
        $this->assertTrue($reader->fresh()->hasVerifiedEmail());
        $this->assertSame(['reader'], $reader->fresh()->getRoleNames()->all());
        $this->assertFalse($reader->fresh()->canWrite());
    }

    public function test_access_validation_returns_to_filters_and_explains_the_affected_account(): void
    {
        $actor = $this->account('admin');
        $target = $this->account('reader');
        $url = '/admin/users/'.$target->id.'?user_role=reader';
        $this->actingAs($actor)->patch($url, ['role' => ['admin'], 'suspended' => 'invalid', 'access_person' => $target->id])
            ->assertRedirect('/admin?view=people&user_role=reader');
        $this->get('/admin?view=people&user_role=reader')->assertOk()
            ->assertSee('Changes weren’t saved.')
            ->assertSee('role-error-'.$target->id, false)
            ->assertSee('access-error-'.$target->id, false)
            ->assertSee('Compare roles and permissions');
        $this->assertNull($target->fresh()->access_role_assigned_at);
        $this->assertSame(['reader'], $target->fresh()->getRoleNames()->all());
    }

    #[DataProvider('accountRoles')]
    public function test_support_sessions_are_only_available_to_owners(string $role, int $unusedAdmin, int $unusedPeople): void
    {
        $actor = $this->account($role);
        $target = $this->account();
        $response = $this->actingAs($actor)->confirmPassword()->post('/admin/users/'.$target->id.'/support');
        if ($role === 'super-admin') {
            $response->assertRedirect('/dashboard')->assertSessionHas('impersonator_id', $actor->id);
            $this->assertAuthenticatedAs($target);
            $this->assertDatabaseHas('activity_log', ['description' => 'Read-only support session started', 'causer_id' => $actor->id, 'subject_id' => $target->id]);
        } else {
            $response->assertForbidden()->assertSessionMissing('impersonator_id');
            $this->assertAuthenticatedAs($actor);
        }
    }

    public function test_support_start_requires_password_and_rejects_protected_targets(): void
    {
        $actor = $this->account('super-admin');
        $target = $this->account();
        $this->actingAs($actor)->post('/admin/users/'.$target->id.'/support')->assertRedirect('/confirm-password');
        $this->confirmPassword();
        foreach ([$actor, $this->account('admin'), $this->account('super-admin'), $this->account(attributes: ['suspended_at' => now()])] as $protected) {
            $this->post('/admin/users/'.$protected->id.'/support')->assertForbidden();
        }
        $this->assertAuthenticatedAs($actor);
    }

    public function test_support_session_blocks_private_pages_and_mutations_then_returns_to_owner(): void
    {
        $actor = $this->account('super-admin');
        $target = $this->account();
        $this->actingAs($actor)->confirmPassword()->post('/admin/users/'.$target->id.'/support')->assertRedirect();
        $this->get('/dashboard')->assertRedirect('/bookmarks');
        $this->get('/bookmarks')->assertOk();
        foreach (['/settings', '/admin', '/email/verify'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->patch('/settings', ['name' => 'Changed', 'username' => 'changed'])->assertForbidden();
        $this->post('/settings/tokens', ['token_name' => 'Forbidden'])->assertForbidden();
        $this->assertSame($target->name, $target->fresh()->name);
        $this->post('/support/stop')->assertRedirect('/admin')->assertSessionMissing('impersonator_id');
        $this->assertAuthenticatedAs($actor);
    }

    #[DataProvider('invalidSupportStates')]
    public function test_support_session_revalidates_original_operator(string $state): void
    {
        $actor = $this->account('super-admin');
        $target = $this->account();
        $this->actingAs($actor)->confirmPassword()->post('/admin/users/'.$target->id.'/support')->assertRedirect();
        match ($state) {
            'expired' => $this->withSession(['impersonation_expires_at' => now()->subMinute()->timestamp]),
            'suspended' => $actor->forceFill(['suspended_at' => now()])->save(),
            'unverified' => $actor->forceFill(['email_verified_at' => null])->save(),
            'demoted' => $actor->syncRoles('reader'),
            'password changed' => $actor->forceFill(['password' => Hash::make('ReplacementPassword123')])->save(),
            'deleted' => $actor->delete(),
        };
        $this->get('/dashboard')->assertForbidden()->assertSessionMissing('impersonator_id');
        $this->assertGuest();
    }

    public static function invalidSupportStates(): array
    {
        return [['expired'], ['suspended'], ['unverified'], ['demoted'], ['password changed'], ['deleted']];
    }

    public function test_owner_can_exit_when_impersonated_account_becomes_suspended(): void
    {
        $actor = $this->account('super-admin');
        $target = $this->account();
        $this->actingAs($actor)->confirmPassword()->post('/admin/users/'.$target->id.'/support')->assertRedirect();
        $target->forceFill(['suspended_at' => now()])->save();
        $this->actingAs($target->fresh())->post('/support/stop')->assertRedirect('/admin');
        $this->assertAuthenticatedAs($actor);
    }
}
