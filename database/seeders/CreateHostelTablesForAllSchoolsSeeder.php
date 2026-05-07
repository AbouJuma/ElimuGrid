<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CreateHostelTablesForAllSchoolsSeeder extends Seeder
{
    public function run()
    {
        echo "=== Creating Hostel Tables for All Schools ===\n\n";
        
        // Get all schools from main database
        $schools = DB::connection('mysql')->table('schools')->get();
        
        echo "Found " . $schools->count() . " schools\n\n";
        
        foreach ($schools as $school) {
            if (!$school->database_name) {
                echo "Skipping school {$school->name} - no database_name\n";
                continue;
            }
            
            echo "Processing school: {$school->name} (DB: {$school->database_name})\n";
            
            try {
                // Switch to school database
                Config::set('database.connections.school.database', $school->database_name);
                DB::purge('school');
                DB::connection('school')->reconnect();
                
                // Check connection
                DB::connection('school')->getPdo();
                
                // 1. Create hostels table
                if (!Schema::connection('school')->hasTable('hostels')) {
                    Schema::connection('school')->create('hostels', function (Blueprint $table) {
                        $table->id();
                        $table->string('name');
                        $table->text('description')->nullable();
                        $table->unsignedBigInteger('school_id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->index('school_id');
                        $table->index('name');
                    });
                    echo "  ✓ Created hostels table\n";
                } else {
                    echo "  ℹ hostels table already exists\n";
                }
                
                // 2. Create rooms table
                if (!Schema::connection('school')->hasTable('rooms')) {
                    Schema::connection('school')->create('rooms', function (Blueprint $table) {
                        $table->id();
                        $table->unsignedBigInteger('hostel_id');
                        $table->string('room_number');
                        $table->integer('capacity')->default(1);
                        $table->integer('occupied_beds')->default(0);
                        $table->unsignedBigInteger('school_id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->index('school_id');
                        $table->index('hostel_id');
                        $table->index('room_number');
                    });
                    echo "  ✓ Created rooms table\n";
                } else {
                    echo "  ℹ rooms table already exists\n";
                }
                
                // 3. Create hostel_allocations table
                if (!Schema::connection('school')->hasTable('hostel_allocations')) {
                    Schema::connection('school')->create('hostel_allocations', function (Blueprint $table) {
                        $table->id();
                        $table->unsignedBigInteger('student_id');
                        $table->unsignedBigInteger('class_id');
                        $table->unsignedBigInteger('hostel_id');
                        $table->unsignedBigInteger('room_id');
                        $table->string('bed_number')->nullable();
                        $table->date('allocation_date');
                        $table->date('checkout_date')->nullable();
                        $table->enum('status', ['active', 'checked_out'])->default('active');
                        $table->unsignedBigInteger('school_id');
                        $table->timestamps();
                        $table->softDeletes();
                        $table->index('school_id');
                        $table->index('student_id');
                        $table->index('status');
                        $table->index(['allocation_date', 'checkout_date']);
                    });
                    echo "  ✓ Created hostel_allocations table\n";
                } else {
                    echo "  ℹ hostel_allocations table already exists\n";
                }
                
            } catch (\Exception $e) {
                echo "  ✗ ERROR: " . $e->getMessage() . "\n";
            }
        }
        
        // Reset to main database
        DB::setDefaultConnection('mysql');
        Config::set('database.connections.school.database', '');
        DB::purge('school');
        DB::connection('mysql')->reconnect();
        
        echo "\n=== Done! ===\n";
        echo "Hostel tables created for all schools.\n";
    }
}
