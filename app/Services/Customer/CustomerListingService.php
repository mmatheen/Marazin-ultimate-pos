<?php

namespace App\Services\Customer;

use App\Helpers\BalanceHelper;
use App\Models\Customer;
use App\Models\SalesRep;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerListingService
{
    /**
     * Build the customer listing payload used by both Web and API controllers.
     */
    public function buildIndexPayload(User $user, ?int $cityId = null): array
    {
        $salesRepAssignments = SalesRep::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['route.cities'])
            ->get();

        $query = Customer::withoutLocationScope()
            ->with('city:id,name')
            ->select([
                'id', 'prefix', 'first_name', 'last_name', 'mobile_no', 'email',
                'address', 'location_id', 'opening_balance', 'credit_limit',
                'city_id', 'customer_type',
            ]);

        if ($cityId !== null) {
            $query->where('city_id', $cityId);
        }

        if ($salesRepAssignments->isNotEmpty()) {
            $this->applySalesRepFilter($query, $salesRepAssignments);
        }

        $customers = $query->orderBy('first_name')->get();
        $customerIds = $customers->pluck('id')->toArray();

        $balances = BalanceHelper::getBulkCustomerBalances($customerIds);
        $advances = BalanceHelper::getBulkCustomerAdvances($customerIds);

        $salesDues = collect();
        $returnDues = collect();

        if (!empty($customerIds)) {
            $salesDues = DB::table('sales')
                ->whereIn('customer_id', $customerIds)
                ->whereIn('status', ['final', 'suspend'])
                ->select('customer_id', DB::raw('SUM(total_due) as total_sale_due'))
                ->groupBy('customer_id')
                ->pluck('total_sale_due', 'customer_id');

            $returnDues = DB::table('sales_returns')
                ->whereIn('customer_id', $customerIds)
                ->select('customer_id', DB::raw('SUM(total_due) as total_return_due'))
                ->groupBy('customer_id')
                ->pluck('total_return_due', 'customer_id');
        }

        $repInvoiceDues = $salesRepAssignments->isNotEmpty()
            ? BalanceHelper::getBulkSalesRepOpenInvoiceDues($customerIds, (int) $user->id)
            : collect();

        $customers = $customers->map(function ($customer) use ($balances, $advances, $salesDues, $returnDues, $repInvoiceDues) {
            $fullName = trim(
                ($customer->prefix ? $customer->prefix . ' ' : '') .
                $customer->first_name . ' ' .
                ($customer->last_name ?? '')
            );

            $currentBalance = $balances->get($customer->id, (float) $customer->opening_balance);
            $advanceCredit = $advances->get($customer->id, 0);

            $totalSaleDue = (float) ($salesDues->get($customer->id, 0));
            $totalReturnDue = (float) ($returnDues->get($customer->id, 0));

            return [
                'id' => $customer->id,
                'prefix' => $customer->prefix,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'full_name' => $fullName,
                'mobile_no' => $customer->mobile_no,
                'email' => $customer->email,
                'address' => $customer->address,
                'location_id' => $customer->location_id,
                'opening_balance' => (float) $customer->opening_balance,
                'current_balance' => (float) $currentBalance,
                'total_sale_due' => $totalSaleDue,
                'total_return_due' => $totalReturnDue,
                'total_advance_credit' => (float) $advanceCredit,
                'current_due' => (float) max(0, $currentBalance),
                'my_invoice_due' => (float) $repInvoiceDues->get($customer->id, 0.0),
                'city_id' => $customer->city_id,
                'city_name' => $customer->city?->name ?? '',
                'credit_limit' => (float) $customer->credit_limit,
                'customer_type' => $customer->customer_type,
            ];
        });

        return [
            'customers' => $customers,
            'total_customers' => $customers->count(),
            'sales_rep_info' => $salesRepAssignments->isNotEmpty()
                ? $this->getSalesRepInfoFromAssignments($salesRepAssignments)
                : null,
            'show_rep_invoice_due' => $salesRepAssignments->isNotEmpty(),
        ];
    }

    private function applySalesRepFilter(Builder $query, Collection $salesRepAssignments): void
    {
        $allCityIds = [];

        foreach ($salesRepAssignments as $assignment) {
            if ($assignment->route && $assignment->route->cities) {
                $cityIds = $assignment->route->cities->pluck('id')->toArray();
                $allCityIds = array_merge($allCityIds, $cityIds);
            }
        }

        $allCityIds = array_unique($allCityIds);

        if (!empty($allCityIds)) {
            $query->whereIn('city_id', $allCityIds);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    private function getSalesRepInfoFromAssignments(Collection $salesRepAssignments): array
    {
        $allRoutes = [];
        $allCities = [];
        $salesRepIds = [];

        foreach ($salesRepAssignments as $assignment) {
            if ($assignment->route) {
                $allRoutes[] = $assignment->route->name;

                if ($assignment->route->cities) {
                    $cities = $assignment->route->cities->pluck('name')->toArray();
                    $allCities = array_merge($allCities, $cities);
                }
            }

            $salesRepIds[] = $assignment->id;
        }

        $allCities = array_unique($allCities);
        $allRoutes = array_unique($allRoutes);

        return [
            'routes' => $allRoutes,
            'route_names' => implode(', ', $allRoutes),
            'assigned_cities' => array_values($allCities),
            'total_cities' => count($allCities),
            'total_routes' => count($allRoutes),
            'sales_rep_ids' => $salesRepIds,
            'total_assignments' => $salesRepAssignments->count(),
        ];
    }
}
