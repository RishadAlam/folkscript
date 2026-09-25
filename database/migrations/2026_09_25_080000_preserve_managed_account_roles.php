<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('access_role_assigned_at')->nullable();
        });

        // Preserve access decisions made before this marker existed, including unverified readers.
        DB::table('users')->whereIn('id', DB::table('activity_log')
            ->select('subject_id')->where('subject_type', 'App\\Models\\User')
            ->where('description', 'Updated account access'))
            ->update(['access_role_assigned_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('access_role_assigned_at');
        });
    }
};
