<?php

namespace App\Http\Controllers;

use App\Models\TransportFeeCharge;
use App\Models\TransportRoute;
use App\Services\ResponseService;
use App\Services\TransportFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransportReportController extends Controller
{
    protected TransportFeeService $feeService;

    public function __construct(TransportFeeService $feeService)
    {
        $this->feeService = $feeService;
    }

    /**
     * Fee collection report
     */
    public function feeCollection(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-report-view');

        $routes = TransportRoute::owner()->orderBy('name')->get();
        
        return response(view('transport.reports.fee_collection', compact('routes')));
    }

    /**
     * Get fee collection data (AJAX)
     */
    public function getFeeCollectionData(Request $request)
    {
        try {
            $routeId = $request->get('route_id');
            $period = $request->get('period', now()->format('Y-m'));
            $status = $request->get('status');

            $query = DB::connection('school')->table('transport_fee_charges as tfc')
                ->leftJoin('transport_allocations as ta', 'tfc.allocation_id', '=', 'ta.id')
                ->leftJoin('students as s', 'tfc.student_id', '=', 's.id')
                ->leftJoin('users as u', 's.user_id', '=', 'u.id')
                ->leftJoin('classes as cs', 's.class_id', '=', 'cs.id')
                ->leftJoin('transport_routes as tr', 'tfc.route_id', '=', 'tr.id')
                ->whereNull('tfc.deleted_at')
                ->where('tfc.school_id', Session::get('auth_school_id')) // Use custom session
                ->select(
                    'tfc.*',
                    'u.first_name',
                    'u.last_name',
                    'cs.name as class_name',
                    'tr.name as route_name'
                );

            if ($routeId) {
                $query->where('tfc.route_id', $routeId);
            }

            if ($period) {
                $query->where('tfc.period', $period);
            }

            if ($status) {
                $query->where('tfc.status', $status);
            }

            $total = $query->count();

            $sort = $request->get('sort', 'tfc.due_date');
            $order = $request->get('order', 'desc');
            $query->orderBy($sort, $order);

            $offset = $request->get('offset', 0);
            $limit = $request->get('limit');
            if ($limit) {
                $query->skip($offset)->take($limit);
            }

            $charges = $query->get()->map(function ($charge) {
                $charge->student_name = $charge->first_name . ' ' . $charge->last_name;
                $charge->balance = $charge->amount - $charge->paid_amount;
                $charge->is_overdue = $charge->status === 'pending' && $charge->due_date < now();
                return $charge;
            });

            return response()->json([
                'total' => $total,
                'rows' => $charges
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Route revenue report
     */
    public function routeRevenue(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-report-view');

        return response(view('transport.reports.route_revenue'));
    }

    /**
     * Get route revenue data
     */
    public function getRouteRevenueData(Request $request)
    {
        try {
            $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));

            $routes = TransportRoute::owner()->get();

            $report = $routes->map(function ($route) use ($startDate, $endDate) {
                $query = TransportFeeCharge::owner()
                    ->where('route_id', $route->id)
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

                $stats = [
                    'route_id' => $route->id,
                    'route_name' => $route->name,
                    'start_point' => $route->start_point,
                    'end_point' => $route->end_point,
                    'total_students' => TransportFeeCharge::owner()
                        ->where('route_id', $route->id)
                        ->distinct('student_id')
                        ->count('student_id'),
                    'total_charges' => (clone $query)->count(),
                    'total_amount' => (clone $query)->sum('amount'),
                    'paid_amount' => (clone $query)->where('status', 'paid')->sum('paid_amount'),
                    'pending_amount' => (clone $query)->where('status', 'pending')->sum('amount'),
                    'overdue_amount' => (clone $query)
                        ->where('status', 'pending')
                        ->where('due_date', '<', now())
                        ->sum('amount'),
                ];

                $stats['collection_rate'] = $stats['total_amount'] > 0 
                    ? round(($stats['paid_amount'] / $stats['total_amount']) * 100, 2) 
                    : 0;

                return $stats;
            });

            return response()->json([
                'total' => $report->count(),
                'rows' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Unpaid fees report
     */
    public function unpaidFees(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-report-view');

        $routes = TransportRoute::owner()->orderBy('name')->get();
        $classes = app('App\Repositories\ClassSchool\ClassSchoolInterface')->builder()->orderBy('name')->get();
        
        return response(view('transport.reports.unpaid_fees', compact('routes', 'classes')));
    }

    /**
     * Get unpaid fees data
     */
    public function getUnpaidFeesData(Request $request)
    {
        try {
            $routeId = $request->get('route_id');
            $classId = $request->get('class_id');

            $query = DB::connection('school')->table('transport_fee_charges as tfc')
                ->leftJoin('transport_allocations as ta', 'tfc.allocation_id', '=', 'ta.id')
                ->leftJoin('students as s', 'tfc.student_id', '=', 's.id')
                ->leftJoin('users as u', 's.user_id', '=', 'u.id')
                ->leftJoin('classes as cs', 's.class_id', '=', 'cs.id')
                ->leftJoin('class_sections as csec', 's.class_section_id', '=', 'csec.id')
                ->leftJoin('transport_routes as tr', 'tfc.route_id', '=', 'tr.id')
                ->where('tfc.status', 'pending')
                ->whereNull('tfc.deleted_at')
                ->where('tfc.school_id', Session::get('auth_school_id')) // Use custom session
                ->select(
                    'tfc.*',
                    'u.first_name',
                    'u.last_name',
                    'u.email',
                    'cs.name as class_name',
                    'tr.name as route_name'
                );

            if ($routeId) {
                $query->where('tfc.route_id', $routeId);
            }

            if ($classId) {
                $query->where('csec.class_id', $classId);
            }

            $total = $query->count();

            $sort = $request->get('sort', 'tfc.due_date');
            $order = $request->get('order', 'asc');
            $query->orderBy($sort, $order);

            $offset = $request->get('offset', 0);
            $limit = $request->get('limit');
            if ($limit) {
                $query->skip($offset)->take($limit);
            }

            $charges = $query->get()->map(function ($charge) {
                $charge->student_name = $charge->first_name . ' ' . $charge->last_name;
                $charge->days_overdue = now()->diffInDays($charge->due_date);
                $charge->is_overdue = $charge->due_date < now();
                return $charge;
            });

            // Summary statistics
            $summary = [
                'total_unpaid' => $total,
                'total_amount' => $charges->sum('amount'),
                'overdue_count' => $charges->where('is_overdue', true)->count(),
                'overdue_amount' => $charges->where('is_overdue', true)->sum('amount'),
            ];

            return response()->json([
                'total' => $total,
                'rows' => $charges,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Student transport statement
     */
    public function studentStatement(Request $request)
    {
        ResponseService::noFeatureThenRedirect('Transport Management');
        ResponseService::noPermissionThenRedirect('transport-report-view');

        $studentId = $request->get('student_id');
        
        if (!$studentId) {
            return response()->json(['error' => 'Student ID required'], 400);
        }

        try {
            $charges = TransportFeeCharge::owner()
                ->where('student_id', $studentId)
                ->with(['route', 'allocation.stop'])
                ->orderBy('period', 'desc')
                ->get();

            $allocation = TransportAllocation::owner()
                ->where('student_id', $studentId)
                ->where('status', 'active')
                ->with(['route', 'stop'])
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'current_allocation' => $allocation,
                    'charges' => $charges,
                    'summary' => [
                        'total_charged' => $charges->sum('amount'),
                        'total_paid' => $charges->sum('paid_amount'),
                        'total_balance' => $charges->sum('amount') - $charges->sum('paid_amount'),
                        'pending_count' => $charges->where('status', 'pending')->count(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Dashboard stats
     */
    public function dashboardStats()
    {
        try {
            $schoolId = Auth::user()->school_id;

            $stats = [
                'total_routes' => TransportRoute::owner()->count(),
                'total_allocations' => DB::connection('school')->table('transport_allocations')
                    ->where('school_id', $schoolId)
                    ->where('status', 'active')
                    ->count(),
                'monthly_revenue' => TransportFeeCharge::owner()
                    ->where('period', now()->format('Y-m'))
                    ->where('status', 'paid')
                    ->sum('paid_amount'),
                'pending_revenue' => TransportFeeCharge::owner()
                    ->where('status', 'pending')
                    ->sum('amount'),
                'overdue_count' => TransportFeeCharge::owner()
                    ->where('status', 'pending')
                    ->where('due_date', '<', now())
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
