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
        // Check if Virtual Classroom feature already exists
        $exists = DB::table('features')->where('name', 'Virtual Classroom')->first();

        if (!$exists) {
            // Get the current max ID
            $maxId = DB::table('features')->max('id') ?? 0;

            DB::table('features')->insert([
                'id' => $maxId + 1,
                'name' => 'Virtual Classroom',
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
        DB::table('features')->where('name', 'Virtual Classroom')->delete();
    }
};
