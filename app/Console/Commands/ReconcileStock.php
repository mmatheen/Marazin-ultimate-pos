<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\StockHistory;

class ReconcileStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:reconcile {product_id? : Product ID to reconcile (optional, reconciles all if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile stock discrepancies between location_batches and stock_histories';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $productId = $this->argument('product_id');
        
        $this->info('🔄 Starting Stock Reconciliation...');
        
        if ($productId) {
            $this->info("Reconciling Product ID: {$productId}");
            $this->reconcileProduct($productId);
        } else {
            $this->info("Reconciling ALL products...");
            $this->reconcileAllProducts();
        }
        
        return Command::SUCCESS;
    }
    
    private function reconcileProduct($productId)
    {
        // Get location batches with discrepancies for the specific product
        $discrepancies = DB::select("
            SELECT 
                lb.id as loc_batch_id,
                lb.qty as actual_qty,
                b.batch_no,
                b.product_id,
                p.product_name,
                l.name as location_name,
                COALESCE(SUM(CASE 
                    WHEN sh.stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'sale_reversal', 'transfer_in', 'adjustment') 
                    THEN sh.quantity 
                    ELSE 0 
                END), 0) as total_in,
                COALESCE(SUM(CASE 
                    WHEN sh.stock_type IN ('sale', 'purchase_return', 'purchase_return_reversal', 'transfer_out') 
                    THEN ABS(sh.quantity) 
                    ELSE 0 
                END), 0) as total_out
            FROM location_batches lb
            JOIN batches b ON lb.batch_id = b.id
            JOIN products p ON b.product_id = p.id
            JOIN locations l ON lb.location_id = l.id
            LEFT JOIN stock_histories sh ON sh.loc_batch_id = lb.id
            WHERE b.product_id = ?
            GROUP BY lb.id
            HAVING ABS(lb.qty - (total_in - total_out)) > 0.001
            ORDER BY lb.id
        ", [$productId]);
        
        if (empty($discrepancies)) {
            $this->info("✅ Product ID {$productId} is already balanced - no adjustments needed.");
            return;
        }
        
        $this->warn("Found " . count($discrepancies) . " location batches with discrepancies:");
        
        $adjustmentCount = 0;
        
        foreach ($discrepancies as $discrepancy) {
            $calculatedStock = $discrepancy->total_in - $discrepancy->total_out;
            $actualStock = $discrepancy->actual_qty;
            $adjustmentNeeded = $actualStock - $calculatedStock;
            
            $this->line("🔍 Location Batch {$discrepancy->loc_batch_id} ({$discrepancy->batch_no}):");
            $this->line("   Product: {$discrepancy->product_name}");
            $this->line("   Location: {$discrepancy->location_name}");
            $this->line("   Actual: {$actualStock} | Calculated: {$calculatedStock} | Adjustment: {$adjustmentNeeded}");
            
            // Create adjustment entry
            try {
                StockHistory::create([
                    'loc_batch_id' => $discrepancy->loc_batch_id,
                    'quantity' => $adjustmentNeeded,
                    'stock_type' => StockHistory::STOCK_TYPE_ADJUSTMENT,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->info("   ✅ Adjustment entry created");
                $adjustmentCount++;
                
            } catch (\Exception $e) {
                $this->error("   ❌ Failed to create adjustment: " . $e->getMessage());
            }
        }
        
        $this->info("🎉 Reconciliation completed! Created {$adjustmentCount} adjustment entries.");
    }
    
    private function reconcileAllProducts()
    {
        // Get all products with discrepancies
        $products = DB::select("
            SELECT DISTINCT b.product_id, p.product_name
            FROM location_batches lb
            JOIN batches b ON lb.batch_id = b.id
            JOIN products p ON b.product_id = p.id
            LEFT JOIN stock_histories sh ON sh.loc_batch_id = lb.id
            GROUP BY b.product_id, lb.id
            HAVING ABS(lb.qty - (
                COALESCE(SUM(CASE 
                    WHEN sh.stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'sale_reversal', 'transfer_in', 'adjustment') 
                    THEN sh.quantity 
                    ELSE 0 
                END), 0) - 
                COALESCE(SUM(CASE 
                    WHEN sh.stock_type IN ('sale', 'purchase_return', 'purchase_return_reversal', 'transfer_out') 
                    THEN ABS(sh.quantity) 
                    ELSE 0 
                END), 0)
            )) > 0.001
            ORDER BY b.product_id
        ");
        
        if (empty($products)) {
            $this->info("✅ All products are balanced - no reconciliation needed!");
            return;
        }
        
        $this->warn("Found " . count($products) . " products with stock discrepancies:");
        
        foreach ($products as $product) {
            $this->line("🔄 Reconciling Product ID {$product->product_id}: {$product->product_name}");
            $this->reconcileProduct($product->product_id);
            $this->line("");
        }
    }
}