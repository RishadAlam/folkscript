<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Never discard live customer or transfer history during an unattended upgrade.
        foreach (['subscriptions', 'subscription_items'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new LogicException('Archive and retire existing billing records before removing paid publishing.');
            }
        }
        foreach (['stripe_id', 'stripe_connect_id'] as $column) {
            if (Schema::hasColumn('users', $column) && DB::table('users')->whereNotNull($column)->exists()) {
                throw new LogicException('Retire connected billing accounts before removing paid publishing.');
            }
        }
        if (Schema::hasTable('payouts') && DB::table('payouts')->where(function ($query) {
            $query->whereNotNull('stripe_transfer_id')->orWhereNotNull('stripe_destination')
                ->orWhere('status', '!=', 'pending')
                ->orWhere('reference', 'not like', 'DEMO-NOT-REAL-ALLOCATION-%');
        })->exists()) {
            throw new LogicException('Archive the earnings ledger before removing paid publishing.');
        }

        // Preserve every account and its other roles while retiring the paid reader tier.
        DB::transaction(function () {
            foreach (DB::table('roles')->where('name', 'premium-reader')->get() as $legacyRole) {
                $readerId = DB::table('roles')->where('name', 'reader')->where('guard_name', $legacyRole->guard_name)->value('id');
                if (! $readerId) {
                    $readerId = DB::table('roles')->insertGetId([
                        'name' => 'reader', 'guard_name' => $legacyRole->guard_name,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $commentPermission = DB::table('permissions')->where('name', 'comments.create')
                        ->where('guard_name', $legacyRole->guard_name)->value('id');
                    if ($commentPermission) {
                        DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $readerId, 'permission_id' => $commentPermission]);
                    }
                }
                foreach (DB::table('model_has_roles')->where('role_id', $legacyRole->id)->get() as $assignment) {
                    DB::table('model_has_roles')->insertOrIgnore([
                        'role_id' => $readerId, 'model_type' => $assignment->model_type, 'model_id' => $assignment->model_id,
                    ]);
                }
                DB::table('model_has_roles')->where('role_id', $legacyRole->id)->delete();
                DB::table('role_has_permissions')->where('role_id', $legacyRole->id)->delete();
                DB::table('roles')->where('id', $legacyRole->id)->delete();
            }
            $permissionIds = DB::table('permissions')->where('name', 'payouts.process')->pluck('id');
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        });
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('payouts');
        if (Schema::hasIndex('users', 'users_stripe_id_index')) {
            Schema::table('users', fn (Blueprint $table) => $table->dropIndex('users_stripe_id_index'));
        }
        $columns = array_values(array_filter(
            ['stripe_id', 'stripe_connect_id', 'pm_type', 'pm_last_four', 'trial_ends_at'],
            fn ($column) => Schema::hasColumn('users', $column),
        ));
        if ($columns) Schema::table('users', fn (Blueprint $table) => $table->dropColumn($columns));
        if (Schema::hasColumn('posts', 'is_premium')) {
            Schema::table('posts', fn (Blueprint $table) => $table->dropColumn('is_premium'));
        }
    }

    public function down(): void
    {
        throw new LogicException('Restore a pre-migration database backup to undo the retirement of paid publishing.');
    }
};
