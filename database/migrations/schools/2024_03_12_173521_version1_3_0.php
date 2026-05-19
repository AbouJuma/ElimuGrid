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
        $usersTable = 'users';
        try {
            Schema::table('packages', static function (Blueprint $table) {
                $table->integer('type')->default(1)->comment('0 => Prepaid, 1 => Postpaid')->after('days');
                $table->integer('no_of_students')->default(0)->after('type');
                $table->integer('no_of_staffs')->default(0)->after('no_of_students');
                $table->double('charges', 64, 4)->default(0)->after('no_of_staffs');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('subscriptions', static function (Blueprint $table) {
                $table->integer('package_type')->default(1)->comment('0 => Prepaid, 1 => Postpaid')->after('end_date');
                $table->integer('no_of_students')->default(0)->after('package_type');
                $table->integer('no_of_staffs')->default(0)->after('no_of_students');
                $table->double('charges', 64, 4)->default(0)->after('no_of_staffs');
            });
        } catch (\Exception $e) {}

        Schema::dropIfExists('subscription_bill_payments');

        try {
            Schema::table('addon_subscriptions', static function (Blueprint $table) {
                $table->foreignId('payment_transaction_id')->nullable(true)->after('status')->references('id')->on('payment_transactions')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('payment_transactions', static function (Blueprint $table) {
                $table->double('amount', 64, 2)->change();
            });
        } catch (\Exception $e) {}

        // Exam Result Status
        try {
            Schema::table('exam_results', static function (Blueprint $table) {
                $table->integer('status')->default(1)->comment('0 -> Failed, 1 -> Pass')->after('grade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('users', static function (Blueprint $table) {
                $table->string('language')->default('en')->after('school_id');
            });
        } catch (\Exception $e) {}

        Cache::flush();


        // Permanent delete option for students
        try {
            Schema::table('students', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'students_user_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('students', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('user_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('students', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'students_class_section_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('students', static function (Blueprint $table) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('students', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'students_guardian_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('students', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('guardian_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('students', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'students_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('students', static function (Blueprint $table) {
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('student_subjects', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'student_subjects_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('student_subjects', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('assignment_submissions', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'assignment_submissions_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('assignment_submissions', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('exam_marks', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_marks_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('exam_marks', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('exam_results', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_results_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('exam_results', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('attendances', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'attendances_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('attendances', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}


        try {
            Schema::table('promote_students', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'promote_students_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('promote_students', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('student_online_exam_statuses', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'student_online_exam_statuses_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('student_online_exam_statuses', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('online_exam_student_answers', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_student_answers_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('online_exam_student_answers', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('extra_student_datas', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'extra_student_datas_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('extra_student_datas', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('fees_paids', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'fees_paids_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('fees_paids', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('compulsory_fees', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'compulsory_fees_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('compulsory_fees', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('optional_fees', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'optional_fees_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('optional_fees', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        // End permanent delete option for students

        try {
            Schema::table('notifications', static function (Blueprint $table) {
                $table->string('send_to')->change();
            });
        } catch (\Exception $e) {}



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $usersTable = 'users';
        //
        Schema::table('packages', static function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('no_of_students');
            $table->dropColumn('no_of_staffs');
            $table->dropColumn('charges');
        });

        Schema::table('subscriptions', static function (Blueprint $table) {
            $table->dropColumn('package_type');
            $table->dropColumn('no_of_students');
            $table->dropColumn('no_of_staffs');
            $table->dropColumn('charges');
        });

        Schema::table('addon_subscriptions', static function (Blueprint $table) {
            $table->dropForeign(['payment_transaction_id']);
            $table->dropColumn('payment_transaction_id');
        });

        Schema::table('exam_results', static function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('language');
        });

    }
};
