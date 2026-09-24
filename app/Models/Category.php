<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description'];
    public function posts(): BelongsToMany { return $this->belongsToMany(Post::class, 'post_category'); }
    public function followers(): MorphMany { return $this->morphMany(Follow::class, 'followable'); }
    public function getUrlAttribute(): string { return '/topic/'.$this->slug; }
}
