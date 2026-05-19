<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            if (!Schema::hasTable('virtual_classroom_attendance')) {
                Schema::create('virtual_classroom_attendance', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('virtual_classroom_id');
                    $table->unsignedBigInteger('student_id');
                    $table->dateTime('joined_at')->nullable();
                    $table->dateTime('left_at')->nullable();
                    $table->integer('duration')->default(0); // in minutes
                    $table->unsignedBigInteger('school_id');
                    $table->timestamps();

                    // Indexes for performance
                    $table->index('school_id');
                    $table->index('virtual_classroom_id');
                    $table->index('student_id');
                    $table->index(['virtual_classroom_id', 'student_id'], 'vca_vc_student_idx');

                    // Foreign keys
                    $table->foreign('virtual_classroom_id')
                        ->references('id')
                        ->on('virtual_classrooms')
                        ->onDelete('cascade');
                });
            }
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('virtual_classroom_attendance');
    }
};
