<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $libraryPermissions = [
            'book-list',
            'book-create',
            'book-update',
            'book-delete',
            'book-issue-list',
            'book-issue-create',
            'book-issue-return',
            'book-report-view',
        ];

        // Create permissions if they don't exist
        foreach ($libraryPermissions as $permissionName) {
            $exists = DB::table('permissions')->where('name', $permissionName)->first();
            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        // Get Super Admin role ID (usually role with name 'Super Admin' or ID 1)
        $superAdminRole = DB::table('roles')->where('name', 'Super Admin')->first();
        
        if ($superAdminRole) {
            foreach ($libraryPermissions as $permissionName) {
                $permission = DB::table('permissions')->where('name', $permissionName)->first();
                if ($permission) {
                    // Check if permission already assigned
                    $exists = DB::table('role_has_permissions')
                        ->where('role_id', $superAdminRole->id)
                        ->where('permission_id', $permission->id)
                        ->first();
                    
                    if (!$exists) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $superAdminRole->id,
                            'permission_id' => $permission->id,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $libraryPermissions = [
            'book-list',
            'book-create',
            'book-update',
            'book-delete',
            'book-issue-list',
            'book-issue-create',
            'book-issue-return',
            'book-report-view',
        ];

        // Remove permissions from roles
        $permissionIds = DB::table('permissions')
            ->whereIn('name', $libraryPermissions)
            ->pluck('id');

        DB::table('role_has_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
