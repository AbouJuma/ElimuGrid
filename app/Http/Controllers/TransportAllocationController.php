<?php

namespace App\Http\Controllers;

use App\Models\TransportAllocation;
use App\Models\TransportFeeCharge;
use App\Models\TransportRoute;
use App\Models\TransportStop;
use App\Models\Students;
use App\Services\ResponseService;
use App\Services\TransportFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TransportAllocationController extends Controller
{
    protected TransportFeeService $feeService;

    public function __construct(TransportFeeService $feeService)
    {
        $this->feeService = $feeService;
    }

    /**
     * Display allocations management page
     */
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-allocation-list');

        $routes = TransportRoute::owner()->orderBy('name')->get();
        $classes = app('App\Repositories\ClassSchool\ClassSchoolInterface')->builder()->orderBy('name')->get();

        return response(view('transport.allocations.index', compact('routes', 'classes')));
    }

    /**
     * Get allocations list (AJAX)
     */
    public function getAllocations(Request $request)
    {
        try {
            $query = DB::connection('school')->table('transport_allocations as ta')
                ->leftJoin('students as s', 'ta.student_id', '=', 's.id')
                ->leftJoin('users as u', 's.user_id', '=', 'u.id')
                ->leftJoin('class_schools as cs', 'ta.class_id', '=', 'cs.id')
                ->leftJoin('transport_routes as tr', 'ta.route_id', '=', 'tr.id')
                ->leftJoin('transport_stops as ts', 'ta.stop_id', '=', 'ts.id')
                ->leftJoin('transport_fees as tf', 'ta.transport_fee_id', '=', 'tf.id')
                ->whereNull('ta.deleted_at')
                ->where('ta.school_id', Auth::user()->school_id)
                ->select(
                    'ta.*',
                    'u.first_name',
                    'u.last_name',
                    'u.email',
                    'cs.name as class_name',
                    'tr.name as route_name',
                    'ts.name as stop_name',
                    'tf.amount as fee_amount',
                    'tf.billing_cycle'
                );

            // Apply filters
            if ($request->has('route_id') && $request->route_id) {
                $query->where('ta.route_id', $request->route_id);
            }

            if ($request->has('class_id') && $request->class_id) {
                $query->where('ta.class_id', $request->class_id);
            }

            if ($request->has('status') && $request->status) {
                $query->where('ta.status', $request->status);
            }

            // Get total count before pagination
            $total = $query->count();

            // Sorting
            $sort = $request->get('sort', 'ta.allocation_date');
            $order = $request->get('order', 'desc');
            $query->orderBy($sort, $order);

            // Pagination
            $offset = $request->get('offset', 0);
            $limit = $request->get('limit');
            if ($limit) {
                $query->skip($offset)->take($limit);
            }

            $allocations = $query->get();

            // Enhance with fee status and add offset for row numbering
            $allocations = $allocations->map(function ($allocation) use ($offset) {
                $allocation->student_name = $allocation->first_name . ' ' . $allocation->last_name;
                $allocation->fee_formatted = $allocation->fee_amount ? number_format($allocation->fee_amount, 2) : 'Not set';
                
                // Get current period charge status
                $charge = TransportFeeCharge::where('allocation_id', $allocation->id)
                    ->where('period', now()->format('Y-m'))
                    ->first();
                
                $allocation->current_charge_status = $charge ? $charge->status : 'Not charged';
                $allocation->has_pending_fee = $charge && $charge->status === 'pending';
                $allocation->_offset = (int) $offset;
                
                return $allocation;
            });

            return response()->json([
                'total' => $total,
                'rows' => $allocations
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store new allocation with auto fee generation
     */
    public function store(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-allocation-create');

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|integer',
            'class_id' => 'required|integer',
            'route_id' => 'required|integer',
            'stop_id' => 'required|integer',
            'trip_type' => 'required|in:morning,evening,both',
            'allocation_date' => 'required|date',
            'auto_charge' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();

            // Check if student already has active allocation
            $existingAllocation = TransportAllocation::owner()
                ->where('student_id', $request->student_id)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
                })
                ->first();

            if ($existingAllocation) {
                return response()->json([
                    'error' => 'Student already has an active transport allocation. Please end the existing allocation first.'
                ], 400);
            }

            // Get current fee for the route
            $route = TransportRoute::owner()->with('activeFee')->findOrFail($request->route_id);
            $fee = $route->activeFee;

            // Create allocation
            $allocation = TransportAllocation::create([
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'route_id' => $request->route_id,
                'stop_id' => $request->stop_id,
                'transport_fee_id' => $fee ? $fee->id : null,
                'allocation_date' => $request->allocation_date,
                'trip_type' => $request->trip_type,
                'status' => 'active',
                'auto_charge' => $request->auto_charge ?? true,
                'school_id' => Auth::user()->school_id,
            ]);

            // Auto-generate fee if enabled (Mode A)
            if ($allocation->auto_charge && $fee) {
                $this->feeService->generateFeeForAllocation($allocation);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transport allocated successfully' . ($allocation->auto_charge && $fee ? ' with fee generated' : ''),
                'data' => $allocation->load(['student.user', 'route', 'stop'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store allocation error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => 'Failed to allocate transport: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update allocation (e.g., change route/stop)
     */
    public function update(Request $request, $id)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-allocation-edit');

        $validator = Validator::make($request->all(), [
            'route_id' => 'required|integer',
            'stop_id' => 'required|integer',
            'trip_type' => 'required|in:morning,evening,both',
            'auto_charge' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            DB::beginTransaction();

            $allocation = TransportAllocation::owner()->findOrFail($id);
            $oldRouteId = $allocation->route_id;
            $newRouteId = $request->route_id;

            // Get new fee
            $route = TransportRoute::owner()->with('activeFee')->findOrFail($newRouteId);
            $fee = $route->activeFee;

            // Handle route change with fee adjustment
            if ($oldRouteId != $newRouteId) {
                $this->feeService->handleRouteChange($allocation, $newRouteId, $fee ? $fee->id : null);
            }

            $allocation->update([
                'route_id' => $request->route_id,
                'stop_id' => $request->stop_id,
                'trip_type' => $request->trip_type,
                'transport_fee_id' => $fee ? $fee->id : null,
                'auto_charge' => $request->auto_charge ?? $allocation->auto_charge,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transport allocation updated successfully',
                'data' => $allocation->load(['student.user', 'route', 'stop'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update allocation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * End/Terminate allocation
     */
    public function terminate($id)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-allocation-edit');

        try {
            DB::beginTransaction();

            $allocation = TransportAllocation::owner()->findOrFail($id);

            $allocation->update([
                'status' => 'terminated',
                'end_date' => now(),
            ]);

            // Stop future billing
            $this->feeService->stopBilling($allocation);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transport allocation terminated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to terminate allocation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete allocation
     */
    public function destroy($id)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-allocation-delete');

        try {
            DB::beginTransaction();

            $allocation = TransportAllocation::owner()->findOrFail($id);

            // Cancel pending charges
            TransportFeeCharge::where('allocation_id', $id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            $allocation->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transport allocation deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to delete allocation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Manual fee generation for all active allocations (Mode B)
     */
    public function generateFees(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-fee-generate');

        try {
            $period = $request->get('period', now()->format('Y-m'));
            $routeId = $request->get('route_id');

            $results = $this->feeService->generateFeesForPeriod($period, $routeId);

            return response()->json([
                'success' => true,
                'message' => "Fee generation completed. Success: {$results['success']}, Skipped: {$results['skipped']}, Failed: {$results['failed']}",
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate fees: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mark transport fee as paid
     */
    public function markFeePaid($id)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-fee-collect');

        try {
            $allocation = TransportAllocation::owner()->findOrFail($id);
            
            // Get or create current period charge
            $period = now()->format('Y-m');
            $charge = TransportFeeCharge::where('allocation_id', $id)
                ->where('period', $period)
                ->first();
            
            if (!$charge) {
                // Create a new charge if none exists
                $charge = new TransportFeeCharge([
                    'allocation_id' => $id,
                    'period' => $period,
                    'amount' => $allocation->current_fee ?? 0,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => Auth::id(),
                    'school_id' => Auth::user()->school_id,
                ]);
                $charge->save();
            } else {
                // Update existing charge to paid
                $charge->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => Auth::id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fee marked as paid successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to mark fee as paid: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store new stop
     */
    public function storeStop(Request $request)
    {
        try {
            Log::info('storeStop called', $request->all());
            
            $validator = Validator::make($request->all(), [
                'route_id' => 'required|integer',
                'name' => 'required|string|max:100',
                'pickup_time' => 'nullable',
                'drop_time' => 'nullable',
            ]);
            
            // Manual check if route exists
            $routeExists = TransportRoute::owner()->where('id', $request->route_id)->exists();
            if (!$routeExists) {
                return response()->json(['error' => 'Invalid route selected'], 422);
            }

            if ($validator->fails()) {
                Log::warning('storeStop validation failed: ' . $validator->errors()->first());
                return response()->json(['error' => $validator->errors()->first()], 422);
            }

            $stop = TransportStop::create([
                'route_id' => $request->route_id,
                'name' => $request->name,
                'pickup_time' => $request->pickup_time,
                'drop_time' => $request->drop_time,
                'school_id' => Auth::user()->school_id,
            ]);

            Log::info('storeStop success: created stop #' . $stop->id);
            return response()->json(['success' => true, 'message' => 'Stop added successfully', 'data' => $stop]);
        } catch (\Exception $e) {
            Log::error('storeStop error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    /**
     * Get stops by route
     */
    public function getStopsByRoute(Request $request)
    {
        try {
            $routeId = $request->get('route_id');
            Log::info('getStopsByRoute called with route_id: ' . $routeId);
            
            if (!$routeId) {
                Log::info('No route_id provided, returning empty array');
                return response()->json([]);
            }

            $stops = TransportStop::owner()
                ->where('route_id', $routeId)
                ->orderBy('id')
                ->get();
            
            Log::info('Found ' . $stops->count() . ' stops for route ' . $routeId);
            
            $mappedStops = $stops->map(function ($stop) {
                return [
                    'id' => $stop->id,
                    'name' => $stop->name,
                    'pickup_time' => $stop->pickup_time,
                    'drop_time' => $stop->drop_time,
                    'text' => $stop->name . 
                        ($stop->pickup_time ? ' (Pickup: ' . $stop->pickup_time . ')' : '') .
                        ($stop->drop_time ? ' (Drop: ' . $stop->drop_time . ')' : '')
                ];
            });

            return response()->json($mappedStops);
        } catch (\Exception $e) {
            Log::error('getStopsByRoute error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Get available students (not yet allocated)
     */
    public function getAvailableStudents(Request $request)
    {
        try {
            $classId = $request->get('class_id');
            $search = $request->get('search');

            // Get already allocated student IDs
            $allocatedStudentIds = TransportAllocation::owner()
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
                })
                ->pluck('student_id')
                ->toArray();

            $query = Students::with('user')
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at');
                });
            
            // Only exclude allocated students if there are any
            if (!empty($allocatedStudentIds)) {
                $query->whereNotIn('id', $allocatedStudentIds);
            }

            if ($classId) {
                $query->whereHas('class_section', function ($q) use ($classId) {
                    $q->where('class_id', $classId);
                });
            }

            if ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->whereRaw("concat(first_name,' ',last_name) LIKE '%" . $search . "%'")
                        ->orWhere('first_name', 'LIKE', "%$search%")
                        ->orWhere('last_name', 'LIKE', "%$search%");
                });
            }

            $students = $query->limit(50)->get()->map(function ($student) {
                return [
                    'id' => $student->id,
                    'user_id' => $student->user_id,
                    'first_name' => $student->user->first_name,
                    'last_name' => $student->user->last_name,
                    'full_name' => $student->user->first_name . ' ' . $student->user->last_name,
                    'admission_no' => $student->admission_no,
                    'roll_number' => $student->roll_number,
                ];
            });

            return response()->json(['data' => $students]);
        } catch (\Exception $e) {
            Log::error('Transport getAvailableStudents error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }
}
