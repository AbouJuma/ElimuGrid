<?php

namespace App\Http\Controllers;

use App\Repositories\Attendance\AttendanceInterface;
use App\Repositories\ClassSection\ClassSectionInterface;
use App\Repositories\Student\StudentInterface;
use App\Services\CachingService;
use App\Services\ResponseService;
use App\Services\SessionYearsTrackingsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AttendanceController extends Controller
{

    private AttendanceInterface $attendance;
    private ClassSectionInterface $classSection;
    private StudentInterface $student;
    private CachingService $cache;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(AttendanceInterface $attendance, ClassSectionInterface $classSection, StudentInterface $student, CachingService $cachingService, SessionYearsTrackingsService $sessionYearsTrackingsService)
    {
        $this->attendance = $attendance;
        $this->classSection = $classSection;
        $this->student = $student;
        $this->cache = $cachingService;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }


    public function index()
    {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher', 'attendance-list']);
        $classSections = $this->classSection->builder()->ClassTeacher()->with('class', 'class.stream', 'section', 'medium')->get();
        return view('attendance.index', compact('classSections'));
    }


    public function view()
    {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher', 'attendance-list']);
        $class_sections = $this->classSection->builder()->ClassTeacher()->with('class', 'class.stream', 'section', 'medium')->get();
        return view('attendance.view', compact('class_sections'));
    }

    public function getAttendanceData(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        $response = $this->attendance->builder()->select('type')->where(['date' => date('Y-m-d', strtotime($request->date)), 'class_section_id' => $request->class_section_id])->pluck('type')->first();
        return response()->json($response);
    }

    public function store(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher', 'attendance-create', 'attendance-edit']);
        $request->validate([
            'class_section_id' => 'required',
            'date'             => 'required',
        ]);
        try {
            DB::connection('mysql')->beginTransaction();
            $attendanceData = array();
            $sessionYear = $this->cache->getDefaultSessionYear();
            $student_ids = array();
            foreach ($request->attendance_data as $value) {
                $data = (object)$value;
                $attendanceData[] = array(
                    "id"               => $data->id,
                    'class_section_id' => $request->class_section_id,
                    'student_id'       => $data->student_id,
                    'session_year_id'  => $sessionYear->id,
                    'type'             => $request->holiday ?? $data->type,
                    'date'             => date('Y-m-d', strtotime($request->date)),
                );

                if ($data->type == 0) {
                    $student_ids[] = $data->student_id;
                }
            }
            $this->attendance->upsert($attendanceData, ["id"], ["class_section_id", "student_id", "session_year_id", "type", "date"]);

            if ($request->absent_notification) {
                $guardianIds = $this->student->builder()
                    ->whereIn('user_id', $student_ids)
                    ->pluck('guardian_id')
                    ->unique()
                    ->toArray();
                $date = Carbon::parse(date('Y-m-d', strtotime($request->date)))->format('F jS, Y');
                $title = 'Absent';
                $body = 'Your child is absent on ' . $date;
                $type = "attendance";

                // Send push notification if there are guardians with FCM tokens
                if (!empty($guardianIds)) {
                    send_notification($guardianIds, $title, $body, $type);
                    // Persist notification record for audit and visibility in notification list
                    $notification = \App\Models\Notification::create([
                        'title' => $title,
                        'message' => $body,
                        'send_to' => 'Guardian',
                        'session_year_id' => $sessionYear->id,
                        'school_id' => auth()->user()->school_id ?? null,
                    ]);
                    // Add tracking entry
                    $this->sessionYearsTrackingsService->storeSessionYearsTracking('App\Models\Notification', $notification->id, Auth::user()->id, $sessionYear->id, Auth::user()->school_id, null);
                }
            }

            DB::connection('mysql')->commit();
            ResponseService::successResponse('Data Stored Successfully');
        } catch (Throwable $e) {
            if (Str::contains($e->getMessage(), [
                'does not exist','file_get_contents'
            ])) {
                DB::connection('mysql')->commit();
                ResponseService::warningResponse("Data Stored successfully. But App push notification not send.");
            } else {
                DB::connection('mysql')->rollBack();
                ResponseService::logErrorResponse($e, "Attendance Controller -> Store method");
                ResponseService::errorResponse();
            }
        }
    }

    public function show(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher', 'attendance-list']);

        //        $offset = $request->input('offset', 0);
        //        $limit = $request->input('limit', 10);
        $sort = $request->input('sort', 'roll_number');
        $order = $request->input('order', 'ASC');
        $search = $request->input('search');

        $class_section_id = $request->class_section_id;
        $date = date('Y-m-d', strtotime($request->date));
        $sessionYear = $this->cache->getDefaultSessionYear();

        $attendanceData = array();
        $total = 0;

        $attendanceQuery = $this->attendance->builder()->with('user.student')->where(['date' => $date, 'class_section_id' => $class_section_id, 'session_year_id' => $sessionYear->id])->whereHas('user', function ($q) {
            $q->whereNull('deleted_at');
        })->whereHas('user.student', function ($q) use ($sessionYear) {
            $q->where('session_year_id', $sessionYear->id);
        });

        if ($date != '' && $attendanceQuery->count() > 0) {
            $attendanceQuery->when($search, function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")->orWhereHas('user', function ($q) use ($search) {
                    $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'");
                });
            })->where('date', $date)->whereHas('user.student', function ($q) use ($sessionYear) {
                $q->where('session_year_id', $sessionYear->id);
            });

            $total = $attendanceQuery->count();
            $attendanceData = $attendanceQuery->get();
        } else if ($class_section_id) {
            $studentQuery = $this->student->builder()->where('session_year_id', $sessionYear->id)->where('class_section_id', $class_section_id)->with('user')
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->when($search, function ($query) use ($search) {
                    $query->where('id', 'LIKE', "%$search%")->orWhereHas('user', function ($q) use ($search) {
                        $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE '%" . $search . "%'")->where('deleted_at', NULL);
                    });
                })->where('session_year_id', $sessionYear->id)->where('class_section_id', $class_section_id);

            $total = $studentQuery->count();
            // $studentQuery->orderBy($sort, $order)->skip($offset)->take($limit);
            $studentQuery->orderBy($sort, $order);
            $attendanceData = $studentQuery->get();
        }

        $rows = [];
        $no = 1;

        foreach ($attendanceData as $row) {
            $type = $row->type ?? NULL;
            // TODO : understand this code
            $rows[] = [
                'id'           => $attendanceQuery->count() ? $row->id : null,
                'no'           => $no,
                'student_id'   => $attendanceQuery->count() ? $row->student_id : $row->user_id,
                'user_id'      => $attendanceQuery->count() ? $row->student_id : $row->user_id,
                'admission_no' => $row->user ? ($row->user->student->admission_no ?? '') : ($row->admission_no ?? ''),
                'roll_no'      => $row->user ? ($row->user->student->roll_number ?? '') : ($row->roll_number ?? ''),
                'name' => '<input type="hidden" value="' . ($row->student_id ? $row->user_id : 'null') . '" name="attendance_data[' . $no . '][id]"><input type="hidden" value="' . ($row->student_id ?? $row->user_id) . '" name="attendance_data[' . $no . '][student_id]">' . ($row->user->first_name ?? '') . ' ' . ($row->user->last_name ?? ''),
                'type'         => $type,
            ];
            $no++;
        }

        $bulkData['total'] = $total;
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }


    public function attendance_show(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher', 'attendance-list']);

        $offset = request('offset', 0);
        $limit = request('limit');
        $sort = request('sort', 'student_id');
        $order = request('order', 'ASC');
        $search = request('search');
        $attendanceType = request('attendance_type');

        $class_section_id = request('class_section_id');
        $date = date('Y-m-d', strtotime(request('date')));

        $validator = Validator::make($request->all(), ['class_section_id' => 'required', 'date' => 'required',]);
        if ($validator->fails()) {
            ResponseService::errorResponse($validator->errors()->first());
        }

        $sessionYear = $this->cache->getDefaultSessionYear();

        // First check if any attendance records exist for this date/class
        $attendanceCount = $this->attendance->builder()
            ->where(['date' => $date, 'class_section_id' => $class_section_id])
            ->count();

        if ($attendanceCount > 0) {
            // Show students with attendance records
            $sql = $this->attendance->builder()->where(['date' => $date, 'class_section_id' => $class_section_id])->with('user.student')
                ->where(function ($query) use ($search) {
                    $query->when($search, function ($query) use ($search) {
                        $query->where(function ($query) use ($search) {
                            $query->where('id', 'LIKE', "%$search%")
                                ->orwhere('student_id', 'LIKE', "%$search%")
                                ->orWhereHas('user', function ($q) use ($search) {
                                    $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'")
                                        ->orwhere('first_name', 'LIKE', "%$search%")
                                        ->orwhere('last_name', 'LIKE', "%$search%");
                                })->orWhereHas('user.student', function ($q) use ($search) {
                                    $q->where('admission_no', 'LIKE', "%$search%")
                                        ->orwhere('id', 'LIKE', "%$search%")
                                        ->orwhere('user_id', 'LIKE', "%$search%")
                                        ->orwhere('roll_number', 'LIKE', "%$search%");
                                });
                        });
                    });
                })
                ->when($attendanceType != null, function ($query) use ($attendanceType) {
                    $query->where('type', $attendanceType);
                });
            $sql = $sql->whereHas('user.student', function ($q) use ($sessionYear) {
                $q->where('session_year_id', $sessionYear->id);
            });
            $total = $sql->count();

            $sql->orderBy($sort, $order);

            if ($limit) {
                $sql->skip($offset)->take($limit);
            }

            $res = $sql->get();
        } else {
            // No attendance records yet - show all students in class section
            $sql = $this->student->builder()
                ->with('user')
                ->where('session_year_id', $sessionYear->id)
                ->where('class_section_id', $class_section_id)
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('id', 'LIKE', "%$search%")
                            ->orWhereHas('user', function ($q) use ($search) {
                                $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'")
                                    ->orwhere('first_name', 'LIKE', "%$search%")
                                    ->orwhere('last_name', 'LIKE', "%$search%");
                            })->orWhereHas('user.student', function ($q) use ($search) {
                                $q->where('admission_no', 'LIKE', "%$search%")
                                    ->orwhere('roll_number', 'LIKE', "%$search%");
                            });
                    });
                });

            $total = $sql->count();
            $sql->orderBy($sort, $order);

            if ($limit) {
                $sql->skip($offset)->take($limit);
            }

            $students = $sql->get();
            
            // Format as attendance-like data
            $res = $students->map(function ($student) use ($date, $class_section_id, $sessionYear) {
                return [
                    'id' => null,
                    'student_id' => $student->user_id,
                    'class_section_id' => $class_section_id,
                    'date' => $date,
                    'session_year_id' => $sessionYear->id,
                    'type' => null,
                    'note' => null,
                    'user' => $student->user,
                ];
            });
        }

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $no = 1;

        foreach ($res as $row) {
            if (is_array($row)) {
                $tempRow = $row;
            } else {
                $tempRow = $row->toArray();
            }
            $tempRow['no'] = $no++;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);
    }

    public function monthWiseIndex()
    {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher', 'attendance-list']);
        $class_sections = $this->classSection->builder()->ClassTeacher()->with('class', 'class.stream', 'section', 'medium')->get();

        return view('attendance.month_wise',compact('class_sections'));
    }

    public function monthWiseShow(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['class-teacher', 'attendance-list']);

        $sessionYear = $this->cache->getDefaultSessionYear();
        $sql = $this->student->builder()->with('user')->whereHas('attendance', function($q) use($request,$sessionYear) {
            $q->where('class_section_id',$request->class_section_id)
            ->whereMonth('date',$request->month)
            ->where('session_year_id',$sessionYear->id);
        })->orderBy('roll_number','ASC');

        $total = $sql->count();
        
        $res = $sql->get();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        
        
        $month = $request->month;
        $date = Carbon::create(null, $month, 1);
                
        foreach ($res as $row) {            
            $studentAttendance = ['full_name' => $row->user->full_name, 'roll_number' => $row->roll_number];
            
            for ($day=1; $day <= $date->daysInMonth; $day++) {
                $currentDate = $date->copy()->day($day)->format('Y-m-d');
                $attendance = $row->attendance()->where('student_id', $row->user_id)->where('date', $currentDate)->first();
                $studentAttendance["day_$day"] = $attendance ? $attendance->type : null;

            }
            $tempRow[] = $studentAttendance;
            $rows = $tempRow;
        }
        $bulkData['rows'] = $rows;
        return response()->json($bulkData);

    }

    public function scanAttendance()
    {
        ResponseService::noFeatureThenRedirect('Attendance Management');
        ResponseService::noAnyPermissionThenRedirect(['attendance-create', 'attendance-edit', 'attendance-list', 'class-teacher']);
        
        $class_sections = $this->classSection->builder()->ClassTeacher()->with('class', 'class.stream', 'section', 'medium')->get();
        
        return view('attendance.scan', compact('class_sections'));
    }

    public function markAttendanceByBarcode(Request $request)
    {
        ResponseService::noFeatureThenSendJson('Attendance Management');
        ResponseService::noAnyPermissionThenSendJson(['attendance-create', 'attendance-edit', 'class-teacher', 'attendance-list']);
        
        try {
            $validator = Validator::make($request->all(), [
                'gr_number' => 'required|string',
                'class_section_id' => 'required|integer',
                'date' => 'required|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ]);
            }

            // Find student by Admission Number (GR Number)
            $student = $this->student->builder()
                ->where('admission_no', $request->gr_number)
                ->where('class_section_id', $request->class_section_id)
                ->with('user')
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found with this GR Number in the selected class'
                ]);
            }

            // Check if attendance already exists
            $sessionYear = $this->cache->getDefaultSessionYear();
            $existingAttendance = $this->attendance->builder()
                ->where('student_id', $student->user_id)
                ->where('class_section_id', $request->class_section_id)
                ->where('date', $request->date)
                ->where('session_year_id', $sessionYear->id)
                ->first();

            if ($existingAttendance) {
                // Update existing attendance to present
                $existingAttendance->update(['type' => 1]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Attendance updated to Present',
                    'student' => [
                        'name' => $student->user->full_name,
                        'gr_number' => $student->admission_no,
                        'roll_number' => $student->roll_number,
                        'status' => 'Present (Updated)'
                    ]
                ]);
            } else {
                // Create new attendance record
                $this->attendance->create([
                    'student_id' => $student->user_id,
                    'class_section_id' => $request->class_section_id,
                    'date' => $request->date,
                    'session_year_id' => $sessionYear->id,
                    'type' => 1, // Present
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Attendance marked as Present',
                    'student' => [
                        'name' => $student->user->full_name,
                        'gr_number' => $student->admission_no,
                        'roll_number' => $student->roll_number,
                        'status' => 'Present (New)'
                    ]
                ]);
            }

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking attendance: ' . $e->getMessage()
            ]);
        }
    }
}
