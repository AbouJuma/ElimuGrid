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
            $query = HostelAllocation::owner()
                ->with(['student', 'classSchool', 'hostel', 'room']);

            // Apply filters
            if ($request->has('hostel_id') && $request->hostel_id) {
                $query->where('hostel_id', $request->hostel_id);
            }

            if ($request->has('class_id') && $request->class_id) {
                $query->where('class_id', $request->class_id);
            }

            if ($request->has('room_id') && $request->room_id) {
                $query->where('room_id', $request->room_id);
            }

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            $allocations = $query->orderBy('allocation_date', 'desc')->get();

            $allocationData = $allocations->map(function ($allocation) {
                $studentName = $allocation->student ? trim($allocation->student->first_name . ' ' . $allocation->student->last_name) : 'Unknown Student';

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
                    'student_email' => $allocation->student->email ?? '',
                    'class_name' => $allocation->classSchool->name ?? 'Unknown',
                    'hostel_name' => $allocation->hostel->name ?? 'Unknown',
                    'room_number' => $allocation->room->room_number ?? 'Unknown',
                    'bed_number' => $allocation->bed_number ?? '-',
                    'allocation_date' => $allocation->allocation_date ? $allocation->allocation_date->format('Y-m-d') : '',
                    'checkout_date' => $allocation->checkout_date ? $allocation->checkout_date->format('Y-m-d') : '-',
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
            'student_id' => 'required|exists:' . (new \App\Models\User)->getTable() . ',id',
            'class_id' => 'required|exists:' . (new \App\Models\ClassSchool)->getTable() . ',id',
            'hostel_id' => 'required|exists:' . (new Hostel)->getTable() . ',id',
            'room_id' => 'required|exists:' . (new Room)->getTable() . ',id',
            'bed_number' => 'nullable|string|max:50',
            'allocation_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::connection('mysql')->beginTransaction();

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

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Student allocated to hostel successfully',
                'data' => $allocation
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
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
            DB::connection('mysql')->beginTransaction();

            $allocation = HostelAllocation::owner()->where('status', 'active')->findOrFail($id);

            // Update allocation status
            $allocation->checkOut();

            // Decrement occupied beds in room
            $room = Room::owner()->find($allocation->room_id);
            if ($room && $room->occupied_beds > 0) {
                $room->decrement('occupied_beds');
            }

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Student checked out successfully'
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
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
            DB::connection('mysql')->beginTransaction();

            $allocation = HostelAllocation::owner()->findOrFail($id);

            // If allocation is active, decrement occupied beds
            if ($allocation->status === 'active') {
                $room = Room::owner()->find($allocation->room_id);
                if ($room && $room->occupied_beds > 0) {
                    $room->decrement('occupied_beds');
                }
            }

            $allocation->delete();

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Allocation deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
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

            $query = Room::owner()->with('hostel');

            if ($hostelId) {
                $query->where('hostel_id', $hostelId);
            }

            $rooms = $query->get();

            $reportData = $rooms->map(function ($room) {
                $availableBeds = $room->capacity - $room->occupied_beds;
                $occupancyRate = $room->capacity > 0 ? round(($room->occupied_beds / $room->capacity) * 100, 2) : 0;

                return [
                    'hostel_name' => $room->hostel->name ?? 'Unknown',
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

            $students = \App\Models\User::role('Student')
                ->whereHas('student', function ($query) use ($classId) {
                    $query->whereHas('class_section', function ($q) use ($classId) {
                        $q->where('class_id', $classId);
                    });
                })
                ->select('id', 'first_name', 'last_name', 'email')
                ->get()
                ->map(function ($student) {
                    return [
                        'id' => $student->student->id ?? 0,
                        'user' => [
                            'id' => $student->id,
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
