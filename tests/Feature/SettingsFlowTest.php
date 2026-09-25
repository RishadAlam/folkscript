<?php

namespace Tests\Feature;

use App\Models\Post;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;

class SettingsFlowTest extends SecurityTestCase
{
    public function test_custom_profile_links_preserve_order_and_multiple_accounts_on_the_same_platform(): void
    {
        $user = $this->account(attributes: ['social_links' => ['website' => 'https://writer.example/']]);
        $links = [
            ['label' => 'my portfolio', 'url' => 'https://writer.example/work'],
            ['label' => 'Mastodon', 'url' => 'https://social.example/@personal'],
            ['label' => 'Mastodon', 'url' => 'https://social.example/@work'],
        ];
        $this->actingAs($user)->from('/settings?section=profile')->patch('/settings', [
            'name' => $user->name, 'username' => $user->username, 'links_present' => 1,
            'links' => [...$links, ['label' => '', 'url' => '']],
        ])->assertRedirect('/settings?section=profile')->assertSessionHasNoErrors();
        $this->assertSame($links, $user->fresh()->social_links);
        $this->get('/settings')->assertOk()->assertSee('my portfolio')->assertSee('https://social.example/@work');
    }

    public function test_profile_edits_preserve_omitted_links_and_explicitly_removing_all_links_clears_them(): void
    {
        $links = ['website' => 'https://writer.example/', 'instagram' => 'https://instagram.com/writer'];
        $user = $this->account(attributes: ['social_links' => $links]);
        $profile = ['name' => $user->name, 'username' => $user->username];
        $this->actingAs($user)->patch('/settings', $profile)->assertSessionHasNoErrors();
        $this->assertSame($links, $user->fresh()->social_links);
        $this->patch('/settings', $profile + ['links_present' => 1])->assertSessionHasNoErrors();
        $this->assertSame([], $user->fresh()->social_links);
    }

    public function test_invalid_custom_links_do_not_replace_existing_profile_data(): void
    {
        $saved = ['website' => 'https://writer.example/'];
        $user = $this->account(attributes: ['social_links' => $saved]);
        $this->actingAs($user)->from('/settings?section=profile')->patch('/settings', [
            'name' => 'Not saved', 'username' => $user->username, 'links_present' => 1,
            'links' => [
                ['label' => '', 'url' => 'https://writer.example/'],
                ['label' => 'Missing address', 'url' => ''],
                ['label' => 'Unsafe', 'url' => 'javascript:alert(1)'],
                ['label' => 'Private', 'url' => 'https://user:secret@writer.example/'],
            ],
        ])->assertSessionHasErrors(['links.0.label', 'links.1.url', 'links.2.url', 'links.3.url']);
        $this->assertSame($saved, $user->fresh()->social_links);
        $this->assertSame($user->name, $user->fresh()->name);
    }

    public function test_removing_all_links_stays_empty_when_another_profile_field_needs_correction(): void
    {
        $saved = ['website' => 'https://writer.example/'];
        $user = $this->account(attributes: ['social_links' => $saved]);
        $this->actingAs($user)->from('/settings?section=profile')->patch('/settings', [
            'name' => '', 'username' => $user->username, 'links_present' => 1,
        ])->assertRedirect('/settings?section=profile');
        $this->get('/settings?section=profile')->assertOk()->assertSee('The name field is required.')
            ->assertDontSee('https://writer.example/');
        $this->assertSame($saved, $user->fresh()->social_links);
    }

    public function test_profile_edits_do_not_reset_a_separately_saved_email_preference(): void
    {
        $user = $this->account(attributes: ['newsletter_enabled' => true]);

        $this->actingAs($user)->from('/settings?section=profile')->patch('/settings', [
            'name' => 'Updated Reader', 'username' => $user->username,
        ])->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->newsletter_enabled);
        $this->assertSame('Updated Reader', $user->fresh()->name);
    }

    public function test_email_preferences_are_saved_independently_and_only_for_the_signed_in_account(): void
    {
        $user = $this->account(attributes: ['newsletter_enabled' => false]);
        $other = $this->account(attributes: ['newsletter_enabled' => false]);

        $this->actingAs($user)->patch('/settings/preferences', [
            'newsletter_enabled' => '1', 'id' => $other->id,
            'name' => 'Injected name', 'email' => 'injected@example.test', 'role' => 'super-admin',
        ])->assertRedirect('/settings?section=preferences')->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->newsletter_enabled);
        $this->assertFalse($other->fresh()->newsletter_enabled);
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertTrue($user->fresh()->hasExactRoles(['reader']));
    }

    public function test_invalid_email_preferences_preserve_the_saved_choice_and_reopen_the_correct_section(): void
    {
        $user = $this->account(attributes: ['newsletter_enabled' => true]);
        $this->actingAs($user)->from('/settings')->patch('/settings/preferences', [
            'newsletter_enabled' => ['invalid'],
        ])->assertRedirect('/settings');

        // Follow the redirect without re-starting Laravel's JSON session through an intermediate session assertion.
        $this->get('/settings')->assertOk()->assertViewHas('section', 'preferences')->assertSee('newsletter enabled field must be true or false');
        $this->assertTrue($user->fresh()->newsletter_enabled);
    }

    #[DataProvider('settingsRoles')]
    public function test_settings_shortcuts_follow_actual_account_capabilities(string $role, string $label, bool $canFeature, bool $canModerate, bool $canManage): void
    {
        Permission::findOrCreate('settings.manage', 'web');
        $user = $this->account($role);
        $user->givePermissionTo('settings.manage');

        $this->actingAs($user)->get('/settings?section=publishing')->assertOk()
            ->assertViewHas('accountRoleLabel', $label)
            ->assertViewHas('canFeatureStory', $canFeature)
            ->assertViewHas('canModerate', $canModerate)
            ->assertViewHas('canManagePublication', $canManage)
            ->assertViewHas('section', $canFeature ? 'publishing' : 'profile');
    }

    public static function settingsRoles(): array
    {
        return [
            ['reader', 'Reader', false, false, false],
            ['author', 'Writer', true, false, false],
            ['editor', 'Editor', true, true, false],
            ['admin', 'Administrator', true, true, true],
            ['super-admin', 'Owner', true, true, true],
        ];
    }

    public function test_unverified_accounts_and_administrators_without_permission_get_no_misleading_management_links(): void
    {
        Permission::findOrCreate('settings.manage', 'web');
        $user = $this->account('admin');
        $this->actingAs($user)->get('/settings')->assertViewHas('canManagePublication', false);

        $user->givePermissionTo('settings.manage');
        $user->forceFill(['email_verified_at' => null])->save();
        $this->get('/settings?section=publishing')->assertOk()
            ->assertViewHas('canFeatureStory', false)
            ->assertViewHas('canModerate', false)
            ->assertViewHas('canManagePublication', false)
            ->assertViewHas('section', 'profile');
    }

    public function test_reader_with_an_existing_published_story_can_still_manage_its_featured_state(): void
    {
        $user = $this->account();
        Post::create(['author_id' => $user->id, 'title' => 'An existing story', 'slug' => 'existing-story', 'body_html' => '<p>Existing public work.</p>', 'status' => 'published', 'published_at' => now()->subDay()]);

        $this->actingAs($user)->get('/settings?section=publishing')->assertOk()
            ->assertViewHas('canFeatureStory', true)->assertViewHas('section', 'publishing');
        $this->get('/settings?section[]=security')->assertOk()->assertViewHas('section', 'profile');
    }

    public function test_security_and_token_errors_reopen_the_relevant_settings_section(): void
    {
        $this->actingAs($this->account())->from('/settings')->put('/settings/password', [
            'current_password' => 'wrong', 'password' => 'ReplacementPassword123', 'password_confirmation' => 'ReplacementPassword123',
        ])->assertRedirect('/settings');
        $this->get('/settings')->assertOk()->assertViewHas('section', 'security')->assertSee('The password is incorrect.');

        $this->confirmPassword()->from('/settings')->post('/settings/tokens', [])->assertRedirect('/settings');
        $this->get('/settings')->assertOk()->assertViewHas('section', 'developer');
        $this->post('/settings/tokens', ['token_name' => 'Reading app'])->assertRedirect('/settings');
        $this->get('/settings')->assertOk()->assertViewHas('section', 'developer');
    }

    public function test_password_confirmation_returns_to_the_requested_safe_settings_section(): void
    {
        $this->actingAs($this->account())->get('/settings/confirm-access?section=developer')
            ->assertRedirect('/confirm-password')->assertSessionHas('url.intended', url('/settings?section=developer'));
        $this->post('/confirm-password', ['password' => 'SecurePassword123'])->assertRedirect('/settings?section=developer');
        $this->get('/settings?section=developer')->assertViewHas('hasRecentPasswordConfirmation', true);

        $this->get('/settings/confirm-access?section=https://example.test')
            ->assertRedirect('/confirm-password')->assertSessionHas('url.intended', url('/settings?section=security'));
    }

    public function test_password_reset_handoff_signs_out_without_allowing_arbitrary_redirects(): void
    {
        $user = $this->account();
        $this->actingAs($user)->post('/logout', ['redirect_to' => 'password-reset'])->assertRedirect('/forgot-password');
        $this->assertGuest();
        $this->actingAs($user)->post('/logout', ['redirect_to' => 'https://example.test'])->assertRedirect('/');
        $this->assertGuest();
    }
}
