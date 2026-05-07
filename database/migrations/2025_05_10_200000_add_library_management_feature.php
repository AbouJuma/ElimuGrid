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
        // Check if Library Management feature already exists
        $exists = DB::table('features')->where('name', 'Library Management')->first();

        if (!$exists) {
            // Get the current max ID
            $maxId = DB::table('features')->max('id') ?? 0;

            DB::table('features')->insert([
                'id' => $maxId + 1,
                'name' => 'Library Management',
                'is_default' => 0,
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('features')->where('name', 'Library Management')->delete();
    }
};
