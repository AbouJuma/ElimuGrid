<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== PRODUCTION DATABASE FIX ===\n\n";

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    // Check current database configuration
    echo "Current database: " . DB::getDatabaseName() . "\n";
    
    // Check if we're using the right database
    $expectedDatabase = 'eschool_saas_3_baobab';
    $currentDatabase = DB::getDatabaseName();
    
    if ($currentDatabase !== $expectedDatabase) {
        echo "Switching to school database: {$expectedDatabase}\n";
        
        // Update database configuration
        config(['database.connections.mysql.database' => $expectedDatabase]);
        DB::purge('mysql');
        
        echo "Connected to: " . DB::getDatabaseName() . "\n";
    }
    
    // Check if system_settings exists
    if (!Schema::hasTable('system_settings')) {
        echo "Creating system_settings table...\n";
        
        Schema::create('system_settings', function ($table) {
            $table->id();
            $table->string('type')->unique();
            $table->text('data')->nullable();
            $table->bigInteger('school_id')->nullable();
            $table->timestamps();
        });
        
        echo "✅ system_settings table created\n";
    }
    
    // Insert basic system settings if they don't exist
    $existingSettings = DB::table('system_settings')->count();
    if ($existingSettings == 0) {
        echo "Inserting basic system settings...\n";
        
        DB::table('system_settings')->insert([
            ['type' => 'system_name', 'data' => 'eSchool Management System', 'school_id' => null],
            ['type' => 'school_timezone', 'data' => 'UTC', 'school_id' => null],
            ['type' => 'currency_symbol', 'data' => '$', 'school_id' => null],
            ['type' => 'date_format', 'data' => 'Y-m-d', 'school_id' => null],
            ['type' => 'time_format', 'data' => 'H:i:s', 'school_id' => null],
        ]);
        
        echo "✅ Basic system settings inserted\n";
    }
    
    // Check Virtual Classroom tables
    echo "\nChecking Virtual Classroom tables...\n";
    
    $requiredTables = ['virtual_classrooms', 'virtual_classroom_attendance'];
    
    foreach ($requiredTables as $tableName) {
        if (!Schema::hasTable($tableName)) {
            echo "Creating {$tableName} table...\n";
            
            if ($tableName === 'virtual_classrooms') {
                Schema::create($tableName, function ($table) {
                    $table->id();
                    $table->string('title');
                    $table->text('description')->nullable();
                    $table->bigInteger('class_id');
                    $table->bigInteger('section_id')->nullable();
                    $table->bigInteger('subject_id');
                    $table->bigInteger('teacher_id');
                    $table->bigInteger('school_id');
                    $table->string('meeting_url')->nullable();
                    $table->string('meeting_id')->nullable();
                    $table->string('password')->nullable();
                    $table->enum('status', ['scheduled', 'live', 'completed', 'cancelled'])->default('scheduled');
                    $table->dateTime('start_time');
                    $table->dateTime('end_time');
                    $table->timestamps();
                    $table->softDeletes();
                    
                    $table->foreign('class_id')->references('id')->on('classes');
                    $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
                    $table->foreign('subject_id')->references('id')->on('subjects');
                    $table->foreign('teacher_id')->references('id')->on('users');
                });
            } elseif ($tableName === 'virtual_classroom_attendance') {
                Schema::create($tableName, function ($table) {
                    $table->id();
                    $table->bigInteger('virtual_classroom_id');
                    $table->bigInteger('student_id');
                    $table->bigInteger('school_id');
                    $table->dateTime('join_time')->nullable();
                    $table->dateTime('leave_time')->nullable();
                    $table->integer('duration_minutes')->default(0);
                    $table->enum('status', ['present', 'absent', 'late'])->default('present');
                    $table->text('notes')->nullable();
                    $table->timestamps();
                    
                    $table->foreign('virtual_classroom_id')->references('id')->on('virtual_classrooms')->onDelete('cascade');
                    $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
                });
            }
            
            echo "✅ {$tableName} table created\n";
        } else {
            echo "✅ {$tableName} table exists\n";
        }
    }
    
    echo "\n✅ Database setup complete!\n";
    echo "Virtual Classroom should now work properly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIX COMPLETE ===\n";
