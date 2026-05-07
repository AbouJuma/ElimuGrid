<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DebugPermissionsSeeder extends Seeder
{
    public function run()
    {
        echo "=== DEBUGGING PERMISSIONS ===\n\n";
        
        // Check roles
        $roles = DB::table('roles')->get();
        echo "Roles found: " . $roles->count() . "\n";
        foreach ($roles as $role) {
            echo "  - {$role->name} (ID: {$role->id})\n";
        }
        echo "\n";
        
        // Check library permissions
        $libraryPerms = DB::table('permissions')
            ->where('name', 'like', 'book%')
            ->get();
        echo "Library permissions found: " . $libraryPerms->count() . "\n";
        foreach ($libraryPerms as $perm) {
            echo "  - {$perm->name} (ID: {$perm->id})\n";
        }
        echo "\n";
        
        // Check School Admin permissions
        $schoolAdmin = DB::table('roles')->where('name', 'School Admin')->first();
        if ($schoolAdmin) {
            echo "School Admin role found (ID: {$schoolAdmin->id})\n";
            $assignedPerms = DB::table('role_has_permissions')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->where('role_id', $schoolAdmin->id)
                ->pluck('permissions.name');
            echo "Assigned permissions: " . $assignedPerms->implode(', ') . "\n\n";
        } else {
            echo "School Admin role NOT found!\n\n";
        }
        
        // Check if Library Management feature exists
        $feature = DB::table('features')->where('name', 'Library Management')->first();
        if ($feature) {
            echo "Library Management feature found (ID: {$feature->id})\n";
        } else {
            echo "Library Management feature NOT found!\n";
        }
    }
}
