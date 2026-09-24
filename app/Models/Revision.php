<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revision extends Model
{
    protected $table = 'post_revisions';
    protected $fillable = ['post_id', 'edited_by', 'title', 'body_html', 'body_json'];
    protected function casts(): array { return ['body_json' => 'array']; }
    public function post(): BelongsTo { return $this->belongsTo(Post::class); }
    public function editor(): BelongsTo { return $this->belongsTo(User::class, 'edited_by'); }
}
