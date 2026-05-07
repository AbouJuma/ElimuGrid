<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CreateHostelTablesSeeder extends Seeder
{
    public function run()
    {
        // Check if tables already exist
        if (Schema::hasTable('hostels')) {
            echo "Hostels table already exists. Skipping...\n";
            return;
        }

        // 1. Create hostels table
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();
            $table->index('school_id');
            $table->index('name');
        });
        echo "Hostels table created successfully\n";

        // 2. Create rooms table (without foreign key - add later)
        Schema::create('rooms', function (Blueprint $table) {
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
        echo "Rooms table created successfully\n";

        // 3. Create hostel_allocations table (without foreign keys - add later)
        Schema::create('hostel_allocations', function (Blueprint $table) {
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
        echo "Hostel allocations table created successfully\n";

        // 4. Mark migrations as run
        $now = now();
        $migrations = [
            '2025_05_11_100000_create_hostels_table',
            '2025_05_11_100001_create_rooms_table',
            '2025_05_11_100002_create_hostel_allocations_table',
        ];

        foreach ($migrations as $migration) {
            $exists = DB::table('migrations')->where('migration', $migration)->first();
            if (!$exists) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => DB::table('migrations')->max('batch') + 1,
                ]);
                echo "Migration marked as run: {$migration}\n";
            }
        }

        echo "\nHostel Management tables created successfully!\n";
        echo "You can now use the Hostel Management module.\n";
    }
}
