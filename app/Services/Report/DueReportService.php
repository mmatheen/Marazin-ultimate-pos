<?php
namespace App\Services\Report;

use App\Models\Sale;
use App\Helpers\BalanceHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DueReportService
{
    /** AJAX entry point: return data + summary JSON. */
    public function getDataForDataTables(Request $request): \Illuminate\Http\JsonResponse
    {
        $reportType = $request->input('report_type', 'customer');

        Log::info('Due Report Request', [
            'report_type' => $reportType,
            'customer_id' => $request->customer_id,
            'supplier_id' => $request->supplier_id,
            'location_id' => $request->location_id,
            'user_id'     => $request->user_id,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
        ]);

        $data    = $reportType === 'customer'
            ? $this->getCustomerData($request)
            : $this->getSupplierData($request);

        Log::info('Due Report Data Count', ['count' => count($data)]);

        $summary = $this->calculateSummaryFromData($data, $reportType);

        Log::info('Due Report Summary', $summary);

        return response()->json(['data' => $data, 'summary' => $summary]);
    }

    /** Customer due rows (ledger-based). */
    public function getCustomerData(Request $request): array
    {
        $query = Sale::with(['customer'])
            ->withSum('salesReturns as sales_return_total', 'return_total')
            ->whereIn('payment_status', ['partial', 'due'])
            ->where('total_due', '>', 0)
            ->whereNotNull('customer_id')
            ->where('status', 'final')
            ->where('transaction_type', 'invoice');

        // Narrow to specific customer if filter applied
        if (filled($request->customer_id)) {
            $query->where('customer_id', $request->customer_id);
        }

        if (filled($request->city_id)) {
            $cityId = $request->city_id;
            $query->whereHas('customer', fn($q) => $q->where('city_id', $cityId));
        }
        if (filled($request->location_id)) {
            $query->where('location_id', $request->location_id);
        }
        if (filled($request->user_id)) {
            $query->where('user_id', $request->user_id);
        }

        if (filled($request->date_range_filter)) {
            $days  = (int) $request->date_range_filter;
            $query->whereBetween('sales_date', [
                Carbon::now()->subDays($days)->startOfDay(),
                Carbon::now()->endOfDay(),
            ]);
        } else {
            if (filled($request->start_date)) {
                $query->whereDate('sales_date', '>=', $request->start_date);
            }
            if (filled($request->end_date)) {
                $query->whereDate('sales_date', '<=', $request->end_date);
            }
        }

        $sales = $query->orderBy('sales_date', 'desc')->get();
        if ($sales->isEmpty()) {
            return [];
        }

        // Compute balances only for customers that already passed filters.
        $customerBalances = [];
        $customerIds = $sales->pluck('customer_id')->filter()->unique()->values();
        foreach ($customerIds as $cid) {
            $balance = (float) BalanceHelper::getCustomerBalance((int) $cid);
            if ($balance > 0) {
                $customerBalances[(int) $cid] = $balance;
            }
        }

        $data  = [];

        foreach ($sales as $sale) {
            $customerId    = (int) $sale->customer_id;
            $ledgerBalance = $customerBalances[$customerId] ?? 0.0;
            if ($ledgerBalance <= 0) {
                continue;
            }

            $totalReturns = (float) ($sale->sales_return_total ?? 0);
            $actualDue    = max(0, (float) $sale->total_due - $totalReturns);

            if ($actualDue <= 0) {
                continue;
            }

            $salesDate = Carbon::parse($sale->sales_date);
            $dueDays   = max(0, $salesDate->diffInDays(Carbon::now(), false));

            $data[] = [
                'id'              => $sale->id,
                'customer_id'     => $customerId,
                'invoice_no'      => $sale->invoice_no ?? 'N/A',
                'customer_name'   => $sale->customer ? $sale->customer->full_name : 'N/A',
                'sales_date'      => $salesDate->format('d-M-Y'),
                'ledger_balance'  => $ledgerBalance,
                'final_total'     => $sale->final_total,
                'total_paid'      => $sale->total_paid,
                'original_due'    => $sale->total_due,
                'return_amount'   => $totalReturns,
                'total_due'       => $actualDue,
                'final_due'       => $actualDue,
                'payment_status'  => $sale->payment_status,
                'due_days'        => $dueDays,
                'due_status'      => $this->getDueStatus($dueDays),
            ];
        }

        return $data;
    }

    /** Supplier due rows. */
    public function getSupplierData(Request $request): array
    {
        $query = \App\Models\Purchase::with(['supplier'])
            ->whereIn('payment_status', ['partial', 'due'])
            ->where('total_due', '>', 0)
            ->whereNotNull('supplier_id')
            ->where('status', 'final')
            ->whereNotNull('reference_no');

        if (filled($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if (filled($request->city_id)) {
            $cityId = $request->city_id;
            $query->whereHas('supplier', fn($q) => $q->where('city_id', $cityId));
        }
        if (filled($request->location_id)) {
            $query->where('location_id', $request->location_id);
        }
        if (filled($request->user_id)) {
            $query->where('user_id', $request->user_id);
        }

        if (filled($request->date_range_filter)) {
            $days = (int) $request->date_range_filter;
            $query->whereBetween('purchase_date', [
                Carbon::now()->subDays($days)->startOfDay(),
                Carbon::now()->endOfDay(),
            ]);
        } else {
            if (filled($request->start_date)) {
                $query->whereDate('purchase_date', '>=', $request->start_date);
            }
            if (filled($request->end_date)) {
                $query->whereDate('purchase_date', '<=', $request->end_date);
            }
        }

        if (filled($request->due_days_from)) {
            $query->whereDate('purchase_date', '<=',
                Carbon::now()->subDays((int) $request->due_days_from)->format('Y-m-d'));
        }
        if (filled($request->due_days_to)) {
            $query->whereDate('purchase_date', '>=',
                Carbon::now()->subDays((int) $request->due_days_to)->format('Y-m-d'));
        }

        $purchases = $query->orderBy('purchase_date', 'desc')->get();
        if ($purchases->isEmpty()) {
            return [];
        }

        // Compute supplier balances once and re-use in rows + summary.
        $supplierBalances = [];
        $supplierIds = $purchases->pluck('supplier_id')->filter()->unique()->values();
        foreach ($supplierIds as $sid) {
            $balance = (float) BalanceHelper::getSupplierBalance((int) $sid);
            if ($balance > 0) {
                $supplierBalances[(int) $sid] = $balance;
            }
        }

        $data      = [];

        foreach ($purchases as $purchase) {
            if ($purchase->total_due <= 0 || $purchase->payment_status === 'paid') {
                continue;
            }

            $supplierId    = (int) $purchase->supplier_id;
            $ledgerBalance = $supplierBalances[$supplierId] ?? 0.0;
            if ($ledgerBalance <= 0) {
                continue;
            }

            $purchaseDate = Carbon::parse($purchase->purchase_date);
            $dueDays      = max(0, $purchaseDate->diffInDays(Carbon::now(), false));

            $data[] = [
                'id'               => $purchase->id,
                'supplier_id'      => $supplierId,
                'reference_no'     => $purchase->reference_no ?? 'N/A',
                'supplier_name'    => $purchase->supplier ? $purchase->supplier->full_name : 'N/A',
                'purchase_date'    => $purchaseDate->format('d-M-Y'),
                'ledger_balance'   => $ledgerBalance,
                'final_total'      => $purchase->final_total,
                'total_paid'       => $purchase->total_paid,
                'original_due'     => $purchase->total_due,
                'return_amount'    => 0,
                'total_due'        => $purchase->total_due,
                'final_due'        => $purchase->total_due,
                'payment_status'   => $purchase->payment_status,
                'due_days'         => $dueDays,
                'due_status'       => $this->getDueStatus($dueDays),
            ];
        }

        return $data;
    }

    /**
     * Calculate summary from already-loaded data array.
     * Fix: customer_id / supplier_id are now in each row  no extra DB query needed.
     */
    public function calculateSummaryFromData(array $data, string $reportType): array
    {
        $totalBills    = count($data);
        $totalDue      = 0.0;
        $maxSingleDue  = 0.0;
        $uniqueParties = [];
        $ledgerByParty = [];

        foreach ($data as $row) {
            $totalDue     += $row['total_due'];
            $maxSingleDue  = max($maxSingleDue, $row['final_due'] ?? 0);

            if ($reportType === 'customer') {
                if (!empty($row['customer_id'])) {
                    $partyId = (int) $row['customer_id'];
                    $uniqueParties[$partyId] = true;
                    if (isset($row['ledger_balance'])) {
                        $ledgerByParty[$partyId] = (float) $row['ledger_balance'];
                    }
                }
            } else {
                if (!empty($row['supplier_id'])) {
                    $partyId = (int) $row['supplier_id'];
                    $uniqueParties[$partyId] = true;
                    if (isset($row['ledger_balance'])) {
                        $ledgerByParty[$partyId] = (float) $row['ledger_balance'];
                    }
                }
            }
        }

        $actualOutstanding = array_sum(array_filter($ledgerByParty, fn($v) => $v > 0));
        $finalOutstanding  = $actualOutstanding > 0 ? $actualOutstanding : $totalDue;

        return [
            'total_due'    => $finalOutstanding,
            'total_bills'  => $totalBills,
            'total_parties' => count($uniqueParties),
            'max_single_due' => $maxSingleDue,
            'avg_due_per_bill' => $totalBills > 0 ? $finalOutstanding / $totalBills : 0,
        ];
    }

    /** Bin overdue age into a status label. */
    public function getDueStatus(int $dueDays): string
    {
        $days = abs($dueDays);
        if ($days <= 7)  return 'recent';
        if ($days <= 30) return 'medium';
        if ($days <= 90) return 'old';
        return 'critical';
    }

    /**
     * Alternative summary via direct DB aggregate (used by calculateDueSummary path).
     * Kept for compatibility; controller export methods use calculateSummaryFromData().
     */
    public function calculateSummary(Request $request): array
    {
        $reportType = $request->input('report_type', 'customer');

        if ($reportType === 'customer') {
            $query = Sale::whereIn('payment_status', ['partial', 'due'])
                ->where('total_due', '>', 0)
                ->whereNotNull('customer_id')
                ->where('status', 'final')
                ->where('transaction_type', 'invoice');

            if (filled($request->customer_id)) $query->where('customer_id', $request->customer_id);
            if (filled($request->location_id)) $query->where('location_id', $request->location_id);
            if (filled($request->user_id))     $query->where('user_id', $request->user_id);
            if (filled($request->start_date))  $query->whereDate('sales_date', '>=', $request->start_date);
            if (filled($request->end_date))    $query->whereDate('sales_date', '<=', $request->end_date);

            $totalDue      = $query->sum('total_due');
            $totalBills    = $query->count();
            $totalParties  = $query->distinct('customer_id')->count('customer_id');
        } else {
            $query = \App\Models\Purchase::whereIn('payment_status', ['partial', 'due'])
                ->where('total_due', '>', 0)
                ->whereNotNull('supplier_id');

            if (filled($request->supplier_id)) $query->where('supplier_id', $request->supplier_id);
            if (filled($request->location_id)) $query->where('location_id', $request->location_id);
            if (filled($request->user_id))     $query->where('user_id', $request->user_id);
            if (filled($request->start_date))  $query->whereDate('purchase_date', '>=', $request->start_date);
            if (filled($request->end_date))    $query->whereDate('purchase_date', '<=', $request->end_date);

            $totalDue      = $query->sum('total_due');
            $totalBills    = $query->count();
            $totalParties  = $query->distinct('supplier_id')->count('supplier_id');
        }

        return [
            'total_due'    => $totalDue,
            'total_bills'  => $totalBills,
            'total_parties' => $totalParties,
            'avg_due_per_bill' => $totalBills > 0 ? $totalDue / $totalBills : 0,
        ];
    }
}
