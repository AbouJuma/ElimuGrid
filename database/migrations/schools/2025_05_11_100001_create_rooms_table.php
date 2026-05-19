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
            if (!Schema::hasTable('rooms')) {
                Schema::create('rooms', function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('hostel_id');
                    $table->string('room_number');
                    $table->integer('capacity')->default(1);
                    $table->integer('occupied_beds')->default(0);
                    $table->unsignedBigInteger('school_id');
                    $table->timestamps();
                    $table->softDeletes();

                    $table->foreign('hostel_id')->references('id')->on('hostels')->onDelete('cascade');
                    $table->index('school_id');
                    $table->index('hostel_id');
                    $table->index('room_number');
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
        Schema::dropIfExists('rooms');
    }
};
