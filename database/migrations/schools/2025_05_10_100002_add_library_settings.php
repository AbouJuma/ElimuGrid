<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add library fine per day setting
        $settings = [
            [
                'name' => 'library_fine_per_day',
                'data' => '10',
                'type' => 'integer',
                'school_id' => null
            ],
            [
                'name' => 'library_max_borrow_days',
                'data' => '14',
                'type' => 'integer',
                'school_id' => null
            ],
            [
                'name' => 'library_max_books_per_student',
                'data' => '3',
                'type' => 'integer',
                'school_id' => null
            ]
        ];

        foreach ($settings as $setting) {
            DB::table('school_settings')->insert($setting);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('school_settings')
            ->whereIn('name', [
                'library_fine_per_day',
                'library_max_borrow_days',
                'library_max_books_per_student'
            ])
            ->delete();
    }
};
