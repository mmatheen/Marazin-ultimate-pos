<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitializeInvoiceCounters extends Command
{
    protected $signature = 'counters:init';
    protected $description = 'Initialize invoice counters based on last invoice number per location';

    public function handle()
    {
        // Get all locations
        $locations = DB::table('locations')->get();

        foreach ($locations as $location) {
            $this->info("🔍 Processing Location ID: {$location->id}");

            // Get prefix from locations table
            $prefix = $location->invoice_prefix;

            if (!$prefix) {
                $this->warn("⚠️ No invoice prefix found for Location ID: {$location->id}");
                continue;
            }

            // Find the latest sale for this location with matching prefix
            $latestSale = DB::table('sales')
                ->where('location_id', $location->id)
                ->where('invoice_no', 'like', "$prefix-%")
                ->orderByDesc('id')
                ->first(['invoice_no']);

            $nextNumber = 1;

            if ($latestSale && preg_match("/^{$prefix}-(\d+)/", $latestSale->invoice_no, $matches)) {
                $lastNumber = (int)$matches[1];
                $nextNumber = $lastNumber + 1;
            }

            // Update or insert counter
            DB::table('invoice_counters')->updateOrInsert(
                ['location_id' => $location->id],
                ['next_invoice_number' => $nextNumber]
            );

            $formattedNext = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $this->info("✅ Counter initialized for Location ID: {$location->id} → Next Invoice: {$prefix}-{$formattedNext}");
        }

        $this->info("✅ All invoice counters initialized successfully.");
    }
}
