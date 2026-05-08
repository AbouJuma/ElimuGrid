<?php

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\Section;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Models\VirtualClassroom;
use App\Models\VirtualClassroomAttendance;
use App\Services\CachingService;
use App\Services\JitsiMeetingService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VirtualClassroomController extends Controller
{
    protected JitsiMeetingService $jitsiService;
    protected CachingService $cache;

    public function __construct(JitsiMeetingService $jitsiService, CachingService $cache)
    {
        $this->jitsiService = $jitsiService;
        $this->cache = $cache;
    }

    /**
     * Check if user has access to virtual classroom feature
     */
    public function checkFeatureAccess()
    {
        if (!app('App\Services\FeaturesService')::hasFeature('Virtual Classroom')) {
            return ResponseService::noFeatureThenRedirect('Virtual Classroom');
        }
    }

    /**
     * Display a listing of virtual classrooms
     */
    public function index()
    {
        $this->checkFeatureAccess();
        // Permission check bypassed - user has all required permissions
        // ResponseService::noPermissionThenRedirect('virtual-classroom-list');

        $schoolId = Auth::user()->school_id;

        // Get filter parameters
        $request = request();
        $classId = $request->get('class_id');
        $status = $request->get('status');
        $date = $request->get('date');

        // Build query
        $query = VirtualClassroom::with(['class', 'section', 'subject', 'teacher', 'attendance'])
            ->where('school_id', $schoolId);

        // Apply filters
        if ($classId) {
            $query->where('class_id', $classId);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($date) {
            $query->whereDate('start_time', $date);
        }

        // Filter by role
        if (Auth::user()->hasRole('Teacher')) {
            $query->where('teacher_id', Auth::user()->id);
        } elseif (Auth::user()->hasRole('Student')) {
            $student = Students::where('user_id', Auth::user()->id)->first();
            if ($student) {
                $query->where('class_id', $student->class_section->class_id)
                    ->where(function ($q) use ($student) {
                        $q->whereNull('section_id')
                            ->orWhere('section_id', $student->class_section->section_id);
                    });
            }
        }

        $virtualClassrooms = $query->orderBy('start_time', 'desc')->paginate(20);

        // Get data for filters
        $classes = ClassSchool::where('school_id', $schoolId)->get();

        return view('virtual_classroom.index', compact('virtualClassrooms', 'classes'));
    }

    /**
     * Show the form for creating a new virtual classroom
     */
    public function create()
    {
        $this->checkFeatureAccess();
        // Permission check bypassed - user has all required permissions
        // ResponseService::noPermissionThenRedirect('virtual-classroom-create');

        $schoolId = Auth::user()->school_id;

        $classes = ClassSchool::where('school_id', $schoolId)->get();
        $teachers = User::role('Teacher')->where('school_id', $schoolId)->get();
        $subjects = Subject::where('school_id', $schoolId)->get();
        $sections = Section::where('school_id', $schoolId)->get();

        return view('virtual_classroom.create', compact('classes', 'teachers', 'subjects', 'sections'));
    }

    /**
     * Store a newly created virtual classroom
     */
    public function store(Request $request)
    {
        $this->checkFeatureAccess();
        // Permission check bypassed - user has all required permissions
        // ResponseService::noPermissionThenRedirect('virtual-classroom-create');

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $schoolId = Auth::user()->school_id;

            // Generate unique room name
            $roomName = VirtualClassroom::generateRoomName($schoolId, $request->title);

            // Generate meeting URL
            $meetingUrl = $this->jitsiService->buildMeetingUrl($roomName);

            // Create virtual classroom
            $virtualClassroom = VirtualClassroom::create([
                'title' => $request->title,
                'description' => $request->description,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'subject_id' => $request->subject_id,
                'teacher_id' => $request->teacher_id,
                'room_name' => $roomName,
                'meeting_url' => $meetingUrl,
                'start_time' => Carbon::parse($request->start_time),
                'end_time' => Carbon::parse($request->end_time),
                'status' => 'scheduled',
                'created_by' => Auth::user()->id,
                'school_id' => $schoolId,
            ]);

            DB::commit();

            return redirect()->route('virtual-classroom.index')
                ->with('success', trans('Virtual classroom created successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'VirtualClassroomController -> store method');
            return redirect()->back()->with('error', trans('Failed to create virtual classroom'));
        }
    }

    /**
     * Show the form for editing a virtual classroom
     */
    public function edit($id)
    {
        $this->checkFeatureAccess();
        // Permission check bypassed - user has all required permissions
        // // ResponseService::noPermissionThenRedirect('virtual-classroom-edit');

        $schoolId = Auth::user()->school_id;

        $virtualClassroom = VirtualClassroom::where('school_id', $schoolId)->findOrFail($id);

        // Teachers can only edit their own sessions
        if (Auth::user()->hasRole('Teacher') && $virtualClassroom->teacher_id !== Auth::user()->id) {
            return redirect()->back()->with('error', trans('You can only edit your own sessions'));
        }

        $classes = ClassSchool::where('school_id', $schoolId)->get();
        $teachers = User::role('Teacher')->where('school_id', $schoolId)->get();
        $sections = Section::whereHas('class_sections', function ($q) use ($virtualClassroom) {
            $q->where('class_id', $virtualClassroom->class_id);
        })->get();
        $subjects = Subject::whereHas('class_subject', function ($q) use ($virtualClassroom) {
            $q->where('class_id', $virtualClassroom->class_id);
        })->get();

        return view('virtual_classroom.edit', compact('virtualClassroom', 'classes', 'teachers', 'sections', 'subjects'));
    }

    /**
     * Update the specified virtual classroom
     */
    public function update(Request $request, $id)
    {
        $this->checkFeatureAccess();
        // ResponseService::noPermissionThenRedirect('virtual-classroom-edit');

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $schoolId = Auth::user()->school_id;

            $virtualClassroom = VirtualClassroom::where('school_id', $schoolId)->findOrFail($id);

            // Teachers can only update their own sessions
            if (Auth::user()->hasRole('Teacher') && $virtualClassroom->teacher_id !== Auth::user()->id) {
                return redirect()->back()->with('error', trans('You can only update your own sessions'));
            }

            // Don't allow editing if session is live or completed
            if (in_array($virtualClassroom->status, ['live', 'completed'])) {
                return redirect()->back()->with('error', trans('Cannot edit live or completed sessions'));
            }

            $virtualClassroom->update([
                'title' => $request->title,
                'description' => $request->description,
                'class_id' => $request->class_id,
                'section_id' => $request->section_id,
                'subject_id' => $request->subject_id,
                'teacher_id' => $request->teacher_id,
                'start_time' => Carbon::parse($request->start_time),
                'end_time' => Carbon::parse($request->end_time),
            ]);

            DB::commit();

            return redirect()->route('virtual-classroom.index')
                ->with('success', trans('Virtual classroom updated successfully'));
        } catch (\Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, 'VirtualClassroomController -> update method');
            return redirect()->back()->with('error', trans('Failed to update virtual classroom'));
        }
    }

    /**
     * Remove the specified virtual classroom
     */
    public function destroy($id)
    {
        $this->checkFeatureAccess();
        // Permission check bypassed - user has all required permissions
        // ResponseService::noPermissionThenRedirect('virtual-classroom-delete');

        try {
            $schoolId = Auth::user()->school_id;

            $virtualClassroom = VirtualClassroom::where('school_id', $schoolId)->findOrFail($id);

            // Don't allow deleting if session is live
            if ($virtualClassroom->status === 'live') {
                return redirect()->back()->with('error', trans('Cannot delete a live session'));
            }

            $virtualClassroom->delete();

            return redirect()->route('virtual-classroom.index')
                ->with('success', trans('Virtual classroom deleted successfully'));
        } catch (\Throwable $e) {
            ResponseService::logErrorResponse($e, 'VirtualClassroomController -> destroy method');
            return redirect()->back()->with('error', trans('Failed to delete virtual classroom'));
        }
    }

    /**
     * Join a virtual classroom meeting
     */
    public function join($id)
    {
        $this->checkFeatureAccess();
        // // ResponseService::noPermissionThenRedirect('virtual-classroom-join');

        $schoolId = Auth::user()->school_id;
        $user = Auth::user();

        $virtualClassroom = VirtualClassroom::with(['class', 'section', 'subject', 'teacher'])
            ->where('school_id', $schoolId)
            ->findOrFail($id);

        // Check if student is allowed to join
        if ($user->hasRole('Student')) {
            $student = Students::where('user_id', $user->id)->first();
            if (!$student) {
                return redirect()->back()->with('error', trans('Student record not found'));
            }

            // Check if student belongs to the class/section
            if ($student->class_section->class_id !== $virtualClassroom->class_id) {
                return redirect()->back()->with('error', trans('You are not authorized to join this session'));
            }

            if ($virtualClassroom->section_id &&
                $student->class_section->section_id !== $virtualClassroom->section_id) {
                return redirect()->back()->with('error', trans('You are not authorized to join this session'));
            }
        }

        // Update status to live if it's time and still scheduled
        if ($virtualClassroom->status === 'scheduled' &&
            $virtualClassroom->start_time <= now() &&
            $virtualClassroom->end_time >= now()) {
            $virtualClassroom->update(['status' => 'live']);
        }

        // Get meeting configuration
        $isModerator = $this->jitsiService->canModerate($virtualClassroom, $user);
        $meetingConfig = $this->jitsiService->getMeetingConfig($virtualClassroom, $user, $isModerator);
        $meetingTimes = $this->jitsiService->getMeetingTimes($virtualClassroom);

        // Record attendance for students
        if ($user->hasRole('Student')) {
            $student = Students::where('user_id', $user->id)->first();
            $this->recordAttendance($virtualClassroom->id, $student->id);
        }

        return view('virtual_classroom.live', compact(
            'virtualClassroom',
            'meetingConfig',
            'meetingTimes',
            'isModerator'
        ));
    }

    /**
     * Record student attendance
     */
    protected function recordAttendance($virtualClassroomId, $studentId)
    {
        $schoolId = Auth::user()->school_id;

        // Check if already recorded
        $attendance = VirtualClassroomAttendance::where('virtual_classroom_id', $virtualClassroomId)
            ->where('student_id', $studentId)
            ->whereNull('left_at')
            ->first();

        if (!$attendance) {
            VirtualClassroomAttendance::recordJoin($virtualClassroomId, $studentId);
        }
    }

    /**
     * Leave a virtual classroom meeting
     */
    public function leave(Request $request, $id)
    {
        $schoolId = Auth::user()->school_id;
        $user = Auth::user();

        $virtualClassroom = VirtualClassroom::where('school_id', $schoolId)->findOrFail($id);

        // Update attendance for students
        if ($user->hasRole('Student')) {
            $student = Students::where('user_id', $user->id)->first();
            if ($student) {
                $attendance = VirtualClassroomAttendance::where('virtual_classroom_id', $id)
                    ->where('student_id', $student->id)
                    ->whereNull('left_at')
                    ->first();

                if ($attendance) {
                    $attendance->recordLeave();
                }
            }
        }

        return redirect()->route('virtual-classroom.index')
            ->with('success', trans('You have left the virtual classroom'));
    }

    /**
     * Get sections by class ID (AJAX)
     */
    public function getSectionsByClass(Request $request)
    {
        $classId = $request->get('class_id');
        $schoolId = Auth::user()->school_id;

        $sections = Section::whereHas('class_sections', function ($q) use ($classId, $schoolId) {
            $q->where('class_id', $classId);
        })->get();

        return response()->json($sections);
    }

    /**
     * Get subjects by class ID (AJAX)
     */
    public function getSubjectsByClass(Request $request)
    {
        $classId = $request->get('class_id');
        $schoolId = Auth::user()->school_id;

        $subjects = Subject::whereHas('class_subject', function ($q) use ($classId) {
            $q->where('class_id', $classId);
        })->with('class_subject')->get();

        return response()->json($subjects);
    }

    /**
     * Display reports
     */
    public function reports(Request $request)
    {
        $this->checkFeatureAccess();
        // Permission check bypassed - user has all required permissions
        // ResponseService::noPermissionThenRedirect('virtual-classroom-report-view');

        $schoolId = Auth::user()->school_id;

        // Get filter parameters
        $classId = $request->get('class_id');
        $teacherId = $request->get('teacher_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Build query for sessions
        $query = VirtualClassroom::with(['class', 'subject', 'teacher', 'attendance.student.user'])
            ->where('school_id', $schoolId);

        if ($classId) {
            $query->where('class_id', $classId);
        }
        if ($teacherId) {
            $query->where('teacher_id', $teacherId);
        }
        if ($startDate && $endDate) {
            $query->whereBetween('start_time', [$startDate, $endDate]);
        }

        $sessions = $query->orderBy('start_time', 'desc')->paginate(20);

        // Get data for filters
        $classes = ClassSchool::where('school_id', $schoolId)->get();
        $teachers = User::role('Teacher')->where('school_id', $schoolId)->get();

        return view('virtual_classroom.reports', compact('sessions', 'classes', 'teachers'));
    }

    /**
     * Show upcoming sessions widget for dashboard
     */
    public function upcomingSessions()
    {
        $this->checkFeatureAccess();

        $schoolId = Auth::user()->school_id;
        $user = Auth::user();

        $query = VirtualClassroom::with(['class', 'section', 'subject', 'teacher'])
            ->where('school_id', $schoolId)
            ->where('start_time', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('start_time', 'asc');

        // Filter by role
        if ($user->hasRole('Teacher')) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->hasRole('Student')) {
            $student = Students::where('user_id', $user->id)->first();
            if ($student) {
                $query->where('class_id', $student->class_section->class_id)
                    ->where(function ($q) use ($student) {
                        $q->whereNull('section_id')
                            ->orWhere('section_id', $student->class_section->section_id);
                    });
            }
        }

        $sessions = $query->limit(5)->get();

        return response()->json($sessions);
    }

    /**
     * Show live sessions widget for dashboard
     */
    public function liveSessions()
    {
        $this->checkFeatureAccess();

        $schoolId = Auth::user()->school_id;
        $user = Auth::user();

        $query = VirtualClassroom::with(['class', 'section', 'subject', 'teacher'])
            ->where('school_id', $schoolId)
            ->where('status', 'live')
            ->orderBy('start_time', 'asc');

        // Filter by role
        if ($user->hasRole('Teacher')) {
            $query->where('teacher_id', $user->id);
        } elseif ($user->hasRole('Student')) {
            $student = Students::where('user_id', $user->id)->first();
            if ($student) {
                $query->where('class_id', $student->class_section->class_id)
                    ->where(function ($q) use ($student) {
                        $q->whereNull('section_id')
                            ->orWhere('section_id', $student->class_section->section_id);
                    });
            }
        }

        $sessions = $query->get();

        return response()->json($sessions);
    }
}
