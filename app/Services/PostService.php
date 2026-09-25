<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PostService
{
    public function save(User $user, array $input, ?Post $post = null): Post
    {
        $post ? Gate::forUser($user)->authorize('update', $post) : Gate::forUser($user)->authorize('create', Post::class);
        $data = Validator::make($input, [
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body_html' => ['nullable', 'string', 'max:500000'],
            'body_json' => ['nullable', 'array'],
            'cover_image' => ['nullable', 'string', 'max:2048', 'regex:~^(https://[^\s]+|/(?:storage|images)/[^\s]+)$~'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled', 'archived'])],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'canonical_url' => ['nullable', 'url:http,https', 'max:2048'],
            'category_ids' => ['nullable', 'array', 'max:5'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'tag_names' => ['nullable', 'string', 'max:250'],
        ])->validate();

        if (in_array($data['status'], ['published', 'scheduled'], true) && ! $user->can('posts.publish')) {
            abort(403, 'Publishing permission is required.');
        }

        $data['body_html'] = $this->sanitize($data['body_html'] ?? '');
        $plain = trim(html_entity_decode(strip_tags($data['body_html'])));
        if (in_array($data['status'], ['published', 'scheduled'], true)) {
            if (mb_strlen($plain) < 40) {
                throw ValidationException::withMessages(['body_html' => 'Write at least 40 characters before publishing your story.']);
            }
        }
        if ($data['status'] === 'scheduled' && (empty($data['published_at']) || ! \Carbon\Carbon::parse($data['published_at'])->isFuture())) {
            throw ValidationException::withMessages(['published_at' => 'Choose a future date and time to schedule your story.']);
        }

        $data['excerpt'] = ($data['excerpt'] ?? null) ?: Str::limit($plain, 220);
        $data['reading_time'] = max(1, (int) ceil(str_word_count($plain) / 220));
        $data['author_id'] = $post?->author_id ?? $user->id;
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $data['slug'] = $data['slug'] ?: 'untitled-story';
        $baseSlug = $data['slug'];
        $suffix = 2;
        while (Post::where('author_id', $data['author_id'])->where('slug', $data['slug'])->when($post, fn ($q) => $q->whereKeyNot($post->id))->exists()) {
            $data['slug'] = $baseSlug.'-'.$suffix++;
        }
        if ($data['status'] === 'published') {
            $data['published_at'] = $post?->published_at?->isPast() ? $post->published_at : now();
        } elseif ($data['status'] !== 'scheduled') {
            $data['published_at'] = null;
        }
        $data['meta_title'] = ($data['meta_title'] ?? null) ?: null;
        $data['meta_description'] = ($data['meta_description'] ?? null) ?: null;

        $tagsChanged = false;
        $categoriesChanged = false;
        $saved = DB::transaction(function () use ($data, $post, $user, &$tagsChanged, &$categoriesChanged) {
            $oldPath = $post?->url;
            if ($post && ($post->body_html !== $data['body_html'] || $post->title !== $data['title'])) {
                $post->revisions()->create(['edited_by' => $user->id, 'title' => $post->title, 'body_html' => $post->body_html, 'body_json' => $post->body_json]);
            }
            $post ??= new Post;
            $post->fill(collect($data)->except(['category_ids', 'tag_names'])->all())->save();
            $categoryChanges = $post->categories()->sync($data['category_ids'] ?? []);
            $categoriesChanged = count($categoryChanges['attached']) + count($categoryChanges['detached']) > 0;
            $tagIds = collect(explode(',', $data['tag_names'] ?? ''))->map(fn ($name) => trim($name))->filter(fn ($name) => $name !== '' && Str::slug($name) !== '')->take(5)->map(function ($name) {
                return Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => Str::limit($name, 50, '')])->id;
            });
            $tagChanges = $post->tags()->sync($tagIds);
            $tagsChanged = count($tagChanges['attached']) + count($tagChanges['detached']) > 0;
            if (class_exists(Redirect::class) && \Illuminate\Support\Facades\Schema::hasTable('redirects')) {
                if ($post->status === 'published') {
                    Redirect::where('from_path', $post->url)->delete();
                }
                if ($oldPath && $oldPath !== $post->url) {
                    Redirect::where('to_path', $oldPath)->update(['to_path' => $post->url]);
                    Redirect::updateOrCreate(['from_path' => $oldPath], ['to_path' => $post->url, 'status_code' => 301]);
                }
            }
            if (function_exists('activity')) {
                activity()->causedBy($user)->performedOn($post)->withProperties(['status' => $post->status])->log('Story saved');
            }
            return $post;
        });
        if ($tagsChanged || $categoriesChanged) {
            DB::afterCommit(fn () => app(PublicDiscoveryCache::class)->invalidate());
        }
        if ($tagsChanged && config('scout.driver') === 'meilisearch') {
            DB::afterCommit(function () use ($saved): void {
                $saved->unsetRelation('tags');
                $saved->shouldBeSearchable() ? $saved->searchable() : $saved->unsearchable();
            });
        }
        event('post.saved', [$saved]);
        return $saved;
    }

    public function sanitize(string $html): string
    {
        if (class_exists(\HTMLPurifier::class)) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'div[data-youtube-video],iframe[src|width|height|allowfullscreen|frameborder],p,br,strong,b,em,i,u,s,h2[id],h3[id],h4,blockquote,ul,ol,li,a[href|title|rel],img[src|alt|width|height],pre,code,hr,table,thead,tbody,tr,th,td');
            $config->set('HTML.SafeIframe', true);
            $config->set('URI.SafeIframeRegexp', '%^https://(?:www\\.)?youtube-nocookie\\.com/embed/%');
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('Cache.SerializerPath', storage_path('framework/cache'));
            $definition = $config->getHTMLDefinition(true);
            $definition->addAttribute('div', 'data-youtube-video', 'Text');
            $definition->addAttribute('iframe', 'allowfullscreen', 'Bool#allowfullscreen');
            return (new \HTMLPurifier($config))->purify($html);
        }
        return nl2br(e(strip_tags($html)));
    }
}
