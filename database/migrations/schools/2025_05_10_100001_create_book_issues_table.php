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
        Schema::create('book_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_id');
            $table->date('issue_date');
            $table->date('return_date'); // Expected return date
            $table->date('actual_return_date')->nullable(); // Actual return date
            $table->integer('late_days')->default(0);
            $table->decimal('fine_amount', 10, 2)->default(0.00);
            $table->enum('status', ['borrowed', 'returned', 'overdue'])->default('borrowed');
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('class_schools')->onDelete('cascade');

            // Indexes
            $table->index('school_id');
            $table->index('book_id');
            $table->index('student_id');
            $table->index('class_id');
            $table->index('status');
            $table->index(['issue_date', 'return_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('book_issues');
    }
};
