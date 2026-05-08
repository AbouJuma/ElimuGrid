<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
            
            // Indexes
            $table->index('school_id');
            $table->index('class_id');
            $table->index('section_id');
            $table->index('teacher_id');
            $table->index('status');
        });
        
        Schema::create('virtual_classroom_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('virtual_classroom_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('virtual_classroom_id')->references('id')->on('virtual_classrooms')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            
            // Indexes
            $table->index(['virtual_classroom_id', 'student_id']);
            $table->index('virtual_classroom_id');
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virtual_classrooms');
        Schema::dropIfExists('virtual_classroom_attendance');
    }
};
