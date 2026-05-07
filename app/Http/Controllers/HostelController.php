<?php

namespace App\Http\Controllers;

use App\Models\Hostel;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HostelController extends Controller
{
    /**
     * Display hostel management page
     */
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-list');

        return response(view('hostel.hostels.index'));
    }

    /**
     * Get hostels list (AJAX for Bootstrap Table)
     */
    public function getHostels(Request $request)
    {
        try {
            $hostels = DB::connection('school')->table('hostels')
                ->whereNull('deleted_at')
                ->where('school_id', Auth::user()->school_id)
                ->select('id', 'name', 'description', 'created_at')
                ->get();

            // Get room counts and occupancy for each hostel
            $hostelData = $hostels->map(function ($hostel) {
                $rooms = DB::connection('school')->table('rooms')
                    ->where('hostel_id', $hostel->id)
                    ->whereNull('deleted_at')
                    ->selectRaw('SUM(capacity) as total_capacity, SUM(occupied_beds) as total_occupied')
                    ->first();

                $totalCapacity = $rooms->total_capacity ?? 0;
                $totalOccupied = $rooms->total_occupied ?? 0;
                $availableBeds = $totalCapacity - $totalOccupied;
                $occupancyRate = $totalCapacity > 0 ? round(($totalOccupied / $totalCapacity) * 100, 2) : 0;

                return [
                    'id' => $hostel->id,
                    'name' => $hostel->name,
                    'description' => $hostel->description ?? '',
                    'total_capacity' => $totalCapacity,
                    'occupied_beds' => $totalOccupied,
                    'available_beds' => $availableBeds,
                    'occupancy_rate' => $occupancyRate . '%',
                    'created_at' => $hostel->created_at,
                    'operate' => '<a href="' . route('hostel.rooms.index', ['hostel_id' => $hostel->id]) . '" class="btn btn-sm btn-info">Rooms</a> ' .
                        '<button class="btn btn-sm btn-primary edit-btn" data-id="' . $hostel->id . '">Edit</button> ' .
                        '<button class="btn btn-sm btn-danger delete-btn" data-id="' . $hostel->id . '">Delete</button>',
                ];
            });

            return response()->json([
                'total' => $hostelData->count(),
                'rows' => $hostelData
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a new hostel
     */
    public function store(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-create');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();

            $hostel = Hostel::create([
                'name' => $request->name,
                'description' => $request->description,
                'school_id' => Auth::user()->school_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hostel created successfully',
                'data' => $hostel
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create hostel: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get hostel details for editing
     */
    public function edit($id)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-edit');

        try {
            $hostel = Hostel::owner()->findOrFail($id);
            return response()->json($hostel);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hostel not found'], 404);
        }
    }

    /**
     * Update a hostel
     */
    public function update(Request $request, $id)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-edit');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();

            $hostel = Hostel::owner()->findOrFail($id);
            $hostel->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hostel updated successfully',
                'data' => $hostel
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update hostel: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete a hostel
     */
    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Hostel Management');
        ResponseService::noPermissionThenRedirect('hostel-delete');

        try {
            DB::beginTransaction();

            $hostel = Hostel::owner()->findOrFail($id);

            // Check if hostel has active allocations
            $activeAllocations = DB::connection('school')->table('hostel_allocations')
                ->where('hostel_id', $id)
                ->where('status', 'active')
                ->count();

            if ($activeAllocations > 0) {
                return response()->json(['error' => 'Cannot delete hostel with active student allocations'], 400);
            }

            // Delete associated rooms first
            DB::connection('school')->table('rooms')
                ->where('hostel_id', $id)
                ->update(['deleted_at' => now()]);

            $hostel->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Hostel deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete hostel: ' . $e->getMessage()], 500);
        }
    }
}
