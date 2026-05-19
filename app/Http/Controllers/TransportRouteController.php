<?php

namespace App\Http\Controllers;

use App\Models\TransportRoute;
use App\Models\TransportFee;
use App\Models\TransportStop;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransportRouteController extends Controller
{
    /**
     * Display routes management page
     */
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-route-list');

        return response(view('transport.routes.index'));
    }

    /**
     * Get routes list (AJAX)
     */
    public function getRoutes(Request $request)
    {
        try {
            $query = DB::connection('school')->table('transport_routes as tr')
                ->whereNull('tr.deleted_at')
                ->where('tr.school_id', Auth::user()->school_id)
                ->select('tr.*');

            // Add calculated fields
            $routes = $query->get()->map(function ($route) {
                $route->stops_count = DB::connection('school')->table('transport_stops')
                    ->where('route_id', $route->id)
                    ->where('school_id', Auth::user()->school_id)
                    ->whereNull('deleted_at')
                    ->count();

                $route->allocations_count = DB::connection('school')->table('transport_allocations')
                    ->where('route_id', $route->id)
                    ->where('school_id', Auth::user()->school_id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at')
                    ->count();

                $activeFee = DB::connection('school')->table('transport_fees')
                    ->where('route_id', $route->id)
                    ->where('effective_from', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('effective_to')
                          ->orWhere('effective_to', '>=', now());
                    })
                    ->whereNull('deleted_at')
                    ->first();

                $route->current_fee = $activeFee ? $activeFee->amount : null;
                $route->fee_formatted = $activeFee ? number_format($activeFee->amount, 2) : 'Not set';

                return $route;
            });

            return response()->json([
                'total' => $routes->count(),
                'rows' => $routes
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store new route with fee
     */
    public function store(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-route-create');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'start_point' => 'required|string|max:100',
            'end_point' => 'required|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'departure_time' => 'nullable',
            'return_time' => 'nullable',
            'fee_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,term,yearly',
            'fee_effective_from' => 'required|date',
            'fee_description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::connection('mysql')->beginTransaction();

            // Create route
            $route = TransportRoute::create([
                'name' => $request->name,
                'description' => $request->description,
                'start_point' => $request->start_point,
                'end_point' => $request->end_point,
                'distance_km' => $request->distance_km,
                'departure_time' => $request->departure_time,
                'return_time' => $request->return_time,
                'school_id' => Auth::user()->school_id,
            ]);

            // Create transport fee
            TransportFee::create([
                'route_id' => $route->id,
                'amount' => $request->fee_amount,
                'billing_cycle' => $request->billing_cycle,
                'effective_from' => $request->fee_effective_from,
                'effective_to' => $request->fee_effective_to,
                'description' => $request->fee_description,
                'school_id' => Auth::user()->school_id,
            ]);

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Route created successfully with transport fee',
                'data' => $route->load('activeFee')
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            return response()->json(['error' => 'Failed to create route: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update route
     */
    public function update(Request $request, $id)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-route-edit');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'start_point' => 'required|string|max:100',
            'end_point' => 'required|string|max:100',
            'distance_km' => 'nullable|numeric|min:0',
            'departure_time' => 'nullable',
            'return_time' => 'nullable',
            'fee_amount' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,term,yearly',
            'fee_effective_from' => 'required|date',
            'fee_effective_to' => 'nullable|date|after_or_equal:fee_effective_from',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::connection('mysql')->beginTransaction();

            $route = TransportRoute::owner()->findOrFail($id);

            $route->update([
                'name' => $request->name,
                'description' => $request->description,
                'start_point' => $request->start_point,
                'end_point' => $request->end_point,
                'distance_km' => $request->distance_km,
                'departure_time' => $request->departure_time,
                'return_time' => $request->return_time,
            ]);

            // Update or create fee
            $existingFee = TransportFee::where('route_id', $route->id)
                ->whereNull('effective_to')
                ->first();

            if ($existingFee) {
                // Close existing fee
                $existingFee->update(['effective_to' => now()->subDay()]);
            }

            // Create new fee
            TransportFee::create([
                'route_id' => $route->id,
                'amount' => $request->fee_amount,
                'billing_cycle' => $request->billing_cycle,
                'effective_from' => $request->fee_effective_from,
                'effective_to' => $request->fee_effective_to,
                'description' => $request->fee_description,
                'school_id' => Auth::user()->school_id,
            ]);

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Route updated successfully',
                'data' => $route->load('activeFee')
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            return response()->json(['error' => 'Failed to update route: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete route
     */
    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-route-delete');

        try {
            DB::connection('mysql')->beginTransaction();

            $route = TransportRoute::owner()->findOrFail($id);

            // Check if route has active allocations
            $activeAllocations = DB::connection('school')->table('transport_allocations')
                ->where('route_id', $id)
                ->where('status', 'active')
                ->count();

            if ($activeAllocations > 0) {
                return response()->json([
                    'error' => 'Cannot delete route with active student allocations. Please reassign students first.'
                ], 400);
            }

            $route->delete();

            // Soft delete related fees
            TransportFee::where('route_id', $id)->delete();

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Route deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            return response()->json(['error' => 'Failed to delete route: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get route details with stops and fees
     */
    public function show($id)
    {
        try {
            $route = TransportRoute::owner()
                ->with(['stops', 'fees', 'allocations.student.user'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $route
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all routes for dropdown
     */
    public function getRoutesDropdown()
    {
        try {
            $routes = TransportRoute::owner()
                ->with('activeFee')
                ->get()
                ->map(function ($route) {
                    $fee = $route->activeFee;
                    return [
                        'id' => $route->id,
                        'name' => $route->name,
                        'start_point' => $route->start_point,
                        'end_point' => $route->end_point,
                        'fee_amount' => $fee ? $fee->amount : 0,
                        'billing_cycle' => $fee ? $fee->billing_cycle : 'monthly',
                        'text' => $route->name . ' (' . $route->start_point . ' - ' . $route->end_point . ')' .
                                  ($fee ? ' - Fee: ' . number_format($fee->amount, 2) . '/' . $fee->billing_cycle : ' - No fee set')
                    ];
                });

            return response()->json($routes);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
