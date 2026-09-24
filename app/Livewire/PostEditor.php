<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PostEditor extends Component
{
    #[Locked]
    public ?Post $post = null;
    public string $title = '';
    public string $excerpt = '';
    public string $bodyHtml = '';
    public array $bodyJson = [];
    public string $slug = '';
    public string $coverImage = '';
    #[Locked]
    public string $status = 'draft';
    public bool $isPremium = false;
    public string $publishedAt = '';
    public string $metaTitle = '';
    public string $metaDescription = '';
    public string $canonicalUrl = '';
    public array $categoryIds = [];
    public string $tagNames = '';
    public string $savedAt = '';

    public function mount(?Post $post = null): void
    {
        $post ? Gate::authorize('update', $post) : Gate::authorize('create', Post::class);
        $this->post = $post;
        if ($post) {
            $this->title = $post->title;
            $this->excerpt = $post->excerpt ?? '';
            $this->bodyHtml = $post->body_html ?? '';
            $this->bodyJson = $post->body_json ?? [];
            $this->slug = $post->slug;
            $this->coverImage = $post->cover_image ?? '';
            $this->status = $post->status;
            $this->isPremium = $post->is_premium;
            $this->publishedAt = $post->published_at?->format('Y-m-d\TH:i') ?? '';
            $this->metaTitle = $post->meta_title ?? '';
            $this->metaDescription = $post->meta_description ?? '';
            $this->canonicalUrl = $post->canonical_url ?? '';
            $this->categoryIds = $post->categories->modelKeys();
            $this->tagNames = $post->tags->pluck('name')->join(', ');
        }
    }

    public function saveDraft(): array
    {
        return $this->persist('draft');
    }

    public function autosave(): array
    {
        // Scheduled and published stories change only through an explicit action.
        if (($this->post && $this->post->fresh()?->status !== 'draft') || mb_strlen(trim($this->title)) < 3) {
            return ['saved' => false];
        }

        return $this->persist('draft');
    }

    public function publish(): array
    {
        return $this->persist('published') + ['redirect' => $this->post->url];
    }

    public function schedule(): array
    {
        $result = $this->persist('scheduled');
        session()->flash('success', 'Your story is scheduled.');

        return $result + ['redirect' => route('dashboard')];
    }

    private function persist(string $status): array
    {
        $this->resetErrorBag();
        try {
            $this->post = app(PostService::class)->save(auth()->user(), ['title' => $this->title, 'excerpt' => $this->excerpt, 'body_html' => $this->bodyHtml, 'body_json' => $this->bodyJson, 'slug' => $this->slug ?: null, 'cover_image' => $this->coverImage ?: null, 'status' => $status, 'is_premium' => $this->isPremium, 'published_at' => $this->publishedAt ?: null, 'meta_title' => $this->metaTitle ?: null, 'meta_description' => $this->metaDescription ?: null, 'canonical_url' => $this->canonicalUrl ?: null, 'category_ids' => $this->categoryIds, 'tag_names' => $this->tagNames], $this->post);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $fields = ['body_html' => 'bodyHtml', 'cover_image' => 'coverImage', 'published_at' => 'publishedAt', 'meta_title' => 'metaTitle', 'meta_description' => 'metaDescription', 'canonical_url' => 'canonicalUrl', 'category_ids' => 'categoryIds', 'tag_names' => 'tagNames'];
            throw \Illuminate\Validation\ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn ($messages, $key) => [$fields[$key] ?? (str_starts_with($key, 'category_ids.') ? 'categoryIds' : $key) => $messages])->all());
        }
        $this->status = $this->post->status;
        $this->slug = $this->post->slug;
        $this->savedAt = now()->format('g:i A');

        return ['saved' => true, 'time' => $this->savedAt, 'savedAt' => now()->toIso8601String(), 'editUrl' => route('posts.edit', $this->post), 'status' => $this->status];
    }

    public function restoreRevision(int $id): array
    {
        abort_unless($this->post, 404);
        Gate::authorize('update', $this->post);
        $revision = $this->post->revisions()->findOrFail($id);
        $this->validate(['title' => 'string|max:200', 'bodyHtml' => 'string|max:500000', 'bodyJson' => 'array']);
        if ($this->title !== $revision->title || $this->bodyHtml !== ($revision->body_html ?? '')) {
            $this->post->revisions()->create(['edited_by' => auth()->id(), 'title' => $this->title, 'body_html' => app(PostService::class)->sanitize($this->bodyHtml), 'body_json' => $this->bodyJson]);
        }
        $this->title = $revision->title;
        $this->bodyHtml = $revision->body_html ?? '';
        $this->bodyJson = $revision->body_json ?? [];
        return ['title' => $this->title, 'html' => $this->bodyHtml];
    }

    public function render()
    {
        return view('livewire.post-editor', [
            'categories' => Category::orderBy('name')->get(),
            'revisions' => $this->post?->revisions()->latest()->limit(10)->get() ?? collect(),
        ]);
    }
}
