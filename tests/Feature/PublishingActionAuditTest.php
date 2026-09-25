<?php

namespace Tests\Feature;

use App\Livewire\PostEditor;
use App\Models\Category;
use App\Models\Post;
use App\Models\Report;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\CommunityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PublishingActionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
            throw new \LogicException('Publishing tests require an isolated in-memory SQLite database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['scout.driver' => 'null', 'folkscript.mail_notifications' => false, 'seo.render_og' => false, 'seo.indexnow_key' => null]);
        Queue::fake();
        Http::preventStrayRequests();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['posts.publish', 'posts.edit-any', 'comments.moderate', 'settings.manage'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        foreach (['reader', 'author', 'editor', 'admin', 'super-admin'] as $role) {
            Role::findOrCreate($role, 'web')->syncPermissions(match ($role) {
                'author' => ['posts.publish'],
                'editor' => ['posts.publish', 'posts.edit-any', 'comments.moderate'],
                'admin', 'super-admin' => $permissions,
                default => [],
            });
        }
    }

    private function user(string $role = 'author', array $attributes = []): User
    {
        $user = User::factory()->create(['username' => 'audit-'.Str::lower(Str::random(12)), ...$attributes]);
        $user->assignRole($role);
        return $user;
    }

    private function story(?User $author = null, array $attributes = []): Post
    {
        return Post::create(['author_id' => ($author ?? $this->user())->id, 'title' => 'A story worth reading', 'slug' => 'story-'.Str::lower(Str::random(12)), 'excerpt' => 'A public introduction.', 'body_html' => '<p>The private interior of this story contains more than forty characters.</p>', 'status' => 'published', 'published_at' => now()->subMinute(), 'reading_time' => 1, ...$attributes]);
    }

    private function draft(array $attributes = []): array
    {
        return ['title' => 'A new story', 'body_html' => '<p>A careful account of the ordinary things that make a day worth remembering.</p>', 'status' => 'draft', ...$attributes];
    }

    public function test_story_creation_enforces_roles_verification_and_ownership(): void
    {
        $this->postJson('/posts', $this->draft())->assertUnauthorized();
        $this->actingAs($this->user('reader'))->postJson('/posts', $this->draft())->assertForbidden();
        $this->actingAs($this->user('author', ['email_verified_at' => null]))->postJson('/posts', $this->draft())->assertForbidden();
        $author = $this->user();
        $foreign = $this->story();
        $this->actingAs($author)->putJson('/posts/'.$foreign->id, $this->draft())->assertForbidden();
        $this->deleteJson('/posts/'.$foreign->id)->assertForbidden();
        $this->get('/write/'.$foreign->id)->assertForbidden();
        $response = $this->postJson('/posts', $this->draft(['author_id' => $foreign->author_id, 'views' => 999]));
        $response->assertOk()->assertJsonPath('status', 'draft');
        $this->assertDatabaseHas('posts', ['id' => $response->json('id'), 'author_id' => $author->id, 'views' => 0]);
        $this->actingAs($this->user('editor'))->putJson('/posts/'.$foreign->id, $this->draft(['title' => 'Editorial correction']))->assertOk();
        $this->assertDatabaseHas('posts', ['id' => $foreign->id, 'author_id' => $foreign->author_id, 'title' => 'Editorial correction']);
    }

    public function test_guests_and_suspended_accounts_cannot_invoke_non_account_mutations(): void
    {
        $author = $this->user();
        $post = $this->story($author);
        $comment = $post->comments()->create(['user_id' => $author->id, 'body' => 'A response', 'status' => 'visible']);
        $tag = Tag::create(['name' => 'Tag', 'slug' => 'tag']);
        $category = Category::create(['name' => 'Topic', 'slug' => 'topic']);
        $series = Series::create(['author_id' => $author->id, 'title' => 'Collection', 'slug' => 'collection']);
        $report = Report::create(['user_id' => $author->id, 'reportable_type' => Post::class, 'reportable_id' => $post->id, 'status' => 'open', 'reason' => 'Needs review']);
        $token = $author->createToken('gate-test', ['tokens:manage'])->accessToken;
        $actions = [
            ['POST', '/posts'], ['PUT', '/posts/'.$post->id], ['DELETE', '/posts/'.$post->id], ['POST', '/media'],
            ['POST', '/notifications/read'], ['POST', '/posts/'.$post->id.'/bookmark'], ['POST', '/posts/'.$post->id.'/react'],
            ['POST', '/posts/'.$post->id.'/comments'], ['POST', '/posts/'.$post->id.'/report'], ['DELETE', '/comments/'.$comment->id],
            ['POST', '/comments/'.$comment->id.'/report'], ['POST', '/authors/'.$author->id.'/follow'], ['POST', '/tags/'.$tag->id.'/follow'], ['POST', '/categories/'.$category->id.'/follow'],
            ['POST', '/series'], ['PATCH', '/series/'.$series->id], ['DELETE', '/series/'.$series->id],
            ['POST', '/series/'.$series->id.'/posts'], ['DELETE', '/series/'.$series->id.'/posts/'.$post->id], ['PATCH', '/series/'.$series->id.'/order'],
            ['PATCH', '/admin/reports/'.$report->id], ['PATCH', '/admin/comments/'.$comment->id], ['PATCH', '/admin/posts/'.$post->id],
            ['POST', '/admin/taxonomy/tags'], ['PATCH', '/admin/settings'], ['DELETE', '/api/v1/tokens/'.$token->id],
        ];
        foreach ($actions as [$method, $path]) {
            $this->json($method, $path)->assertUnauthorized();
        }
        $suspended = $this->user('admin', ['suspended_at' => now()]);
        $this->actingAs($suspended, 'web');
        foreach ($actions as [$method, $path]) {
            $this->json($method, $path)->assertForbidden();
        }
        $this->actingAs($suspended, 'web')->postJson('/posts/'.$post->id.'/quote-card', ['quote' => 'The private interior of this story'])->assertForbidden();
        $this->assertSame('published', $post->fresh()->status);
        $this->assertSame('visible', $comment->fresh()->status);
        $this->assertSame('open', $report->fresh()->status);
    }

    public function test_scheduler_only_publishes_due_stories_by_currently_authorized_writers(): void
    {
        $due = $this->story(null, ['status' => 'scheduled']);
        $future = $this->story(null, ['status' => 'scheduled', 'published_at' => now()->addDay()]);
        $suspended = $this->story($this->user('author', ['suspended_at' => now()]), ['status' => 'scheduled']);
        $unverified = $this->story($this->user('author', ['email_verified_at' => null]), ['status' => 'scheduled']);
        $demoted = $this->story($this->user('reader'), ['status' => 'scheduled']);
        $this->artisan('posts:publish-scheduled')->expectsOutput('Published 1 scheduled stories.')->assertSuccessful();
        $this->assertSame('published', $due->fresh()->status);
        foreach ([$future, $suspended, $unverified, $demoted] as $post) {
            $this->assertSame('scheduled', $post->fresh()->status);
        }
    }

    public function test_publication_validates_content_and_schedules_without_exposing_drafts(): void
    {
        $author = $this->user();
        $this->actingAs($author)->postJson('/posts', $this->draft(['status' => 'published', 'body_html' => '<script>Hidden code</script><p>Short</p>']))->assertUnprocessable()->assertJsonValidationErrors('body_html');
        $this->postJson('/posts', $this->draft(['status' => 'scheduled', 'published_at' => now()->subDay()->toIso8601String()]))->assertUnprocessable()->assertJsonValidationErrors('published_at');
        $this->postJson('/posts', $this->draft(['status' => 'scheduled']))->assertUnprocessable()->assertJsonValidationErrors('published_at');
        $response = $this->postJson('/posts', $this->draft(['status' => 'scheduled', 'published_at' => now()->addDay()->toIso8601String()]));
        $response->assertOk()->assertJsonPath('status', 'scheduled');
        $this->get($response->json('url'))->assertNotFound();
        $this->getJson('/api/v1/posts/'.$response->json('id'))->assertNotFound();
        $this->putJson('/posts/'.$response->json('id'), $this->draft(['status' => 'published']))->assertOk();
        $this->get($response->json('url'))->assertOk()->assertSee('A careful account');
        $this->delete('/posts/'.$response->json('id'))->assertRedirect('/dashboard');
        $this->assertDatabaseHas('posts', ['id' => $response->json('id'), 'status' => 'archived']);
        $this->get($response->json('url'))->assertRedirect('/@'.$author->username);
    }

    public function test_story_validation_sanitization_revisions_and_slug_redirects(): void
    {
        $author = $this->user();
        $this->actingAs($author)->postJson('/posts', $this->draft(['title' => ['wrong'], 'category_ids' => [9999], 'cover_image' => 'javascript:alert(1)']))->assertUnprocessable()->assertJsonValidationErrors(['title', 'category_ids.0', 'cover_image']);
        $post = $this->story($author);
        $oldUrl = $post->url;
        $this->putJson('/posts/'.$post->id, $this->draft(['title' => 'Updated title', 'slug' => 'updated-title', 'status' => 'published', 'body_html' => '<p>Meaningful prose remains intact after unsafe attributes and scripts are removed.</p><script>alert(1)</script><img src="/images/test.jpg" onerror="alert(1)"><a href="javascript:alert(1)">Bad link</a>']))->assertOk();
        $saved = $post->fresh();
        $this->assertStringNotContainsString('<script', $saved->body_html);
        $this->assertStringNotContainsString('onerror', $saved->body_html);
        $this->assertStringNotContainsString('javascript:', $saved->body_html);
        $this->assertSame('A story worth reading', $saved->revisions()->first()->title);
        $this->get($oldUrl)->assertRedirect($saved->url);
    }

    public function test_livewire_autosave_restoration_and_cross_story_revision_access(): void
    {
        $author = $this->user();
        $post = $this->story($author, ['status' => 'draft', 'published_at' => null]);
        $revision = $post->revisions()->create(['edited_by' => $author->id, 'title' => 'Earlier version', 'body_html' => '<p>Earlier body</p>']);
        $foreign = $this->story()->revisions()->create(['edited_by' => $author->id, 'title' => 'Private revision', 'body_html' => '<p>Private body</p>']);
        Livewire::actingAs($author)->test(PostEditor::class, ['post' => $post])->set('title', 'Unsaved work')->call('restoreRevision', $revision->id)->assertSet('title', 'Earlier version')->assertSet('bodyHtml', '<p>Earlier body</p>');
        $this->assertDatabaseHas('post_revisions', ['post_id' => $post->id, 'title' => 'Unsaved work']);
        $this->assertThrows(fn () => Livewire::actingAs($author)->test(PostEditor::class, ['post' => $post])->call('restoreRevision', $foreign->id), \Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::actingAs($author)->test(PostEditor::class, ['post' => $post])->set('title', 'Autosaved title')->call('autosave')->assertHasNoErrors();
        $this->assertSame('Autosaved title', $post->fresh()->title);
        $post->update(['status' => 'published', 'published_at' => now()->subMinute()]);
        Livewire::actingAs($author)->test(PostEditor::class, ['post' => $post->fresh()])->set('title', 'Must remain unsaved')->call('autosave');
        $this->assertSame('Autosaved title', $post->fresh()->title);
    }

    public function test_open_editor_rechecks_email_verification_before_privileged_saves(): void
    {
        foreach (['editor', 'admin', 'super-admin'] as $role) {
            $actor = $this->user($role);
            $post = $this->story($actor, ['status' => 'draft', 'published_at' => null]);
            $component = Livewire::actingAs($actor)->test(PostEditor::class, ['post' => $post])->set('title', 'Must not save after verification changes');
            $actor->forceFill(['email_verified_at' => null])->save();
            $component->call('saveDraft')->assertForbidden();
            $this->assertSame('A story worth reading', $post->fresh()->title);
        }
    }

    public function test_media_upload_rejects_unsafe_files_and_returns_a_real_image(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user('reader'))->postJson('/media', ['image' => UploadedFile::fake()->image('cover.jpg')])->assertForbidden();
        $this->actingAs($this->user())->postJson('/media', ['image' => UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml')])->assertUnprocessable()->assertJsonValidationErrors('image');
        $response = $this->postJson('/media', ['image' => UploadedFile::fake()->image('cover.jpg', 120, 80)]);
        $response->assertOk()->assertJsonStructure(['url']);
        $this->assertDatabaseHas('media', ['collection_name' => 'stories', 'mime_type' => 'image/jpeg']);
        $this->assertNotEmpty(Storage::disk('public')->allFiles());
    }

    public function test_profile_photo_upload_is_shared_across_public_account_and_admin_surfaces_and_can_be_removed(): void
    {
        Storage::fake('public');
        $writer = $this->user(attributes: ['name' => 'Photo Writer', 'username' => 'photo_writer']);
        $post = $this->story($writer);
        $post->comments()->create(['user_id' => $writer->id, 'body' => 'A response from the writer.', 'status' => 'visible']);

        $this->actingAs($writer)->from('/settings')->patch('/settings', [
            'name' => $writer->name, 'username' => $writer->username,
            'avatar' => UploadedFile::fake()->image('portrait.png', 180, 240),
        ])->assertRedirect('/settings')->assertSessionHasNoErrors();
        $url = $writer->fresh()->avatar_url;
        $this->assertStringStartsWith('/storage/', $url);
        Storage::disk('public')->assertExists(substr($url, strlen('/storage/')));
        $this->get('/settings')->assertOk()->assertSeeInOrder(['profile-photo-editor', 'src="'.$url.'"'], false);
        $this->post('/logout');

        foreach ([
            ['/@'.$writer->username, 'profile-header'],
            ['/', 'lead-author'],
            ['/explore', 'story-author'],
            [$post->url, 'comment-head'],
        ] as [$route, $region]) {
            $this->get($route)->assertOk()->assertSeeInOrder([$region, 'src="'.$url.'"'], false);
        }

        $this->actingAs($this->user('admin'));
        foreach ([['people', 'admin-person-identity'], ['stories', 'admin-writer-identity'], ['comments&comment_status=visible', 'admin-case-meta']] as [$section, $region]) {
            $this->get('/admin?view='.$section)->assertOk()->assertSeeInOrder([$region, 'src="'.$url.'"'], false);
        }

        $this->actingAs($writer)->patch('/settings', ['name' => $writer->name, 'username' => $writer->username, 'remove_avatar' => true])->assertSessionHasNoErrors();
        $this->assertNull($writer->fresh()->avatar_url);
        $this->assertSame(0, $writer->fresh()->getMedia('avatar')->count());
        $this->get('/@'.$writer->username)->assertOk()->assertDontSee('src="'.$url.'"', false);
    }

    public function test_avatar_paths_and_initials_are_consistent_for_legacy_missing_and_unsafe_values(): void
    {
        foreach ([
            ['avatars/photo.webp', '/storage/avatars/photo.webp'],
            ['storage/avatars/photo.webp', '/storage/avatars/photo.webp'],
            ['/storage/avatars/photo.webp', '/storage/avatars/photo.webp'],
            ['https://images.example.test/photo.webp', 'https://images.example.test/photo.webp'],
            [null, null], ['//images.example.test/photo.webp', null], ['javascript:alert(1)', null], ['../private/photo.webp', null],
        ] as [$stored, $expected]) {
            $this->assertSame($expected, (new User(['avatar' => $stored]))->avatar_url);
        }

        $this->assertSame('ÉN', (new User(['name' => "  Élodie\t\n  Noël  "]))->initials());
        $this->assertSame('李', (new User(['name' => '李']))->initials());
        $this->assertSame('?', (new User(['name' => '   ']))->initials());
    }

    public function test_bookmarks_reactions_and_follows_toggle_for_the_current_reader(): void
    {
        $reader = $this->user('reader');
        $post = $this->story();
        $this->actingAs($reader)->postJson('/posts/'.$post->id.'/bookmark')->assertOk()->assertJsonPath('bookmarked', true);
        $this->get('/bookmarks')->assertOk()->assertSee($post->title);
        $this->postJson('/posts/'.$post->id.'/bookmark')->assertJsonPath('bookmarked', false);
        $this->postJson('/posts/'.$post->id.'/react')->assertJsonPath('reacted', true)->assertJsonPath('count', 1);
        $this->postJson('/posts/'.$post->id.'/react')->assertJsonPath('reacted', false)->assertJsonPath('count', 0);
        $tag = Tag::create(['name' => 'Notes', 'slug' => 'notes']);
        $category = Category::create(['name' => 'Life', 'slug' => 'life']);
        foreach (['/authors/'.$post->author_id.'/follow', '/tags/'.$tag->id.'/follow', '/categories/'.$category->id.'/follow'] as $path) {
            $this->postJson($path)->assertJsonPath('following', true);
            $this->postJson($path)->assertJsonPath('following', false);
        }
        $this->postJson('/authors/'.$reader->id.'/follow')->assertUnprocessable();
        $this->assertDatabaseCount('follows', 0);
        $this->assertDatabaseCount('bookmarks', 0);
        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_private_and_unpublished_stories_cannot_be_engaged_with(): void
    {
        $this->actingAs($this->user('reader'));
        foreach ([['status' => 'draft'], ['status' => 'archived'], ['published_at' => now()->addDay()]] as $attributes) {
            $post = $this->story(null, $attributes);
            foreach (['bookmark', 'react', 'comments', 'report'] as $action) {
                $this->postJson('/posts/'.$post->id.'/'.$action, ['body' => 'A response', 'reason' => 'An actionable report'])->assertNotFound();
            }
        }
        $suspended = $this->user('author', ['suspended_at' => now()]);
        $post = $this->story($suspended);
        $this->postJson('/posts/'.$post->id.'/bookmark')->assertNotFound();
        $this->postJson('/authors/'.$suspended->id.'/follow')->assertUnprocessable();
    }

    public function test_responses_validate_text_thread_ownership_and_visibility(): void
    {
        $reader = $this->user('reader');
        $post = $this->story();
        $this->actingAs($reader)->postJson('/posts/'.$post->id.'/comments', ['body' => '<b></b>'])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->postJson('/posts/'.$post->id.'/comments', ['body' => 'Valid body', 'website' => 'spam'])->assertUnprocessable()->assertJsonValidationErrors('website');
        $root = $this->postJson('/posts/'.$post->id.'/comments', ['body' => '<b>A useful response</b>'])->assertCreated()->json('comment.id');
        $this->assertDatabaseHas('comments', ['id' => $root, 'body' => 'A useful response']);
        $foreignPost = $this->story();
        $this->postJson('/posts/'.$foreignPost->id.'/comments', ['body' => 'Cross-story reply', 'parent_id' => $root])->assertNotFound();
        $reply = $this->postJson('/posts/'.$post->id.'/comments', ['body' => 'A useful reply', 'parent_id' => $root])->assertCreated()->json('comment.id');
        $this->postJson('/posts/'.$post->id.'/comments', ['body' => 'A nested reply', 'parent_id' => $reply])->assertCreated()->assertJsonPath('comment.parent_id', $root);
        $this->actingAs($this->user('reader'))->delete('/comments/'.$root)->assertForbidden();
        $this->actingAs($reader)->delete('/comments/'.$root)->assertRedirect($post->url.'#responses');
        $this->postJson('/posts/'.$post->id.'/comments', ['body' => 'Cannot reply to hidden thread', 'parent_id' => $reply])->assertNotFound();
        $this->get($post->url)->assertOk()->assertDontSee('A useful reply')->assertDontSee('A nested reply');
    }

    public function test_published_stories_and_quotes_are_free_to_everyone_while_responses_require_an_account(): void
    {
        $post = $this->story();
        $quote = 'The private interior of this story';
        $this->get($post->url)->assertOk()->assertSee($quote)->assertDontSee('Become a member');
        $this->getJson('/api/v1/posts/'.$post->id)->assertOk()->assertJsonPath('data.body_html', $post->body_html);
        $this->postJson('/posts/'.$post->id.'/quote-card', ['quote' => $quote])->assertOk()->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8');
        $this->postJson('/posts/'.$post->id.'/comments', ['body' => 'A response'])->assertUnauthorized();
        $this->actingAs($this->user('reader'))->postJson('/posts/'.$post->id.'/comments', ['body' => 'An open conversation'])->assertCreated();
        $schema = (new \App\Domain\Seo\SeoData(post: $post))->schema;
        $article = collect($schema['@graph'])->firstWhere('@type', 'BlogPosting');
        $this->assertTrue($article['isAccessibleForFree']);
        $this->assertArrayNotHasKey('hasPart', $article);
    }

    public function test_reports_deduplicate_open_reports_and_reject_hidden_comments(): void
    {
        $post = $this->story();
        $comment = $post->comments()->create(['user_id' => $post->author_id, 'body' => 'A visible response', 'status' => 'visible']);
        $this->actingAs($this->user('reader'))->postJson('/posts/'.$post->id.'/report', ['reason' => 'Short'])->assertUnprocessable();
        foreach (['/posts/'.$post->id.'/report', '/comments/'.$comment->id.'/report'] as $path) {
            $this->post($path, ['reason' => 'An actionable report'])->assertRedirect();
            $this->post($path, ['reason' => 'Another actionable report'])->assertRedirect();
        }
        $this->assertDatabaseCount('reports', 2);
        $comment->update(['status' => 'hidden']);
        $this->postJson('/comments/'.$comment->id.'/report', ['reason' => 'An actionable report'])->assertNotFound();
    }

    public function test_mark_notifications_read_is_scoped_to_the_current_account(): void
    {
        $reader = $this->user('reader');
        $other = $this->user('reader');
        foreach ([$reader, $other] as $user) {
            $user->notifyNow(new CommunityNotification('A response arrived', '/', 'comment'), ['database']);
        }
        $this->actingAs($reader)->get('/notifications')->assertOk()->assertSee('A response arrived');
        $this->post('/notifications/read')->assertRedirect();
        $this->assertSame(0, $reader->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
    }

    public function test_collections_enforce_ownership_validate_membership_and_preserve_stories(): void
    {
        $author = $this->user();
        $post = $this->story($author);
        $draft = $this->story($author, ['title' => 'Private collection draft', 'status' => 'draft', 'published_at' => null]);
        $foreign = $this->story();
        $this->actingAs($this->user('reader'))->postJson('/series', ['title' => 'Reading list'])->assertForbidden();
        $this->actingAs($author)->post('/series', ['title' => 'Collected notes', 'description' => 'Related stories'])->assertRedirect();
        $series = Series::firstOrFail();
        $this->patch('/series/'.$series->id, ['title' => 'Revised collection'])->assertRedirect();
        $this->postJson('/series/'.$series->id.'/posts', ['post_id' => $foreign->id])->assertUnprocessable();
        foreach ([$post, $draft] as $item) {
            $this->post('/series/'.$series->id.'/posts', ['post_id' => $item->id])->assertRedirect();
        }
        $this->post('/series/'.$series->id.'/posts', ['post_id' => $post->id])->assertRedirect();
        $this->assertSame(2, $series->posts()->count());
        $this->patchJson('/series/'.$series->id.'/order', ['positions' => [$post->id => 1]])->assertUnprocessable();
        $this->patch('/series/'.$series->id.'/order', ['positions' => [$post->id => 2, $draft->id => 1]])->assertRedirect();
        $this->assertSame([$draft->id, $post->id], $series->posts()->pluck('posts.id')->all());
        $publicUrl = '/@'.$author->username.'/series/'.$series->slug;
        $this->get($publicUrl)->assertOk()->assertSee($post->title)->assertDontSee($draft->title);
        $this->actingAs($this->user())->patch('/series/'.$series->id, ['title' => 'Stolen'])->assertForbidden();
        $this->delete('/series/'.$series->id)->assertForbidden();
        $this->post('/series/'.$series->id.'/posts', ['post_id' => $post->id])->assertForbidden();
        $this->delete('/series/'.$series->id.'/posts/'.$post->id)->assertForbidden();
        $this->patch('/series/'.$series->id.'/order', ['positions' => [$post->id => 1]])->assertForbidden();
        $this->actingAs($author)->delete('/series/'.$series->id.'/posts/'.$foreign->id)->assertNotFound();
        $this->delete('/series/'.$series->id.'/posts/'.$draft->id)->assertRedirect();
        $this->delete('/series/'.$series->id)->assertRedirect('/series');
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
        $this->assertDatabaseHas('posts', ['id' => $draft->id]);
        $this->get($publicUrl)->assertRedirect('/@'.$author->username);
    }

    public function test_quote_cards_require_actual_story_text_and_escape_markup(): void
    {
        $post = $this->story(null, ['title' => 'Story <script>alert(1)</script>', 'body_html' => '<p>A thoughtful &amp; careful phrase to remember.</p>']);
        $this->postJson('/posts/'.$post->id.'/quote-card', ['quote' => 'Invented quotation from nowhere'])->assertUnprocessable()->assertJsonValidationErrors('quote');
        $this->postJson('/posts/'.$post->id.'/quote-card', ['quote' => ['wrong']])->assertUnprocessable();
        $this->postJson('/posts/'.$post->id.'/quote-card', ['quote' => 'A thoughtful & careful phrase'])->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertSee('A thoughtful &amp; careful phrase', false)->assertDontSee('<script>', false);
        $post->update(['status' => 'draft']);
        $this->postJson('/posts/'.$post->id.'/quote-card', ['quote' => 'A thoughtful & careful phrase'])->assertNotFound();
    }

    public function test_api_scopes_private_posts_and_token_ownership(): void
    {
        $author = $this->user();
        $own = $this->story($author, ['status' => 'draft', 'title' => 'Own secret draft']);
        $foreign = $this->story(null, ['status' => 'draft', 'title' => 'Someone else secret draft']);
        $this->getJson('/api/v1/me')->assertUnauthorized();
        Sanctum::actingAs($author, ['profile:read']);
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.id', $author->id)->assertJsonMissingPath('data.password');
        $this->getJson('/api/v1/me/posts')->assertForbidden();
        Sanctum::actingAs($author, ['posts:read']);
        $this->getJson('/api/v1/me/posts')->assertOk()->assertJsonPath('data.0.id', $own->id)->assertJsonCount(1, 'data')->assertDontSee($foreign->title);
        $token = $author->createToken('own', ['profile:read'])->accessToken;
        $foreignToken = $foreign->author->createToken('foreign', ['profile:read'])->accessToken;
        $this->deleteJson('/api/v1/tokens/'.$token->id)->assertForbidden();
        Sanctum::actingAs($author, ['tokens:manage']);
        $this->deleteJson('/api/v1/tokens/'.$foreignToken->id)->assertNotFound();
        $this->deleteJson('/api/v1/tokens/'.$token->id)->assertOk();
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $foreignToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_public_api_and_search_validate_inputs_and_hide_unpublished_content(): void
    {
        $public = $this->story();
        $this->story(null, ['status' => 'draft']);
        $this->story(null, ['published_at' => now()->addDay()]);
        $this->story($this->user('author', ['suspended_at' => now()]));
        $this->getJson('/api/v1/posts')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $public->id);
        foreach (['per_page=51', 'page=0', 'q[]=bad'] as $query) {
            $this->getJson('/api/v1/posts?'.$query)->assertUnprocessable();
        }
        foreach (['/', '/explore', '/trending'] as $path) {
            $this->get($path.'?q[]=bad')->assertStatus(400);
            $this->get($path.'?page=0')->assertStatus(400);
        }
        $this->getJson('/api/v1/posts/'.$public->id)->assertJsonPath('data.body_html', $public->body_html);
    }

    public function test_editor_moderates_reports_comments_and_stories_without_reopening_reports(): void
    {
        $editor = $this->user('editor');
        $post = $this->story();
        $comment = $post->comments()->create(['user_id' => $post->author_id, 'body' => 'A reported response', 'status' => 'visible']);
        $report = Report::create(['user_id' => $editor->id, 'reportable_type' => \App\Models\Comment::class, 'reportable_id' => $comment->id, 'status' => 'open', 'reason' => 'Needs review']);
        $this->actingAs($editor)->patch('/admin/reports/'.$report->id.'?report_status=open&reports_page=2&view=reports&evil=1', ['action' => 'hide'])->assertRedirect(route('admin', ['view' => 'reports', 'report_status' => 'open', 'reports_page' => 2]));
        $this->assertSame('hidden', $comment->fresh()->status);
        $this->assertSame('resolved', $report->fresh()->status);
        $this->patch('/admin/reports/'.$report->id, ['action' => 'dismiss'])->assertRedirect();
        $this->assertSame('resolved', $report->fresh()->status);
        $this->patch('/admin/comments/'.$comment->id, ['status' => 'visible'])->assertRedirect();
        $this->assertSame('visible', $comment->fresh()->status);
        $this->patchJson('/admin/comments/'.$comment->id, ['status' => 'published'])->assertUnprocessable();
        $this->patch('/admin/posts/'.$post->id)->assertRedirect();
        $this->assertSame('archived', $post->fresh()->status);
        $this->get($post->url)->assertNotFound();
    }

    public function test_taxonomy_prevents_cross_type_collisions_and_invalid_names(): void
    {
        $this->actingAs($this->user('editor'))->post('/admin/taxonomy/categories', ['name' => 'Living well', 'description' => 'Notes on life'])->assertRedirect(route('admin', ['view' => 'topics']));
        $this->postJson('/admin/taxonomy/tags', ['name' => 'Living well'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/admin/taxonomy/tags', ['name' => '!!!'])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/admin/taxonomy/wrong', ['name' => 'Wrong'])->assertNotFound();
        $this->post('/admin/taxonomy/tags', ['name' => 'Creativity'])->assertRedirect();
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('tags', 1);
    }

    public function test_platform_settings_validate_integrations_and_save_authorized_changes(): void
    {
        $settings = ['site_name' => 'Audit publication', 'training_bot_policy' => 'block', 'mail_from_name' => 'Audit', 'mail_from_address' => 'audit@example.test'];
        $this->actingAs($this->user('editor'))->patchJson('/admin/settings', $settings)->assertForbidden();
        $this->actingAs($this->user('admin'))->patch('/admin/settings', $settings)->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'site_name', 'value' => '"Audit publication"']);
        config(['analytics.plausible_script_url' => '', 'analytics.plausible_endpoint' => '']);
        $this->patchJson('/admin/settings', [...$settings, 'analytics_enabled' => true])->assertUnprocessable()->assertJsonValidationErrors('analytics_enabled');
        $this->patchJson('/admin/settings', [...$settings, 'mail_from_name' => "Name\r\nBcc: attacker", 'google_verification' => '<script>'])->assertUnprocessable()->assertJsonValidationErrors(['mail_from_name', 'google_verification']);
    }

    public function test_removed_paid_product_endpoints_are_not_available(): void
    {
        foreach (['/membership', '/earnings', '/payouts'] as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->actingAs($this->user('admin'));
        foreach (['/membership/checkout', '/membership/portal', '/membership/connect', '/payouts', '/payouts/1/process', '/payouts/1/reconcile', '/stripe/webhook'] as $path) {
            $this->assertContains($this->postJson($path)->getStatusCode(), [404, 405]);
        }
    }
}
