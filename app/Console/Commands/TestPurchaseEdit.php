<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\Batch;
use App\Models\LocationBatch;

class TestPurchaseEdit extends Command
{
    protected $signature = 'test:purchase-edit {purchase_ref?}';
    protected $description = 'Test purchase edit functionality';

    public function handle()
    {
        $purchaseRef = $this->argument('purchase_ref') ?? 'PUR-007';
        
        $this->info("🔍 Testing Purchase Edit Functionality for {$purchaseRef}");
        $this->newLine();

        // Find the purchase
        $purchase = Purchase::where('reference_no', $purchaseRef)->first();

        if (!$purchase) {
            $this->error("❌ Purchase {$purchaseRef} not found");
            
            // List recent purchases for reference
            $this->newLine();
            $this->info("📋 Recent purchases:");
            $recentPurchases = Purchase::orderBy('id', 'desc')->take(5)->get(['id', 'reference_no', 'final_total', 'created_at']);
            foreach ($recentPurchases as $p) {
                $this->line("- {$p->reference_no}: Rs {$p->final_total} (ID: {$p->id})");
            }
            return;
        }

        $this->info("✅ Found Purchase: {$purchase->reference_no}");
        $this->line("📊 Current Final Total: Rs {$purchase->final_total}");
        $this->line("📅 Purchase Date: {$purchase->purchase_date}");
        $this->line("🏪 Supplier ID: {$purchase->supplier_id}");
        $this->newLine();

        // Get products in this purchase
        $products = PurchaseProduct::where('purchase_id', $purchase->id)->get();
        $this->info("📦 Products in purchase ({$products->count()}):");

        foreach ($products as $product) {
            $batch = Batch::find($product->batch_id);
            $locationBatches = LocationBatch::where('batch_id', $product->batch_id)->get();
            
            $this->line("  - Product ID: {$product->product_id}");
            $this->line("    Quantity: {$product->quantity}");
            $this->line("    Unit Cost: Rs {$product->unit_cost}");
            $this->line("    Total: Rs {$product->total}");
            $this->line("    Batch ID: {$product->batch_id}");
            
            if ($batch) {
                $this->line("    Batch Qty: {$batch->qty}");
                $this->line("    Batch Retail Price: Rs {$batch->retail_price}");
            }
            
            if ($locationBatches->count() > 0) {
                $this->line("    Location Stock:");
                foreach ($locationBatches as $locBatch) {
                    $this->line("      Location {$locBatch->location_id}: {$locBatch->qty} units");
                }
            }
            $this->newLine();
        }

        $this->info("✨ Test completed. This shows the current state of purchase {$purchaseRef}.");
        $this->warn("To test the edit functionality, you would need to:");
        $this->line("1. Change a product quantity in the UI (e.g., from 10 to 20)");
        $this->line("2. Submit the form");
        $this->line("3. Run this command again to see if stock was correctly updated");
    }
}