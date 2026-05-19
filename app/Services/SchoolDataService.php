<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\SessionYear;
use App\Models\User;
use Artisan as GlobalArtisan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Session;
use App\Services\SharedHostingTenantService;
use Illuminate\Support\Facades\Schema;

class SchoolDataService {

    public function preSettingsSetup($schoolData) {
        set_time_limit(300);

        // Always work from central (unprefixed) connection first
        SharedHostingTenantService::switchToMain();

        $prefixName = SharedHostingTenantService::tenantTablePrefix($schoolData->id);
        DB::connection('mysql')->table('schools')->where('id', $schoolData->id)->update(['database_name' => $prefixName]);
        $schoolData->database_name = $prefixName;

        $mainUser = DB::connection('mysql')->table('users')->where('id', $schoolData->admin_id)->first();
        if (!$mainUser) {
            \Log::warning("School admin user not found for tenant provisioning. Proceeding with basic setup. School ID: {$schoolData->id}");
        }

        SharedHostingTenantService::createTenantTables($schoolData->id);

        SharedHostingTenantService::switchToTenant($schoolData->id);

        if (!Schema::hasTable('schools')) {
            SharedHostingTenantService::createBasicTenantTables($schoolData->id);
        }

        $schoolRow = [
            'id' => $schoolData->id,
            'name' => $schoolData->name,
            'address' => $schoolData->address,
            'support_phone' => $schoolData->support_phone,
            'support_email' => $schoolData->support_email,
            'tagline' => $schoolData->tagline,
            'logo' => $schoolData->logo,
            'status' => $schoolData->type == "demo" ? 1 : ($schoolData->status ?? 0),
            'domain' => $schoolData->domain,
            'database_name' => $prefixName,
            'code' => $schoolData->code,
            'domain_type' => $schoolData->domain_type ?? 'default',
            'type' => $schoolData->type,
            'admin_id' => $schoolData->admin_id,
            'created_at' => $schoolData->created_at,
            'updated_at' => $schoolData->updated_at,
        ];

        // Tenant FK: schools.admin_id -> users.id and users.school_id -> schools.id.
        // Insert school first with admin_id null, then tenant user, then link admin_id.
        $schoolRowInsert = $schoolRow;
        $schoolRowInsert['admin_id'] = null;

        if (!DB::table($prefixName . 'schools')->where('id', $schoolData->id)->exists()) {
            DB::table($prefixName . 'schools')->insert($this->filterRowForTable($schoolRowInsert, 'schools'));
        } else {
            $this->syncTenantSchoolRowFromCentral($schoolData->id, $schoolRow, $prefixName);
        }

        if ($mainUser) {
            $userInsert = [
                'id' => $mainUser->id,
                'first_name' => $mainUser->first_name,
                'last_name' => $mainUser->last_name,
                'mobile' => $mainUser->mobile,
                'email' => $mainUser->email,
                'password' => $mainUser->password,
                'remember_token' => $mainUser->remember_token,
                'email_verified_at' => $schoolData->type == "demo" ? Carbon::now() : ($mainUser->email_verified_at ?? null),
                'created_at' => $mainUser->created_at,
                'updated_at' => $mainUser->updated_at,
                'school_id' => $schoolData->id,
            ];
            if (!DB::table($prefixName . 'users')->where('id', $schoolData->admin_id)->exists()) {
                if (Schema::hasColumn($prefixName . 'users', 'two_factor_enabled')) {
                    $userInsert['two_factor_enabled'] = $mainUser->two_factor_enabled ?? 0;
                }
                DB::table($prefixName . 'users')->insert($this->filterRowForTable($userInsert, 'users'));
            }

            $this->ensureTenantSchoolAdminLinked((int) $schoolData->id, (int) $schoolData->admin_id, $prefixName);

            $school = School::find($schoolData->id);
            if ($school) {
                $school->admin_id = $schoolData->admin_id;
                $school->save();
            }
        }



        $this->createPreSetupRole($schoolData);
        $sessionYear = SessionYear::updateOrCreate([
            'name'      => Carbon::now()->format('Y'),
            'school_id' => $schoolData->id
        ],
            ['default'    => 1,
             'start_date' => Carbon::now()->startOfYear()->format('Y-m-d'),
             'end_date'   => Carbon::now()->endOfYear()->format('Y-m-d'),
            ]);
        // Add School Setting Data
        $schoolSettingData = array(
            [
                'name'      => 'school_name',
                'data'      => $schoolData->name,
                'type'      => 'string',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'school_email',
                'data'      => $schoolData->support_email,
                'type'      => 'string',
                'school_id' => $schoolData->id
            ],
            [
                'name'      => 'school_phone',
                'data'      => $schoolData->support_phone,
                'type'      => 'number',
                'school_id' => $schoolData->id
            ],
            [
                'name'      => 'school_tagline',
                'data'      => $schoolData->tagline,
                'type'      => 'string',
                'school_id' => $schoolData->id
            ],
            [
                'name'      => 'school_address',
                'data'      => $schoolData->address,
                'type'      => 'string',
                'school_id' => $schoolData->id
            ],
            [
                'name'      => 'session_year',
                'data'      => $sessionYear->id,
                'type'      => 'number',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'horizontal_logo',
                'data'      => '',
                'type'      => 'file',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'vertical_logo',
                'data'      => '',
                'type'      => 'file',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'timetable_start_time',
                'data'      => '09:00:00',
                'type'      => 'time',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'timetable_end_time',
                'data'      => '18:00:00',
                'type'      => 'time',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'timetable_duration',
                'data'      => '01:00:00',
                'type'      => 'time',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'auto_renewal_plan',
                'data'      => '1',
                'type'      => 'integer',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'currency_code',
                'data'      => 'INR',
                'type'      => 'string',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'currency_symbol',
                'data'      => '₹',
                'type'      => 'string',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'date_format',
                'data'      => 'd-m-Y',
                'type'      => 'string',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'time_format',
                'data'      => 'h:i A',
                'type'      => 'string',
                'school_id' => $schoolData->id,
            ],
            [
                'name'      => 'domain',
                'data'      => $schoolData->domain ?? '',
                'type'      => 'string',
                'school_id' => $schoolData->id,
            ],

            [
                'name' => 'email-template-staff',
                'data' => '&lt;p&gt;Dear {full_name},&lt;/p&gt; &lt;p&gt;Welcome to {school_name}!&lt;/p&gt; &lt;p&gt;We are excited to have you join our team. Below are your registration details to access the {school_name}:&lt;/p&gt; &lt;hr&gt; &lt;p&gt;&lt;strong&gt;Your Registration Details:&lt;/strong&gt;&lt;/p&gt; &lt;ul&gt; &lt;li&gt;&lt;strong&gt;Registration URL:&lt;/strong&gt; {url}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;Code:&lt;/strong&gt; {code}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;Email:&lt;/strong&gt; {email}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;Password:&lt;/strong&gt; {password}&lt;/li&gt; &lt;/ul&gt; &lt;hr&gt; &lt;p&gt;&lt;strong&gt;Steps to Complete Your Registration:&lt;/strong&gt;&lt;/p&gt; &lt;ol&gt; &lt;li&gt;Click on the registration URL provided above.&lt;/li&gt; &lt;li&gt;Enter your email and password.&lt;/li&gt; &lt;li&gt;Follow the on-screen instructions to set up your profile.&lt;/li&gt; &lt;/ol&gt; &lt;p&gt;&lt;strong&gt;Important:&lt;/strong&gt;&lt;/p&gt; &lt;ul&gt; &lt;li&gt;For security reasons, please change your password upon your first login.&lt;/li&gt; &lt;li&gt;If you have any questions or need assistance during the registration process, please contact our support team at {support_email} or call {support_contact}.&lt;/li&gt; &lt;/ul&gt; &lt;p&gt;&lt;strong&gt;App Download Links:&lt;/strong&gt;&lt;/p&gt; &lt;ul&gt; &lt;li&gt;&lt;strong&gt;Android:&lt;/strong&gt; {android_app}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;iOS:&lt;/strong&gt; {ios_app}&lt;/li&gt; &lt;/ul&gt; &lt;p&gt;We look forward to a successful academic year with you on our team. Thank you for your commitment to excellence in education.&lt;/p&gt; &lt;p&gt;Best regards,&lt;/p&gt; &lt;p&gt;{school_name}&lt;br&gt;{support_email}&lt;br&gt;{support_contact}&lt;br&gt;{url}&lt;/p&gt;',
                'type' => 'text',
                'school_id' => $schoolData->id
            ],
            [
                'name' => 'email-template-parent',
                'data' => '&lt;p&gt;Dear {parent_name},&lt;/p&gt; &lt;p&gt;We are delighted to welcome {child_name} to {school_name}!&lt;/p&gt; &lt;p&gt;As part of our registration process, we have created accounts for both you and your child in our {school_name}. Below are the registration details you will need to access the system, along with links to download our mobile app for your convenience.&lt;/p&gt; &lt;hr&gt; &lt;p&gt;&lt;strong&gt;Student Credential Details:&lt;/strong&gt;&lt;/p&gt; &lt;ul&gt; &lt;li&gt;&lt;strong&gt;Name:&lt;/strong&gt; {child_name}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;Admission No.: &lt;/strong&gt;{admission_no}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;Code:&lt;/strong&gt; {code}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;GR No.:&lt;/strong&gt; {grno}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;Password:&lt;/strong&gt; {child_password}&lt;/li&gt; &lt;/ul&gt; &lt;hr&gt; &lt;p&gt;&lt;strong&gt;Parent Credential Details:&lt;/strong&gt;&lt;/p&gt; &lt;ul&gt; &lt;li&gt;&lt;strong&gt;Name:&lt;/strong&gt; {parent_name}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;Code:&lt;/strong&gt; {code}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;Email:&lt;/strong&gt; {email}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;Password:&lt;/strong&gt; {password}&lt;/li&gt; &lt;/ul&gt; &lt;hr&gt; &lt;p&gt;&lt;strong&gt;App Download Links:&lt;/strong&gt;&lt;/p&gt; &lt;ul&gt; &lt;li&gt;&lt;strong&gt;Android:&lt;/strong&gt; {android_app}&lt;/li&gt; &lt;li&gt;&lt;strong&gt;iOS:&lt;/strong&gt; {ios_app}&lt;/li&gt; &lt;/ul&gt; &lt;hr&gt; &lt;p&gt;&lt;strong&gt;Steps to Complete the Registration:&lt;/strong&gt;&lt;/p&gt; &lt;ol&gt; &lt;li&gt;Download the school management app using the links above for easier access on your mobile devices.&lt;/li&gt; &lt;li&gt;Enter the email and password for either the student or parent account.&lt;/li&gt; &lt;li&gt;Follow the on-screen instructions to complete the profile setup.&lt;/li&gt; &lt;/ol&gt; &lt;p&gt;&lt;strong&gt;Important:&lt;/strong&gt;&lt;/p&gt; &lt;ul&gt; &lt;li&gt;For security reasons, please ensure that both the student and parent passwords are changed upon first login.&lt;/li&gt; &lt;li&gt;If you encounter any issues during the registration process, please do not hesitate to contact our support team at {support_email} or call {support_contact}.&lt;/li&gt; &lt;/ul&gt; &lt;p&gt;We look forward to an enriching educational experience for {child_name} at {school_name}. Thank you for entrusting us with your child&#039;s education.&lt;/p&gt; &lt;p&gt;Best regards,&lt;/p&gt; &lt;p&gt;{school_name}&lt;br&gt;{support_email}&lt;/p&gt;',
                'type' => 'text',
                'school_id' => $schoolData->id
            ],
            [
                'name' => 'email-template-application-reject',
                'data' => '&lt;p&gt;Dear {child_name},&lt;/p&gt; &lt;p&gt;We regret to inform you that your application for admission to {school_name} has been rejected. After a thorough review, it was found that your application did not meet certain criteria required for enrollment. Please note that this decision was made based on valid reasons, Unfortunately, all available seats for the requested grade have already been filled.&lt;/p&gt; &lt;p&gt;We encourage you to reach out to the admissions office if you have any questions or require further clarification.&lt;/p&gt; &lt;p&gt;Thank you for your interest in our school.&lt;/p&gt; &lt;p&gt;Sincerely,&lt;br&gt;{school_name}&lt;/p&gt; &lt;p&gt;Admissions Team&lt;/p&gt;',
                'type' => 'text',
                'school_id' => $schoolData->id
            ],


        );
        try {
            SchoolSetting::upsert($schoolSettingData, ["name", "school_id"], ["data", "type"]);
        } catch (\Exception $e) {
            \Log::error("Error inserting school settings for tenant {$schoolData->id}: " . $e->getMessage());
        }
        
        // Switch back to main database
        SharedHostingTenantService::switchToMain();
    }
    
    public function createPreSetupRole($school) {
        // Use new prefix-based approach
        $this->createPermissions();
        $this->createSchoolAdminRole($school);
        $this->assignSchoolAdminRole($school);
        $this->defaultRoles($school);
        $this->createTeacherRole($school);
    }

    public function assignSchoolAdminRole($school) {
        $prefix = SharedHostingTenantService::tenantTablePrefix($school->id);
        $schoolAdminUser = DB::table($prefix . 'users')->where('id', $school->admin_id)->first();
        if ($schoolAdminUser) {
            // Assign role using direct database insertion with prefix
            $role = DB::table($prefix . 'roles')->where('name', 'School Admin')->first();
            if ($role) {
                DB::table($prefix . 'model_has_roles')->updateOrInsert([
                    'role_id' => $role->id,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $schoolAdminUser->id
                ]);
            }
        }
    }

    public function defaultRoles($school)
    {
        Role::updateOrCreate(['name' => 'Guardian', 'school_id' => $school->id, 'custom_role' => 0, 'editable' => 0]);
        Role::updateOrCreate(['name' => 'Student', 'school_id' => $school->id, 'custom_role' => 0, 'editable' => 0]);
    }

    /**
     * Ensure the school uses prefixed tables on the shared database and run tenant migrations.
     * (Legacy name kept for upgrade migrations and tooling.)
     */
    public function createDatabaseMigration($schoolData)
    {
        set_time_limit(300);
        SharedHostingTenantService::switchToMain();

        $prefixName = SharedHostingTenantService::tenantTablePrefix($schoolData->id);
        DB::connection('mysql')->table('schools')->where('id', $schoolData->id)->update(['database_name' => $prefixName]);
        $schoolData->database_name = $prefixName;

        SharedHostingTenantService::createTenantTables($schoolData->id);
        SharedHostingTenantService::switchToMain();
        DB::setDefaultConnection('mysql');
    }

    public static function getTenantPermissionsList() {
        return [
            ...self::permission('role'),
            ...self::permission('medium'),
            ...self::permission('section'),
            ...self::permission('class'),
            ...self::permission('class-section'),
            ...self::permission('subject'),
            ...self::permission('teacher'),
            ...self::permission('guardian'),
            ...self::permission('session-year'),
            ...self::permission('student'),
            ...self::permission('timetable'),
            ...self::permission('attendance'),
            ...self::permission('holiday'),
            ...self::permission('announcement'),
            ...self::permission('slider'),
            ...self::permission('promote-student'),
            ...self::permission('language'),
            ...self::permission('lesson'),
            ...self::permission('topic'),
            ...self::permission('schools'),
            ...self::permission('form-fields'),
            ...self::permission('grade'),
            ...self::permission('package'),
            ...self::permission('addons'),
            ...self::permission('guidance'),
            ...self::permission('assign-elective-subject'),

            ...self::permission('assignment'),
            ['name' => 'assignment-submission'],

            ...self::permission('exam'),
            ...self::permission('exam-timetable'),
            ['name' => 'exam-upload-marks'],
            ['name' => 'exam-result'],
            ['name' => 'exam-result-edit'],

            ['name' => 'system-setting-manage'],
            ['name' => 'fcm-setting-create'],
            ['name' => 'email-setting-create'],
            ['name' => 'privacy-policy'],
            ['name' => 'contact-us'],
            ['name' => 'about-us'],
            ['name' => 'terms-condition'],

            ['name' => 'class-teacher'],
            ['name' => 'student-reset-password'],
            ['name' => 'reset-password-list'],
            ['name' => 'student-change-password'],

            ['name' => 'fees-classes'],
            ['name' => 'fees-paid'],
            ['name' => 'fees-config'],

            ['name' => 'school-setting-manage'],
            ['name' => 'app-settings'],
            ['name' => 'subscription-view'],

            ...self::permission('online-exam'),
            ...self::permission('online-exam-questions'),
            ['name' => 'online-exam-result-list'],
            ...self::permission('fees-type'),
            ...self::permission('fees-class'),
            ...self::permission('role'),
            ...self::permission('staff'),
            ...self::permission('expense-category'),
            ...self::permission('expense'),
            ...self::permission('semester'),
            ...self::permission('payroll'),
            ...self::permission('stream'),
            ...self::permission('shift'),
            ...self::permission('leave'),
            ['name' => 'approve-leave'],
            ...self::permission('faqs'),

            ['name' => 'fcm-setting-manage'],

            ...self::permission('fees'),
            ...self::permission('transfer-student'),
            ...self::permission('gallery'),
            ...self::permission('notification'),

            ['name' => 'payment-settings'],

            ['name' => 'subscription-settings'],
            ['name' => 'subscription-change-bills'],
            ['name' => 'school-terms-condition'],

            ['name' => 'id-card-settings'],

            ['name' => 'subscription-bill-payment'],
            ['name' => 'web-settings'],

            ...self::permission('certificate'),

            ...self::permission('payroll-settings'),

            ['name' => 'school-web-settings' ],
            ...self::permission('class-group'),

            ['name' => 'email-template' ],
            ['name' => 'database-backup' ],
            ['name' => 'view-exam-marks'],

            ['name' => 'contact-inquiry-list'],
            
            // Library
            ...self::permission('book'),
            ...self::permission('book-issue', ['return']),
            ['name' => 'book-report-view'],

            // Reports
            ['name' => 'report-list'],
            ['name' => 'reports-student'],
            ['name' => 'reports-exam'],

            // Hostel
            ...self::permission('hostel'),
            ...self::permission('room'),
            ...self::permission('hostel-allocation'),
            ['name' => 'hostel-report-view'],

            // Transport
            ...self::permission('transport-route'),
            ...self::permission('transport-vehicle'),
            ...self::permission('transport-driver'),
            ...self::permission('transport-stop'),
            ...self::permission('transport-allocation'),
            ...self::permission('transport-fee', ['generate']),
            ['name' => 'transport-report-view'],

            // Virtual Classroom
            ...self::permission('virtual-classroom'),
            ['name' => 'virtual-classroom-reports'],
            ['name' => 'virtual-classroom-upcoming'],
            ['name' => 'virtual-classroom-live'],
        ];
    }

    public function createPermissions() {
        $permissions = self::getTenantPermissionsList();
        $permissions = array_map(static function ($data) {
            $data['guard_name'] = 'web';
            return $data;
        }, $permissions);
        Permission::upsert($permissions, ['name'], ['name']);
    }

    public static function permission($prefix, array $customPermissions = []) {

        $list = [["name" => $prefix . '-list']];
        $create = [["name" => $prefix . '-create']];
        $edit = [["name" => $prefix . '-edit']];
        $delete = [["name" => $prefix . '-delete']];

        $finalArray = array_merge($list, $create, $edit, $delete);
        foreach ($customPermissions as $customPermission) {
            $finalArray[] = ["name" => $prefix . "-" . $customPermission];
        }
        return $finalArray;
    }

    public function createSchoolAdminRole($school) {
        $role = Role::withoutGlobalScope('school')->updateOrCreate(['name' => 'School Admin', 'school_id' => $school->id], ['custom_role' => 0, 'editable' => 1]);
        
        $permissions = self::getTenantPermissionsList();
        $permissionNames = array_column($permissions, 'name');
        
        $role->syncPermissions($permissionNames);
    }

    public function createTeacherRole($school)
    {
        //Add Teacher Role
        $teacher_role = Role::updateOrCreate(['name' => 'Teacher', 'school_id' => $school->id, 'custom_role' => 0, 'editable' => 1]);
        $TeacherHasAccessTo = [
            'student-list',
            'timetable-list',
            'holiday-list',
            'announcement-list',
            'announcement-create',
            'announcement-edit',
            'announcement-delete',
            'assignment-create',
            'assignment-list',
            'assignment-edit',
            'assignment-delete',
            'assignment-submission',
            'lesson-list',
            'lesson-create',
            'lesson-edit',
            'lesson-delete',
            'topic-list',
            'topic-create',
            'topic-edit',
            'topic-delete',
            'class-section-list',
            'online-exam-create',
            'online-exam-list',
            'online-exam-edit',
            'online-exam-delete',
            'online-exam-questions-create',
            'online-exam-questions-list',
            'online-exam-questions-edit',
            'online-exam-questions-delete',
            'online-exam-result-list',
            
            'leave-list',
            'leave-create',
            'leave-edit',
            'leave-delete',

            'attendance-list',

            'book-list',
            'book-issue-list',
            'book-issue-create',
            'book-issue-return',
        ];
        $teacher_role->syncPermissions($TeacherHasAccessTo);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function filterRowForTable(array $row, string $table): array
    {
        if (!Schema::hasTable($table)) {
            return $row;
        }

        $columns = Schema::getColumnListing($table);

        return array_intersect_key($row, array_flip($columns));
    }

    /**
     * @param  array<string, mixed>  $schoolRow
     */
    private function syncTenantSchoolRowFromCentral(int $schoolId, array $schoolRow, string $tablePrefix = ''): void
    {
        $prefixedTable = $tablePrefix . 'schools';
        $patch = $this->filterRowForTable($schoolRow, $prefixedTable);
        unset($patch['id'], $patch['created_at'], $patch['admin_id']);
        if (count($patch) === 0) {
            return;
        }
        $patch['updated_at'] = $schoolRow['updated_at'] ?? Carbon::now();
        DB::table($prefixedTable)->where('id', $schoolId)->update($patch);
    }

    private function ensureTenantSchoolAdminLinked(int $schoolId, int $adminId, string $tablePrefix = ''): void
    {
        $prefixedTable = $tablePrefix . 'schools';
        if (!Schema::hasTable($prefixedTable) || !Schema::hasColumn($prefixedTable, 'admin_id')) {
            return;
        }
        DB::table($prefixedTable)->where('id', $schoolId)->update([
            'admin_id' => $adminId,
            'updated_at' => Carbon::now(),
        ]);
    }
    
    public static  function switchToMainDatabase()
    {
        SharedHostingTenantService::switchToMain();
        SharedHostingTenantService::resetSchoolDatabaseConnection();
        DB::setDefaultConnection('mysql');
        Session::forget('school_database_name');
        Session::flush();
        Session::put('school_database_name', null);

    }

    public static function switchToSchoolDatabase($school_id)
    {
        $school_database = School::where('id',$school_id)->pluck('database_name')->first();

        if (SharedHostingTenantService::usesPrefixedTenantTables($school_database)) {
            SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($school_database);
            DB::setDefaultConnection('school');
            Session::put('school_database_name', $school_database);
            return;
        }

        SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($school_database);
        DB::setDefaultConnection('school');

        Session::put('school_database_name', $school_database);
        
    }

    /**
     * Sync permissions and role assignments to all existing school databases.
     * Call this after adding new permissions to createPermissions(), createSchoolAdminRole(), createTeacherRole().
     */
    public static function syncPermissionsToAllSchools()
    {
        $schools = School::on('mysql')->get();
        $instance = new self();

        foreach ($schools as $school) {
            if (!$school->database_name) continue;

            try {
                if (SharedHostingTenantService::usesPrefixedTenantTables($school->database_name)) {
                    SharedHostingTenantService::switchToTenant($school->id);
                    $instance->createPermissions();
                    $instance->createSchoolAdminRole($school);
                    $instance->assignSchoolAdminRole($school);
                    $instance->createTeacherRole($school);
                    continue;
                }

                SharedHostingTenantService::configureSchoolConnectionFromDatabaseName($school->database_name);
                DB::setDefaultConnection('school');

                // Re-run permission creation and role sync
                $instance->createPermissions();
                $instance->createSchoolAdminRole($school);
                $instance->assignSchoolAdminRole($school);
                $instance->createTeacherRole($school);
            } catch (\Exception $e) {
                \Log::error("Failed to sync permissions for school {$school->name}: " . $e->getMessage());
            } finally {
                SharedHostingTenantService::switchToMain();
            }
        }

        // Switch back to main
        SharedHostingTenantService::switchToMain();
        SharedHostingTenantService::resetSchoolDatabaseConnection();
        DB::setDefaultConnection('mysql');
        DB::connection('mysql')->reconnect();
        DB::setDefaultConnection('mysql');
    }
}
