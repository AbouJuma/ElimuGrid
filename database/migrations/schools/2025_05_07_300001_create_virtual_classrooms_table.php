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
            if (!Schema::hasTable('virtual_classrooms')) {
                Schema::create('virtual_classrooms', function (Blueprint $table) {
                    $table->id();
                    $table->string('title');
                    $table->text('description')->nullable();
                    $table->unsignedBigInteger('class_id');
                    $table->unsignedBigInteger('section_id')->nullable();
                    $table->unsignedBigInteger('subject_id');
                    $table->unsignedBigInteger('teacher_id');
                    $table->string('room_name')->unique();
                    $table->string('meeting_url')->nullable();
                    $table->dateTime('start_time');
                    $table->dateTime('end_time');
                    $table->enum('status', ['scheduled', 'live', 'completed', 'cancelled'])->default('scheduled');
                    $table->unsignedBigInteger('created_by');
                    $table->unsignedBigInteger('school_id');
                    $table->timestamps();
                    $table->softDeletes();

                    // Indexes for performance
                    $table->index('school_id');
                    $table->index('class_id');
                    $table->index('section_id');
                    $table->index('teacher_id');
                    $table->index('status');
                    $table->index('start_time');
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
        Schema::dropIfExists('virtual_classrooms');
    }
};
