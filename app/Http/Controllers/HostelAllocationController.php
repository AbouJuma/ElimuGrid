<?php

namespace App\Http\Controllers;

use App\Models\Hostel;
use App\Models\HostelAllocation;
use App\Models\Room;
use App\Repositories\ClassSchool\ClassSchoolInterface;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HostelAllocationController extends Controller
{
    private ClassSchoolInterface $classSchool;

    public function __construct(ClassSchoolInterface $classSchool)
    {
        $this->classSchool = $classSchool;
    }

    /**
     * Display allocations management page
     */
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-allocation-list');

        $hostels = Hostel::owner()->orderBy('name', 'ASC')->get();
        $classes = $this->classSchool->builder()->orderBy('name', 'ASC')->get();

        return response(view('hostel.allocations.index', compact('hostels', 'classes')));
    }

    /**
     * Get allocations list (AJAX for Bootstrap Table)
     */
    public function getAllocations(Request $request)
    {
        try {
            $query = DB::connection('school')->table('hostel_allocations as ha')
                ->leftJoin('users as u', 'ha.student_id', '=', 'u.id')
                ->leftJoin('class_schools as cs', 'ha.class_id', '=', 'cs.id')
                ->leftJoin('hostels as h', 'ha.hostel_id', '=', 'h.id')
                ->leftJoin('rooms as r', 'ha.room_id', '=', 'r.id')
                ->whereNull('ha.deleted_at')
                ->where('ha.school_id', Auth::user()->school_id)
                ->select('ha.*', 'u.first_name', 'u.last_name', 'u.email', 'cs.name as class_name', 'h.name as hostel_name', 'r.room_number');

            // Apply filters
            if ($request->has('hostel_id') && $request->hostel_id) {
                $query->where('ha.hostel_id', $request->hostel_id);
            }

            if ($request->has('class_id') && $request->class_id) {
                $query->where('ha.class_id', $request->class_id);
            }

            if ($request->has('room_id') && $request->room_id) {
                $query->where('ha.room_id', $request->room_id);
            }

            if ($request->has('status') && $request->status) {
                $query->where('ha.status', $request->status);
            }

            $allocations = $query->orderBy('ha.allocation_date', 'desc')->get();

            $allocationData = $allocations->map(function ($allocation) {
                $studentName = trim(($allocation->first_name ?? '') . ' ' . ($allocation->last_name ?? '')) ?: 'Unknown Student';

                $statusBadge = $allocation->status === 'active'
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Checked Out</span>';

                $operateButtons = '';
                if ($allocation->status === 'active') {
                    $operateButtons .= '<button class="btn btn-sm btn-warning checkout-btn" data-id="' . $allocation->id . '">Check Out</button> ';
                }
                $operateButtons .= '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $allocation->id . '">Delete</button>';

                return [
                    'id' => $allocation->id,
                    'student_name' => $studentName,
                    'student_email' => $allocation->email ?? '',
                    'class_name' => $allocation->class_name ?? 'Unknown',
                    'hostel_name' => $allocation->hostel_name ?? 'Unknown',
                    'room_number' => $allocation->room_number ?? 'Unknown',
                    'bed_number' => $allocation->bed_number ?? '-',
                    'allocation_date' => $allocation->allocation_date,
                    'checkout_date' => $allocation->checkout_date ?? '-',
                    'status' => $statusBadge,
                    'operate' => $operateButtons,
                ];
            });

            return response()->json([
                'total' => $allocationData->count(),
                'rows' => $allocationData
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new allocation
     */
    public function store(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-allocation-create');

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'class_id' => 'required|exists:class_schools,id',
            'hostel_id' => 'required|exists:hostels,id',
            'room_id' => 'required|exists:rooms,id',
            'bed_number' => 'nullable|string|max:50',
            'allocation_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();

            // Check if student already has an active allocation
            $existingAllocation = HostelAllocation::owner()
                ->where('student_id', $request->student_id)
                ->where('status', 'active')
                ->first();

            if ($existingAllocation) {
                return response()->json(['error' => 'Student already has an active hostel allocation'], 422);
            }

            // Check if room has available beds
            $room = Room::owner()->findOrFail($request->room_id);
            if (!$room->hasAvailableBeds()) {
                return response()->json(['error' => 'Room is full. No beds available.'], 422);
            }

            // Check if room belongs to the selected hostel
            if ($room->hostel_id != $request->hostel_id) {
                return response()->json(['error' => 'Selected room does not belong to the selected hostel'], 422);
            }

            $allocation = HostelAllocation::create([
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'hostel_id' => $request->hostel_id,
                'room_id' => $request->room_id,
                'bed_number' => $request->bed_number,
                'allocation_date' => $request->allocation_date,
                'status' => 'active',
                'school_id' => Auth::user()->school_id,
            ]);

            // Increment occupied beds in room
            $room->increment('occupied_beds');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student allocated to hostel successfully',
                'data' => $allocation
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to allocate student: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check out a student from hostel
     */
    public function checkOut($id)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-allocation-edit');

        try {
            DB::beginTransaction();

            $allocation = HostelAllocation::owner()->where('status', 'active')->findOrFail($id);

            // Update allocation status
            $allocation->checkOut();

            // Decrement occupied beds in room
            $room = Room::owner()->find($allocation->room_id);
            if ($room && $room->occupied_beds > 0) {
                $room->decrement('occupied_beds');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student checked out successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to check out student: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete an allocation
     */
    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-allocation-delete');

        try {
            DB::beginTransaction();

            $allocation = HostelAllocation::owner()->findOrFail($id);

            // If allocation is active, decrement occupied beds
            if ($allocation->status === 'active') {
                $room = Room::owner()->find($allocation->room_id);
                if ($room && $room->occupied_beds > 0) {
                    $room->decrement('occupied_beds');
                }
            }

            $allocation->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Allocation deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete allocation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get rooms by hostel ID (AJAX)
     */
    public function getRoomsByHostel(Request $request)
    {
        try {
            $hostelId = $request->get('hostel_id');

            if (!$hostelId) {
                return response()->json([]);
            }

            $rooms = Room::owner()
                ->where('hostel_id', $hostelId)
                ->whereRaw('occupied_beds < capacity')
                ->orderBy('room_number', 'ASC')
                ->get()
                ->map(function ($room) {
                    $availableBeds = $room->capacity - $room->occupied_beds;
                    return [
                        'id' => $room->id,
                        'text' => 'Room ' . $room->room_number . ' (' . $availableBeds . ' beds available)',
                        'available_beds' => $availableBeds,
                    ];
                });

            return response()->json($rooms);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reports page
     */
    public function reports()
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-report-view');

        $hostels = Hostel::owner()->orderBy('name', 'ASC')->get();
        $classes = $this->classSchool->builder()->orderBy('name', 'ASC')->get();

        // Calculate stats
        $stats = [
            'total_hostels' => Hostel::owner()->count(),
            'total_rooms' => Room::owner()->count(),
            'total_capacity' => Room::owner()->sum('capacity'),
            'total_occupied' => Room::owner()->sum('occupied_beds'),
            'total_allocations' => HostelAllocation::owner()->where('status', 'active')->count(),
        ];

        $stats['available_beds'] = $stats['total_capacity'] - $stats['total_occupied'];
        $stats['occupancy_rate'] = $stats['total_capacity'] > 0
            ? round(($stats['total_occupied'] / $stats['total_capacity']) * 100, 2)
            : 0;

        return response(view('hostel.reports.index', compact('hostels', 'classes', 'stats')));
    }

    /**
     * Get occupancy report data
     */
    public function getOccupancyReport(Request $request)
    {
        try {
            $hostelId = $request->get('hostel_id');

            $query = DB::connection('school')->table('rooms as r')
                ->leftJoin('hostels as h', 'r.hostel_id', '=', 'h.id')
                ->whereNull('r.deleted_at')
                ->where('r.school_id', Auth::user()->school_id)
                ->select('r.*', 'h.name as hostel_name');

            if ($hostelId) {
                $query->where('r.hostel_id', $hostelId);
            }

            $rooms = $query->orderBy('h.name')->orderBy('r.room_number')->get();

            $reportData = $rooms->map(function ($room) {
                $availableBeds = $room->capacity - $room->occupied_beds;
                $occupancyRate = $room->capacity > 0 ? round(($room->occupied_beds / $room->capacity) * 100, 2) : 0;

                return [
                    'hostel_name' => $room->hostel_name ?? 'Unknown',
                    'room_number' => $room->room_number,
                    'capacity' => $room->capacity,
                    'occupied_beds' => $room->occupied_beds,
                    'available_beds' => $availableBeds,
                    'occupancy_rate' => $occupancyRate . '%',
                ];
            });

            return response()->json([
                'total' => $reportData->count(),
                'rows' => $reportData
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get students by class (AJAX for dropdown)
     */
    public function getStudentsByClass(Request $request)
    {
        try {
            $classId = $request->get('class_id');

            if (!$classId) {
                return response()->json(['data' => []]);
            }

            $students = DB::connection('school')->table('students as s')
                ->leftJoin('users as u', 's.user_id', '=', 'u.id')
                ->leftJoin('class_sections as cs', 's.class_section_id', '=', 'cs.id')
                ->leftJoin('class_schools as c', 'cs.class_id', '=', 'c.id')
                ->where('c.id', $classId)
                ->whereNull('s.deleted_at')
                ->whereNull('u.deleted_at')
                ->where('s.school_id', Auth::user()->school_id)
                ->select('s.id', 'u.first_name', 'u.last_name', 'u.email', 'u.id as user_id')
                ->orderBy('u.first_name')
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'user' => [
                            'id' => $student->user_id,
                            'first_name' => $student->first_name,
                            'last_name' => $student->last_name,
                            'email' => $student->email,
                        ]
                    ];
                });

            return response()->json([
                'data' => $students
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
