<?php

use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schoolsTable = 'schools';
        $usersTable = 'users';
        try {
            Schema::table('schools', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'schools_admin_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('schools', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('admin_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}


        /* Master Table Started */
        try {
            Schema::table('subjects', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subjects_medium_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('subjects', static function (Blueprint $table) {
                $table->foreign('medium_id')->references('id')->on('mediums')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('classes', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'classes_medium_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'classes_shift_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'classes_stream_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('classes', static function (Blueprint $table) {
                $table->foreign('medium_id')->references('id')->on('mediums')->onDelete('cascade');
                $table->foreign('shift_id')->references('id')->on('shifts')->onUpdate('restrict')->onDelete('cascade');
                $table->foreign('stream_id')->references('id')->on('streams')->onUpdate('restrict')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('class_subjects', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'class_subjects_class_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'class_subjects_subject_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'class_subjects_semester_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('class_subjects', static function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('class_sections', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'class_sections_class_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'class_sections_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'class_sections_medium_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('class_sections', static function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
                $table->foreign('medium_id')->references('id')->on('mediums')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('students', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'students_user_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'students_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'students_guardian_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'students_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('students', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('user_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('guardian_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('staffs', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'staffs_user_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('staffs', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('user_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        /* Master Table End */

        try {
            Schema::table('elective_subject_groups', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'elective_subject_groups_class_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'elective_subject_groups_semester_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('elective_subject_groups', static function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('student_subjects', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'student_subjects_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'student_subjects_class_subject_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'student_subjects_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'student_subjects_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('student_subjects', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('class_subject_id')->references('id')->on('class_subjects')->onDelete('cascade');
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('subject_teachers', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subject_teachers_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subject_teachers_subject_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subject_teachers_teacher_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subject_teachers_class_subject_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('subject_teachers', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->foreign('teacher_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('class_subject_id')->references('id')->on('class_subjects')->onDelete('cascade');
            });
        } catch (\Exception $e) {}


        /* Lesson Module Start */
        try {
            Schema::table('lessons', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'lessons_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'lessons_class_subject_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('lessons', static function (Blueprint $table) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('class_subject_id')->references('id')->on('class_subjects')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        /* Lesson Module End */

        /* Assignment Module Start */
        try {
            Schema::table('assignments', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'assignments_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'assignments_class_subject_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'assignments_session_year_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'assignments_created_by_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'assignments_edited_by_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('assignments', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('class_subject_id')->references('id')->on('class_subjects')->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('edited_by')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('assignment_submissions', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'assignment_submissions_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'assignment_submissions_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('assignment_submissions', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        /* Assignment Module End */

        /* Exam Module Start */
        try {
            Schema::table('exams', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exams_class_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exams_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('exams', static function (Blueprint $table) {
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('exam_timetables', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_timetables_class_subject_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_timetables_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('exam_timetables', static function (Blueprint $table) {
                $table->foreign('class_subject_id')->references('id')->on('class_subjects')->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('exam_marks', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_marks_exam_timetable_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_marks_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_marks_class_subject_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_marks_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('exam_marks', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('exam_timetable_id')->references('id')->on('exam_timetables')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('class_subject_id')->references('id')->on('class_subjects')->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('exam_results', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_results_exam_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_results_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_results_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'exam_results_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('exam_results', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        /*Exam module End*/

        try {
            Schema::table('timetables', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'timetables_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'timetables_subject_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'timetables_semester_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('timetables', static function (Blueprint $table) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->foreign('semester_id')->references('id')->on('semesters')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        /* Announcement Module Start */
        try {
            Schema::table('announcements', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'announcements_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('announcements', static function (Blueprint $table) {
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('announcement_classes', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'announcement_classes_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'announcement_classes_class_subject_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('announcement_classes', static function (Blueprint $table) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('class_subject_id')->references('id')->on('class_subjects')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        /* Announcement Module End */

        try {
            Schema::table('academic_calendars', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'academic_calendars_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('academic_calendars', static function (Blueprint $table) {
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('attendances', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'attendances_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'attendances_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'attendances_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('attendances', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}


        try {
            Schema::table('promote_students', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'promote_students_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'promote_students_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'promote_students_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('promote_students', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        /* Online Exam Module Start */
        try {
            Schema::table('online_exams', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exams_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exams_class_subject_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exams_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('online_exams', static function (Blueprint $table) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('class_subject_id')->references('id')->on('class_subjects')->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('online_exam_questions', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_questions_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_questions_class_subject_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_questions_last_edited_by_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('online_exam_questions', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('class_subject_id')->references('id')->on('class_subjects')->onDelete('cascade');
                $table->foreign('last_edited_by')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('online_exam_question_choices', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_question_choices_online_exam_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_question_choices_question_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('online_exam_question_choices', static function (Blueprint $table) {
                $table->foreign('online_exam_id')->references('id')->on('online_exams')->onDelete('cascade');
                $table->foreign('question_id')->references('id')->on('online_exam_questions')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('student_online_exam_statuses', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'student_online_exam_statuses_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'student_online_exam_statuses_online_exam_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('student_online_exam_statuses', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('online_exam_id')->references('id')->on('online_exams')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('online_exam_student_answers', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_student_answers_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_student_answers_online_exam_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_student_answers_question_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'online_exam_student_answers_option_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('online_exam_student_answers', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('online_exam_id')->references('id')->on('online_exams')->onDelete('cascade');
                $table->foreign('question_id')->references('id')->on('online_exam_question_choices')->onDelete('cascade');
                $table->foreign('option_id')->references('id')->on('online_exam_question_options')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        /* Online Exam Module End */

        /* Form Field Module Start */
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
        /* Form Field Module End */

        try {
            Schema::table('class_teachers', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'class_teachers_class_section_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'class_teachers_teacher_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('class_teachers', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('class_section_id')->references('id')->on('class_sections')->onDelete('cascade');
                $table->foreign('teacher_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}


        /* Fees Module */
        try {
            Schema::table('fees', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'fees_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('fees', static function (Blueprint $table) {
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('payment_transactions', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'payment_transactions_user_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('payment_transactions', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('user_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('fees_paids', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'fees_paids_fees_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'fees_paids_student_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('fees_paids', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('fees_id')->references('id')->on('fees')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('compulsory_fees', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'compulsory_fees_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'compulsory_fees_payment_transaction_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'compulsory_fees_fees_paid_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('compulsory_fees', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('payment_transaction_id')->references('id')->on('payment_transactions')->onDelete('cascade');
                $table->foreign('fees_paid_id')->references('id')->on('fees_paids')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('optional_fees', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'optional_fees_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'optional_fees_class_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'optional_fees_payment_transaction_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'optional_fees_fees_paid_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('optional_fees', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('payment_transaction_id')->references('id')->on('payment_transactions')->onDelete('cascade');
                $table->foreign('fees_paid_id')->references('id')->on('fees_paids')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        /*Fees Module End*/

        /* Subscription Module Start*/
        try {
            Schema::table('subscriptions', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subscriptions_package_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('subscriptions', static function (Blueprint $table) {
                $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('addons', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'addons_feature_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('addons', static function (Blueprint $table) {
                $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        // Schema::table('addon_subscriptions', static function (Blueprint $table) {
        //     $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'addon_subscriptions_feature_id_foreign');
        //     $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');
        // });
        
        try {
            Schema::table('subscription_bills', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subscription_bills_subscription_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subscription_bills_payment_transaction_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('subscription_bills', static function (Blueprint $table) {
                $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
                $table->foreign('payment_transaction_id')->references('id')->on('payment_transactions')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('subscription_features', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subscription_features_subscription_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'subscription_features_feature_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('subscription_features', static function (Blueprint $table) {
                $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
                $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('package_features', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'package_features_package_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'package_features_feature_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('package_features', static function (Blueprint $table) {
                $table->foreign('package_id')->references('id')->on('packages')->onDelete('cascade');
                $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        /* Subscription Module End*/

        /*Expense Module Start*/
        try {
            Schema::table('expenses', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'expenses_category_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'expenses_staff_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'expenses_session_year_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('expenses', static function (Blueprint $table) {
                $table->foreign('category_id')->references('id')->on('expense_categories')->onDelete('cascade');
                $table->foreign('staff_id')->references('id')->on('staffs')->onDelete('cascade');
                $table->foreign('session_year_id')->references('id')->on('session_years')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        /*Expense Module End*/

        try {
            Schema::table('leaves', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'leaves_user_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('leaves', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('user_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('staff_support_schools', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'staff_support_schools_user_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('staff_support_schools', static function (Blueprint $table) use ($usersTable) {
                $table->foreign('user_id')->references('id')->on($usersTable)->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('fees', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'fees_school_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'fees_class_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('fees', static function (Blueprint $table) use ($schoolsTable) {
                $table->foreign('school_id')->references('id')->on($schoolsTable)->onDelete('cascade');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('compulsory_fees', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'compulsory_fees_installment_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'compulsory_fees_school_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'compulsory_fees_student_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'compulsory_fees_payment_transaction_id_foreign'); } catch (\Exception $e) {}
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'compulsory_fees_fees_paid_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('compulsory_fees', static function (Blueprint $table) use ($schoolsTable, $usersTable) {
                $table->foreign('installment_id')->references('id')->on('fees_installments')->onDelete('cascade');
                $table->foreign('school_id')->references('id')->on($schoolsTable)->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreign('payment_transaction_id')->references('id')->on('payment_transactions')->onDelete('cascade');
                $table->foreign('fees_paid_id')->references('id')->on('fees_paids')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('optional_fees', static function (Blueprint $table) {
                try { $table->dropForeign(\Illuminate\Support\Facades\DB::connection()->getTablePrefix() . 'optional_fees_fees_class_id_foreign'); } catch (\Exception $e) {}
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('optional_fees', static function (Blueprint $table) {
                $table->foreign('fees_class_id')->references('id')->on('fees_class_types')->onDelete('cascade');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('payment_configurations', static function (Blueprint $table) {
                $table->string('bank_name')->nullable(true)->after('webhook_secret_key');
                $table->string('account_name')->nullable(true)->after('bank_name');
                $table->string('account_no')->nullable(true)->after('account_name');
            });
        } catch (\Exception $e) {}

        try {
            Schema::create('chats', static function (Blueprint $table) use ($usersTable) {
                $table->id();
                $table->foreignId('sender_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->foreignId('receiver_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->timestamps();
            });
        } catch (\Exception $e) {}

        try {
            Schema::create('messages', static function (Blueprint $table) use ($usersTable) {
                $table->id();
                $table->foreignId('chat_id')->references('id')->on('chats')->onDelete('cascade');
                $table->foreignId('sender_id')->references('id')->on($usersTable)->onDelete('cascade');
                $table->text('message')->nullable(true);
                $table->timestamp('read_at')->nullable(true);
                $table->timestamps();
            });
        } catch (\Exception $e) {}

        try {
            Schema::create('attachments', static function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->references('id')->on('messages')->onDelete('cascade');
                $table->string('file')->nullable(true);
                $table->string('file_type')->nullable(true);
                $table->timestamps();
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('packages', static function (Blueprint $table) {
                $table->float('student_charge', 8, 2)->change();
                $table->float('staff_charge', 8, 2)->change();
                $table->float('charges', 8, 2)->change();
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('students', static function (Blueprint $table) use ($usersTable) {
                $table->string('application_type')->nullable()->after('class_section_id')->default('offline');
                $table->integer('application_status')->nullable()->after('school_id')->comment('1- accepted, 0- rejected')->default('1');
                $table->foreignId('class_id')->nullable()->after('user_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreignId('class_section_id')->nullable()->change();
            });
        } catch (\Exception $e) {}

        try {
            Schema::create('database_backups', static function (Blueprint $table) use ($schoolsTable) {
                $table->id();
                $table->string('name')->nullable(true);
                $table->foreignId('school_id')->references('id')->on($schoolsTable)->onDelete('cascade');
                $table->timestamps();
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('features', static function (Blueprint $table) {
                $table->integer('required_vps')->after('status')->default(0);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('schools', static function (Blueprint $table) {
                $table->string('database_name')->nullable(true)->after('domain');
                $table->string('code')->nullable(true)->after('database_name');
            });
        } catch (\Exception $e) {}

        try {
            $schools = DB::table('schools')->get();

            foreach ($schools as $key => $school) {
                DB::table('schools')->where('id', $school->id)->update(['code' => "SCH" . date('Y') . $school->id]);
            }
        } catch (\Exception $e) {}
      
        try {
            Schema::table('staffs', static function (Blueprint $table) {
                $table->date('joining_date')->nullable(true)->after('salary');
            });
        } catch (\Exception $e) {}

        try {
            // Old school admin email verification
            $schoolAdmins = User::role('School Admin')->withTrashed()->get();
            foreach ($schoolAdmins as $key => $admin) {
                $admin->email_verified_at = Carbon::now();
                $admin->save();
            }    
        } catch (\Throwable $th) {
            
        }
        Cache::flush();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schoolsTable = 'schools';
        $usersTable = 'users';
        //
        Schema::table('payment_configurations', static function (Blueprint $table) {
            $table->dropColumn('bank_name');
            $table->dropColumn('account_name');
            $table->dropColumn('account_no');
        });

        Schema::dropIfExists('chats');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('attachments');

        Schema::table('students', static function (Blueprint $table) {
            $table->dropForeign(['class_id']);
            $table->dropColumn('class_id');
            $table->dropColumn('application_type');
            $table->dropColumn('application_status');
            $table->foreignId('class_section_id')->nullable(false)->change();
        });

        Schema::dropIfExists('database_backups');
        Schema::table('features', static function (Blueprint $table) {
            $table->dropColumn('required_vps');
        });

        Schema::table('schools', static function (Blueprint $table) {
            $table->dropColumn('database_name');
            $table->dropColumn('code');
        });
        Schema::table('staffs', static function (Blueprint $table) {
            $table->dropColumn('joining_date');
        });

    }
};
