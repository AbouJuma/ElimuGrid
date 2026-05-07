<?php

namespace App\Services;

use App\Models\TransportAllocation;
use App\Models\TransportFee;
use App\Models\TransportFeeCharge;
use App\Models\Students;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransportFeeService
{
    /**
     * Auto-generate transport fees for active allocations
     * Mode A: Automatic charging
     */
    public function generateFeesForPeriod(string $period = null, int $routeId = null): array
    {
        $period = $period ?? $this->getCurrentPeriod();
        $results = [
            'success' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            DB::beginTransaction();

            $query = TransportAllocation::with(['route.activeFee', 'student.user'])
                ->where('status', 'active')
                ->where('auto_charge', true)
                ->where(function ($q) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', now());
                });

            if ($routeId) {
                $query->where('route_id', $routeId);
            }

            $allocations = $query->owner()->get();

            foreach ($allocations as $allocation) {
                try {
                    // Skip if already charged for this period
                    if ($allocation->isChargedForCurrentPeriod()) {
                        $results['skipped']++;
                        continue;
                    }

                    $fee = $allocation->current_fee;
                    if (!$fee) {
                        $results['skipped']++;
                        continue;
                    }

                    // Calculate due date based on billing cycle
                    $dueDate = $this->calculateDueDate($fee->billing_cycle, $period);

                    TransportFeeCharge::create([
                        'allocation_id' => $allocation->id,
                        'student_id' => $allocation->student_id,
                        'route_id' => $allocation->route_id,
                        'transport_fee_id' => $fee->id,
                        'amount' => $fee->amount,
                        'period' => $period,
                        'due_date' => $dueDate,
                        'status' => 'pending',
                        'school_id' => Auth::user()->school_id
                    ]);

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Allocation {$allocation->id}: " . $e->getMessage();
                    Log::error("Transport fee generation failed for allocation {$allocation->id}: " . $e->getMessage());
                }
            }

            DB::commit();
            return $results;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Transport fee batch generation failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate fee for a single allocation (when assigning transport)
     */
    public function generateFeeForAllocation(TransportAllocation $allocation): ?TransportFeeCharge
    {
        try {
            // Don't auto-generate if disabled
            if (!$allocation->auto_charge) {
                return null;
            }

            // Check if already charged for current period
            if ($allocation->isChargedForCurrentPeriod()) {
                return null;
            }

            $fee = $allocation->current_fee;
            if (!$fee) {
                return null;
            }

            $period = $allocation->getCurrentPeriod();
            $dueDate = $this->calculateDueDate($fee->billing_cycle, $period);

            return TransportFeeCharge::create([
                'allocation_id' => $allocation->id,
                'student_id' => $allocation->student_id,
                'route_id' => $allocation->route_id,
                'transport_fee_id' => $fee->id,
                'amount' => $fee->amount,
                'period' => $period,
                'due_date' => $dueDate,
                'status' => 'pending',
                'school_id' => $allocation->school_id
            ]);

        } catch (\Exception $e) {
            Log::error("Single transport fee generation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Stop billing for allocation (when student leaves transport)
     */
    public function stopBilling(TransportAllocation $allocation): void
    {
        // Cancel pending charges for future periods
        TransportFeeCharge::where('allocation_id', $allocation->id)
            ->where('status', 'pending')
            ->where('period', '>', $this->getCurrentPeriod())
            ->update(['status' => 'cancelled']);
    }

    /**
     * Handle route change - stop old billing, apply new fee
     */
    public function handleRouteChange(TransportAllocation $allocation, int $newRouteId, int $newFeeId = null): void
    {
        DB::transaction(function () use ($allocation, $newRouteId, $newFeeId) {
            // Stop future billing on old route
            $this->stopBilling($allocation);

            // Get new fee
            $newFee = $newFeeId 
                ? TransportFee::find($newFeeId) 
                : TransportRoute::find($newRouteId)?->activeFee;

            if ($newFee) {
                // Generate new fee for current period with new amount
                $period = $allocation->getCurrentPeriod();
                $dueDate = $this->calculateDueDate($newFee->billing_cycle, $period);

                TransportFeeCharge::create([
                    'allocation_id' => $allocation->id,
                    'student_id' => $allocation->student_id,
                    'route_id' => $newRouteId,
                    'transport_fee_id' => $newFee->id,
                    'amount' => $newFee->amount,
                    'period' => $period,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                    'school_id' => $allocation->school_id,
                    'notes' => 'Route change fee'
                ]);
            }
        });
    }

    /**
     * Get current period string
     */
    public function getCurrentPeriod(): string
    {
        return now()->format('Y-m');
    }

    /**
     * Calculate due date based on billing cycle
     */
    private function calculateDueDate(string $billingCycle, string $period): string
    {
        return match($billingCycle) {
            'monthly' => Carbon::parse($period . '-01')->addDays(10)->format('Y-m-d'),
            'term' => Carbon::parse($period)->endOfQuarter()->format('Y-m-d'),
            'yearly' => Carbon::parse($period . '-12-31')->format('Y-m-d'),
            default => now()->addDays(10)->format('Y-m-d'),
        };
    }

    /**
     * Get billing statistics for reports
     */
    public function getBillingStats(int $routeId = null, string $period = null): array
    {
        $query = TransportFeeCharge::owner();

        if ($routeId) {
            $query->where('route_id', $routeId);
        }

        if ($period) {
            $query->where('period', $period);
        }

        return [
            'total_charges' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('amount'),
            'paid_amount' => (clone $query)->where('status', 'paid')->sum('paid_amount'),
            'pending_amount' => (clone $query)->where('status', 'pending')->sum('amount'),
            'overdue_count' => (clone $query)->where('status', 'pending')->where('due_date', '<', now())->count(),
            'overdue_amount' => (clone $query)->where('status', 'pending')->where('due_date', '<', now())->sum('amount'),
        ];
    }

    /**
     * Link transport fee to existing invoice
     */
    public function linkToInvoice(int $chargeId, int $invoiceId): bool
    {
        try {
            $charge = TransportFeeCharge::find($chargeId);
            if ($charge) {
                $charge->update(['invoice_id' => $invoiceId]);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to link transport fee to invoice: " . $e->getMessage());
            return false;
        }
    }
}
