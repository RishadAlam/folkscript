<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('location')->nullable();
            $table->json('social_links')->nullable();
            $table->boolean('newsletter_enabled')->default(true);
            $table->timestamp('suspended_at')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('oauth_provider')->nullable();
            $table->string('oauth_id')->nullable();
            $table->unique(['oauth_provider', 'oauth_id']);
            $table->string('stripe_connect_id')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['username', 'bio', 'avatar', 'cover_image', 'location', 'social_links', 'newsletter_enabled', 'suspended_at', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'oauth_provider', 'oauth_id', 'stripe_connect_id']));
    }
};
