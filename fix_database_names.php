<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schools = DB::table('schools')->get();
foreach ($schools as $school) {
    // Determine the correct prefix by checking if the users table exists
    $s_prefix = 's' . $school->id . '_';
    $school_prefix = 'school_' . $school->id . '_';
    
    $correct_prefix = null;
    
    try {
        if (DB::select("SHOW TABLES LIKE '{$s_prefix}users'")) {
            $correct_prefix = $s_prefix;
        } elseif (DB::select("SHOW TABLES LIKE '{$school_prefix}users'")) {
            $correct_prefix = $school_prefix;
        }
    } catch (\Exception $e) {
        // Table doesn't exist
    }
    
    if ($correct_prefix && $school->database_name !== $correct_prefix) {
        echo "Updating school {$school->id} database_name from {$school->database_name} to {$correct_prefix}\n";
        DB::table('schools')->where('id', $school->id)->update(['database_name' => $correct_prefix]);
    } elseif (!$correct_prefix) {
        echo "Warning: No user table found for school {$school->id} (checked {$s_prefix}users and {$school_prefix}users)\n";
        // Let's set it to s_prefix by default so that new schools don't break
        if (!preg_match('/^s\d+_$/', $school->database_name) && !preg_match('/^school_\d+_$/', $school->database_name)) {
             echo "Setting default prefix {$s_prefix} for school {$school->id}\n";
             DB::table('schools')->where('id', $school->id)->update(['database_name' => $s_prefix]);
        }
    } else {
        echo "School {$school->id} already has correct database_name: {$school->database_name}\n";
    }
}
