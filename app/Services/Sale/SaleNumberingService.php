<?php

namespace App\Services\Sale;

use App\Models\InvoiceCounter;
use App\Models\Location;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleNumberingService
{
    public function generateOrderNumber(int $locationId): string
    {
        return DB::transaction(function () use ($locationId) {
            $location = Location::findOrFail($locationId);
            $prefix = ! empty($location->invoice_prefix) ? strtoupper($location->invoice_prefix) : 'SO';
            $pattern = "{$prefix}-SO-";

            // Check all locations to keep uniqueness stable across scoped queries.
            $lastNumber = Sale::withoutGlobalScopes()->where('order_number', 'like', $pattern . '%')
                ->lockForUpdate()
                ->get(['order_number'])
                ->map(function ($row) use ($pattern) {
                    if (preg_match('/^' . preg_quote($pattern, '/') . '(\d+)$/', $row->order_number, $m)) {
                        return (int) $m[1];
                    }

                    return 0;
                })
                ->max() ?? 0;

            $nextNumber = $lastNumber + 1;
            $orderNumber = $pattern . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            $attempts = 0;
            $maxAttempts = 50;
            while (Sale::withoutGlobalScopes()->where('order_number', $orderNumber)->exists() && $attempts < $maxAttempts) {
                $nextNumber++;
                $orderNumber = $pattern . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException("Could not generate a unique order number after {$maxAttempts} attempts.");
            }

            return $orderNumber;
        });
    }

    public function generateInvoiceNo(int $locationId): string
    {
        return DB::transaction(function () use ($locationId) {
            InvoiceCounter::firstOrCreate(
                ['location_id' => $locationId],
                ['next_invoice_number' => 1]
            );

            $counter = InvoiceCounter::lockForUpdate()
                ->where('location_id', $locationId)
                ->first();

            $location = Location::findOrFail($locationId);
            $prefix = ! empty($location->invoice_prefix) ? strtoupper($location->invoice_prefix) : 'INV';

            // Legacy: "AFX" was a typo — normalize to "AFS".
            if (strtoupper($prefix) === 'AFX') {
                $prefix = 'AFS';
            }

            $invoiceNo = "{$prefix}-" . str_pad($counter->next_invoice_number, 3, '0', STR_PAD_LEFT);

            $attempts = 0;
            $maxAttempts = 50;
            while (Sale::withoutGlobalScopes()->where('invoice_no', $invoiceNo)->exists() && $attempts < $maxAttempts) {
                $counter->next_invoice_number++;
                $invoiceNo = "{$prefix}-" . str_pad($counter->next_invoice_number, 3, '0', STR_PAD_LEFT);
                $attempts++;
            }

            if ($attempts >= $maxAttempts) {
                throw new \RuntimeException("Could not generate a unique invoice number after {$maxAttempts} attempts for location {$locationId}.");
            }

            $counter->next_invoice_number++;
            $counter->save();

            return $invoiceNo;
        });
    }
}
