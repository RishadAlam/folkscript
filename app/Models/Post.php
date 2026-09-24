<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['author_id', 'title', 'slug', 'excerpt', 'body_html', 'body_json', 'cover_image', 'status', 'is_premium', 'published_at', 'reading_time', 'meta_title', 'meta_description', 'canonical_url', 'og_image_path', 'views'];

    protected function casts(): array
    {
        return ['body_json' => 'array', 'is_premium' => 'boolean', 'published_at' => 'datetime', 'views' => 'integer'];
    }

    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function tags(): BelongsToMany { return $this->belongsToMany(Tag::class); }
    public function categories(): BelongsToMany { return $this->belongsToMany(Category::class, 'post_category'); }
    public function comments(): HasMany { return $this->hasMany(Comment::class); }
    public function reactions(): HasMany { return $this->hasMany(Reaction::class); }
    public function bookmarks(): HasMany { return $this->hasMany(Bookmark::class); }
    public function revisions(): HasMany { return $this->hasMany(Revision::class)->latest(); }
    public function series(): BelongsToMany { return $this->belongsToMany(Series::class, 'series_post')->withPivot('order'); }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('published_at', '<=', now())->whereHas('author', fn (Builder $q) => $q->whereNull('suspended_at'));
    }

    public function scopeWithCard(Builder $query): Builder
    {
        return $query->with(['author', 'tags', 'categories'])->withCount([
            'reactions', 'bookmarks',
            'comments' => fn (Builder $q) => $q->where('status', 'visible')->where(fn (Builder $thread) => $thread
                ->whereNull('parent_id')
                ->orWhereHas('parent', fn (Builder $parent) => $parent->where('status', 'visible')->whereNull('parent_id'))),
        ])->withExists(['bookmarks as is_bookmarked' => fn (Builder $q) => $q->where('user_id', auth()->id() ?? 0)]);
    }

    public function getUrlAttribute(): string { return '/@'.$this->author->username.'/'.$this->slug; }
    public function getCoverUrlAttribute(): string { return $this->cover_image ?: '/images/story-attention.jpg'; }
    public function shouldBeSearchable(): bool { return $this->status === 'published' && $this->published_at?->isPast() && $this->author && ! $this->author->suspended_at; }
    public function searchIndexShouldBeUpdated(): bool
    {
        return $this->wasRecentlyCreated || $this->wasChanged(['author_id', 'title', 'excerpt', 'body_html', 'is_premium', 'status', 'published_at']);
    }

    public function toSearchableArray(): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'excerpt' => $this->excerpt, 'body' => $this->is_premium ? '' : strip_tags($this->body_html ?? ''), 'author' => $this->author->name, 'tags' => $this->tags->pluck('name')->all()];
    }
}
