<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // Drop all tables that are not needed
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try { Schema::dropIfExists('academic_calendars'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('categories'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('addons'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('addon_subscriptions'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('database_backups'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('features'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('feature_sections'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('feature_section_lists'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('guidances'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('languages'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('packages'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('package_features'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('staff_support_schools'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('subscriptions'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('subscription_bills'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('subscription_features'); } catch (\Exception $e) {}
        try { Schema::dropIfExists('system_settings'); } catch (\Exception $e) {}
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        // Add class_subject_id to lesson_commons, assignment_commons, online_exam_commons, online_exam_question_commons, topic_commons
        try {
            Schema::table('lesson_commons', function (Blueprint $table) {
                $table->foreignId('class_subject_id')->after('class_section_id')->references('id')->on('class_subjects')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('assignment_commons', function (Blueprint $table) {
                $table->foreignId('class_subject_id')->after('class_section_id')->references('id')->on('class_subjects')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('online_exam_commons', function (Blueprint $table) {
                $table->foreignId('class_subject_id')->after('class_section_id')->references('id')->on('class_subjects')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('online_exam_question_commons', function (Blueprint $table) {
                $table->foreignId('class_subject_id')->after('class_section_id')->references('id')->on('class_subjects')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('topic_commons', function (Blueprint $table) {
                $table->foreignId('class_subject_id')->after('class_section_id')->references('id')->on('class_subjects')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        // Update compulsory_fees, fees, fees_installments, fees_class_types, optional_fees, fees_paids, fees_advance, staffs, expenses, staff_payrolls, payment_transactions, addons, subscription_bills, fees_paids
        try {
            Schema::table('compulsory_fees', function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
                $table->double('due_charges', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('fees', function (Blueprint $table) {
                $table->double('due_charges', 64, 2)->change();
                $table->double('due_charges_amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('fees_installments', function (Blueprint $table) {
                $table->double('due_charges', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('fees_class_types', function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('optional_fees', function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('fees_paids', function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('fees_advance', function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        // Staff salary
        try {
            Schema::table('staffs', function (Blueprint $table) {
                $table->double('salary', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        // Expenses
        try {
            Schema::table('expenses', function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        // Payroll tables
        try {
            Schema::table('staff_payrolls', function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}
        
        try {
            Schema::table('payment_transactions', function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('fees_paids', function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}

        // Clear the cache to ensure changes are reflected
        Cache::flush();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove class_subject_id from lesson_commons, assignment_commons, online_exam_commons, online_exam_question_commons, topic_commons
        Schema::table('lesson_commons', function (Blueprint $table) {
            $table->dropColumn('class_subject_id');
        });

        Schema::table('assignment_commons', function (Blueprint $table) {
            $table->dropColumn('class_subject_id');
        });

        Schema::table('online_exam_commons', function (Blueprint $table) {
            $table->dropColumn('class_subject_id');
        });

        Schema::table('online_exam_question_commons', function (Blueprint $table) {
            $table->dropColumn('class_subject_id');
        });

        Schema::table('topic_commons', function (Blueprint $table) {
            $table->dropColumn('class_subject_id');
        });
    }
};
