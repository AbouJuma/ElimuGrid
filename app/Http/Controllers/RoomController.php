<?php

namespace App\Http\Controllers;

use App\Models\Hostel;
use App\Models\Room;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * Display rooms management page
     */
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('room-list');

        $hostelId = request()->get('hostel_id');
        $hostel = null;

        if ($hostelId) {
            $hostel = Hostel::owner()->findOrFail($hostelId);
        }

        $hostels = Hostel::owner()->orderBy('name', 'ASC')->get();

        return response(view('hostel.rooms.index', compact('hostel', 'hostels')));
    }

    /**
     * Get rooms list (AJAX for Bootstrap Table)
     */
    public function getRooms(Request $request)
    {
        try {
            $query = Room::owner()->with('hostel');

            // Filter by hostel if provided
            if ($request->has('hostel_id') && $request->hostel_id) {
                $query->where('hostel_id', $request->hostel_id);
            }

            $rooms = $query->get();

            $roomData = $rooms->map(function ($room) {
                $availableBeds = $room->capacity - $room->occupied_beds;
                $occupancyRate = $room->capacity > 0 ? round(($room->occupied_beds / $room->capacity) * 100, 2) : 0;

                $statusBadge = $availableBeds > 0
                    ? '<span class="badge badge-success">Available</span>'
                    : '<span class="badge badge-danger">Full</span>';

                return [
                    'id' => $room->id,
                    'hostel_name' => $room->hostel->name ?? 'Unknown',
                    'room_number' => $room->room_number,
                    'capacity' => $room->capacity,
                    'occupied_beds' => $room->occupied_beds,
                    'available_beds' => $availableBeds,
                    'occupancy_rate' => $occupancyRate . '%',
                    'status' => $statusBadge,
                    'operate' => '<button class="btn btn-sm btn-primary edit-btn" data-id="' . $room->id . '">Edit</button> ' .
                        '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $room->id . '">Delete</button>',
                ];
            });

            return response()->json([
                'total' => $roomData->count(),
                'rows' => $roomData
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new room
     */
    public function store(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('room-create');

        $validator = Validator::make($request->all(), [
            'hostel_id' => 'required|exists:' . (new Hostel)->getTable() . ',id',
            'room_number' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::connection('mysql')->beginTransaction();

            // Check if room number already exists in this hostel
            $existingRoom = Room::owner()
                ->where('hostel_id', $request->hostel_id)
                ->where('room_number', $request->room_number)
                ->first();

            if ($existingRoom) {
                return response()->json(['error' => 'Room number already exists in this hostel'], 422);
            }

            $room = Room::create([
                'hostel_id' => $request->hostel_id,
                'room_number' => $request->room_number,
                'capacity' => $request->capacity,
                'occupied_beds' => 0,
                'school_id' => Auth::user()->school_id,
            ]);

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Room created successfully',
                'data' => $room
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            return response()->json(['error' => 'Failed to create room: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get room details for editing
     */
    public function edit($id)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('room-edit');

        try {
            $room = Room::owner()->with('hostel')->findOrFail($id);
            return response()->json($room);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Room not found'], 404);
        }
    }

    /**
     * Update a room
     */
    public function update(Request $request, $id)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('room-edit');

        $validator = Validator::make($request->all(), [
            'room_number' => 'required|string|max:50',
            'capacity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::connection('mysql')->beginTransaction();

            $room = Room::owner()->findOrFail($id);

            // Check if new capacity is less than occupied beds
            if ($request->capacity < $room->occupied_beds) {
                return response()->json(['error' => 'Capacity cannot be less than occupied beds (' . $room->occupied_beds . ')'], 422);
            }

            // Check if room number already exists in this hostel (excluding current room)
            $existingRoom = Room::owner()
                ->where('hostel_id', $room->hostel_id)
                ->where('room_number', $request->room_number)
                ->where('id', '!=', $id)
                ->first();

            if ($existingRoom) {
                return response()->json(['error' => 'Room number already exists in this hostel'], 422);
            }

            $room->update([
                'room_number' => $request->room_number,
                'capacity' => $request->capacity,
            ]);

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Room updated successfully',
                'data' => $room
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            return response()->json(['error' => 'Failed to update room: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a room
     */
    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('room-delete');

        try {
            DB::connection('mysql')->beginTransaction();

            $room = Room::owner()->findOrFail($id);

            // Check if room has active allocations using prefix-safe Eloquent model
            $activeAllocations = \App\Models\HostelAllocation::owner()
                ->where('room_id', $id)
                ->active()
                ->count();

            if ($activeAllocations > 0) {
                return response()->json(['error' => 'Cannot delete room with active student allocations'], 400);
            }

            $room->delete();

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Room deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            return response()->json(['error' => 'Failed to delete room: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get available rooms for allocation
     */
    public function getAvailableRooms(Request $request)
    {
        try {
            $hostelId = $request->get('hostel_id');

            $query = Room::owner()
                ->whereRaw('occupied_beds < capacity');

            if ($hostelId) {
                $query->where('hostel_id', $hostelId);
            }

            $rooms = $query->with('hostel')
                ->get()
                ->map(function ($room) {
                    return [
                        'id' => $room->id,
                        'text' => $room->hostel->name . ' - Room ' . $room->room_number . ' (' . $room->available_beds . ' beds available)',
                        'available_beds' => $room->available_beds,
                    ];
                });

            return response()->json($rooms);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get rooms by hostel (AJAX for dropdown)
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
}
