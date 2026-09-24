<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('excerpt')->nullable();
            $table->longText('body_html')->nullable();
            $table->json('body_json')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('status')->default('draft')->index();
            $table->boolean('is_premium')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedInteger('reading_time')->default(1);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url', 2048)->nullable();
            $table->string('og_image_path')->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();
            $table->unique(['author_id', 'slug']);
            $table->index(['status', 'published_at']);
        });
        foreach (['tags', 'categories'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
        foreach (['post_tag' => 'tag', 'post_category' => 'category'] as $name => $relation) {
            Schema::create($name, function (Blueprint $table) use ($relation) {
                $table->foreignId('post_id')->constrained()->cascadeOnDelete();
                $table->foreignId($relation.'_id')->constrained()->cascadeOnDelete();
                $table->primary(['post_id', $relation.'_id']);
            });
        }
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->string('status')->default('visible')->index();
            $table->timestamps();
        });
        Schema::create('reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('clap');
            $table->timestamps();
            $table->unique(['user_id', 'post_id', 'type']);
        });
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'post_id']);
        });
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('followable');
            $table->timestamps();
            $table->unique(['follower_id', 'followable_id', 'followable_type']);
        });
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reportable');
            $table->text('reason');
            $table->string('status')->default('open')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->longText('body_html')->nullable();
            $table->json('body_json')->nullable();
            $table->timestamps();
        });
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('series_post', function (Blueprint $table) {
            $table->foreignId('series_id')->constrained('series')->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->primary(['series_id', 'post_id']);
        });
    }

    public function down(): void
    {
        foreach (['series_post', 'series', 'post_revisions', 'reports', 'follows', 'bookmarks', 'reactions', 'comments', 'post_category', 'post_tag', 'categories', 'tags', 'posts'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
