<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure 'dashboard.view' permission exists
        $perm = DB::table('permissions')->where('slug', 'dashboard.view')->first();
        if (!$perm) {
            $permId = DB::table('permissions')->insertGetId([
                'slug' => 'dashboard.view',
                'name' => 'Dashboard View',
                'module' => 'Dashboard',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $permId = $perm->id;
        }

        // 2. Attach dashboard.view to all existing roles
        $roles = DB::table('roles')->get();
        foreach ($roles as $role) {
            $exists = DB::table('role_permission')
                ->where('role_id', $role->id)
                ->where('permission_id', $permId)
                ->exists();

            if (!$exists) {
                DB::table('role_permission')->insert([
                    'role_id' => $role->id,
                    'permission_id' => $permId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Ensure admin role is assigned to user ID 1 or admin email if role_id is null
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        if ($adminRole) {
            DB::table('users')
                ->whereNull('role_id')
                ->where(function ($q) {
                    $q->where('id', 1)->orWhere('email', 'like', '%admin%');
                })
                ->update(['role_id' => $adminRole->id]);
        }
    }

    public function down(): void
    {
        // No destructive rollback needed
    }
};
