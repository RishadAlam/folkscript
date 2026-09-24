<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements MustVerifyEmail, HasMedia
{
    use Billable, HasApiTokens, HasFactory, HasRoles, InteractsWithMedia, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = ['name', 'username', 'email', 'password', 'bio', 'avatar', 'cover_image', 'location', 'social_links', 'newsletter_enabled'];
    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'stripe_id', 'stripe_connect_id'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'social_links' => 'array', 'newsletter_enabled' => 'boolean', 'two_factor_confirmed_at' => 'datetime', 'suspended_at' => 'datetime', 'trial_ends_at' => 'datetime'];
    }

    public function pinnedPost(): BelongsTo { return $this->belongsTo(Post::class, 'pinned_post_id')->published(); }
    public function posts(): HasMany { return $this->hasMany(Post::class, 'author_id'); }
    public function followers(): MorphMany { return $this->morphMany(Follow::class, 'followable'); }
    public function following(): HasMany { return $this->hasMany(Follow::class, 'follower_id'); }
    public function bookmarks(): BelongsToMany { return $this->belongsToMany(Post::class, 'bookmarks')->withTimestamps(); }
    public function reactions(): HasMany { return $this->hasMany(Reaction::class); }
    public function comments(): HasMany { return $this->hasMany(Comment::class); }

    public function canWrite(): bool
    {
        return ! $this->suspended_at && $this->hasVerifiedEmail() && $this->hasAnyRole(['author', 'editor', 'admin', 'super-admin']);
    }

    public function hasPremiumAccess(): bool
    {
        return ! $this->suspended_at && ($this->hasAnyRole(['premium-reader', 'admin', 'super-admin']) || $this->subscribed('default'));
    }

    public function markEmailAsVerified(): bool
    {
        $result = $this->forceFill(['email_verified_at' => $this->freshTimestamp()])->save();
        $this->assignRole('author');
        return $result;
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
    }

    public function registerMediaCollections(): void
    {
        foreach (['stories', 'avatar', 'cover_image'] as $collection) {
            $definition = $this->addMediaCollection($collection)
                ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
            if ($collection !== 'stories') { $definition->singleFile(); }
        }
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Produce the URL returned by upload requests immediately, even without a queue worker.
        $avatar = $media?->collection_name === 'avatar';
        $this->addMediaConversion('web')->format('webp')->quality(82)
            ->fit($avatar ? Fit::Crop : Fit::Max, $avatar ? 320 : 1800, $avatar ? 320 : 1800)
            ->performOnCollections('stories', 'cover_image', 'avatar')->nonOptimized()->nonQueued();
    }
}
