<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Series extends Model
{
    protected $table = 'series';
    protected $fillable = ['author_id', 'title', 'slug', 'description'];
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function posts(): BelongsToMany { return $this->belongsToMany(Post::class, 'series_post')->withPivot('order')->orderByPivot('order'); }
}
