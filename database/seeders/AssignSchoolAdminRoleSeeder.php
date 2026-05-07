<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignSchoolAdminRoleSeeder extends Seeder
{
    public function run()
    {
        // Get School Admin role
        $schoolAdminRole = DB::table('roles')->where('name', 'School Admin')->first();
        
        if (!$schoolAdminRole) {
            echo "School Admin role not found!\n";
            return;
        }
        
        // Find user
        $user = DB::table('users')->where('email', 'aboukisiaki@gmail.com')->first();
        
        if (!$user) {
            echo "User aboukisiaki@gmail.com not found!\n";
            return;
        }
        
        // Check if already has role
        $hasRole = DB::table('model_has_roles')
            ->where('role_id', $schoolAdminRole->id)
            ->where('model_id', $user->id)
            ->where('model_type', 'App\Models\User')
            ->first();
        
        if (!$hasRole) {
            DB::table('model_has_roles')->insert([
                'role_id' => $schoolAdminRole->id,
                'model_id' => $user->id,
                'model_type' => 'App\Models\User',
            ]);
            echo "Assigned School Admin role to aboukisiaki@gmail.com\n";
        } else {
            echo "User already has School Admin role\n";
        }
        
        // Ensure school_id is set
        if (!$user->school_id) {
            // Find first school
            $school = DB::table('schools')->first();
            if ($school) {
                DB::table('users')->where('id', $user->id)->update(['school_id' => $school->id]);
                echo "Set school_id to {$school->id}\n";
            }
        }
    }
}
