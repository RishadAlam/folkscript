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

    public function saveDraft(): void { $this->persist('draft'); }
    public function autosave(): void { if (trim($this->title) !== '' && $this->post?->status !== 'published') { $this->persist($this->post?->status ?? 'draft'); } }
    public function publish() { $this->persist('published'); return $this->redirect($this->post->url); }
    public function schedule() { $this->persist('scheduled'); session()->flash('success', 'Your story is scheduled.'); return $this->redirect(route('dashboard')); }

    private function persist(string $status): void
    {
        $this->post = app(PostService::class)->save(auth()->user(), ['title' => $this->title, 'excerpt' => $this->excerpt, 'body_html' => $this->bodyHtml, 'body_json' => $this->bodyJson, 'slug' => $this->slug ?: null, 'cover_image' => $this->coverImage ?: null, 'status' => $status, 'is_premium' => $this->isPremium, 'published_at' => $this->publishedAt ?: null, 'meta_title' => $this->metaTitle ?: null, 'meta_description' => $this->metaDescription ?: null, 'canonical_url' => $this->canonicalUrl ?: null, 'category_ids' => $this->categoryIds, 'tag_names' => $this->tagNames], $this->post);
        $this->status = $this->post->status;
        $this->slug = $this->post->slug;
        $this->savedAt = now()->format('g:i A');
        $this->dispatch('story-saved', time: $this->savedAt);
    }

    public function restoreRevision(int $id): void
    {
        abort_unless($this->post, 404);
        Gate::authorize('update', $this->post);
        $revision = $this->post->revisions()->findOrFail($id);
        $this->title = $revision->title;
        $this->bodyHtml = $revision->body_html ?? '';
        $this->bodyJson = $revision->body_json ?? [];
        $this->dispatch('revision-restored', html: $this->bodyHtml);
    }

    public function render()
    {
        $title = $this->metaTitle ?: $this->title;
        $seoChecks = ['title' => mb_strlen($title) >= 50 && mb_strlen($title) <= 60, 'description' => mb_strlen($this->metaDescription) >= 150 && mb_strlen($this->metaDescription) <= 160, 'image_alt' => preg_match('/<img[^>]+alt=["\'][^"\']+["\']/i', $this->bodyHtml) === 1, 'internal_link' => str_contains($this->bodyHtml, 'href="/@') || str_contains($this->bodyHtml, config('app.url').'/@'), 'opening' => preg_match('/\\b(is|are|means|helps|gives|makes|allows|begins|starts|can|should)\\b/i', implode(' ', array_slice(preg_split('/\\s+/', strip_tags($this->bodyHtml)), 0, 40))) === 1];
        return view('livewire.post-editor', ['categories' => Category::all(), 'revisions' => $this->post?->revisions()->limit(10)->get() ?? collect(), 'seoChecks' => $seoChecks]);
    }
}
