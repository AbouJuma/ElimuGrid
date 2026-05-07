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

            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('class_schools')->onDelete('cascade');
            $table->foreign('hostel_id')->references('id')->on('hostels')->onDelete('cascade');
            $table->foreign('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->index('school_id');
            $table->index('student_id');
            $table->index('status');
            $table->index(['allocation_date', 'checkout_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hostel_allocations');
    }
};
