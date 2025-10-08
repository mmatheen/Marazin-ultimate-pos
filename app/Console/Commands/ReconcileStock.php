<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\StockHistory;

class ReconcileStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:reconcile {product_id? : Product ID to reconcile (optional, reconciles all if not provided)} {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile stock discrepancies between location_batches and stock_histories to fix negative stock issues';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $productId = $this->argument('product_id');
        
        $this->info('=== STOCK RECONCILIATION FOR NEGATIVE STOCK FIX ===');
        $this->info('Date: ' . now()->format('Y-m-d H:i:s'));
        $this->info('Environment: ' . config('app.env'));
        $this->info('Database: ' . config('database.connections.mysql.database'));
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('This will add stock adjustment entries to fix negative stock issues. Continue?')) {
                $this->error('Operation cancelled.');
                return 1;
            }
        }
        
        if ($productId) {
            $this->info("🔄 Reconciling Product ID: {$productId}");
            return $this->reconcileProduct($productId);
        } else {
            $this->info("🔄 Reconciling ALL products with negative stock...");
            return $this->reconcileAllProducts();
        }
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
        try {
            DB::beginTransaction();
            
            $this->info('🔍 Finding products with negative calculated stock...');
            
            // Find all products with negative calculated stock
            $productsWithIssues = DB::select("
                SELECT 
                    p.id as product_id,
                    p.product_name,
                    p.sku,
                    SUM(lb.qty) as total_actual_stock,
                    (
                        SELECT COALESCE(
                            SUM(CASE 
                                WHEN sh.stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'sale_reversal', 'transfer_in', 'adjustment') 
                                THEN sh.quantity 
                                ELSE 0 
                            END) - 
                            SUM(CASE 
                                WHEN sh.stock_type IN ('sale', 'purchase_return', 'purchase_return_reversal', 'transfer_out') 
                                THEN ABS(sh.quantity) 
                                ELSE 0 
                            END), 0)
                        FROM stock_histories sh2
                        JOIN location_batches lb2 ON sh2.loc_batch_id = lb2.id
                        JOIN batches b2 ON lb2.batch_id = b2.id
                        WHERE b2.product_id = p.id
                    ) as calculated_stock
                FROM products p
                JOIN batches b ON p.id = b.product_id
                JOIN location_batches lb ON b.id = lb.batch_id
                GROUP BY p.id, p.product_name, p.sku
                HAVING total_actual_stock > 0 AND calculated_stock < 0
                ORDER BY p.id
            ");
            
            if (empty($productsWithIssues)) {
                $this->info('✅ No products found with negative stock issues!');
                DB::rollBack();
                return 0;
            }
            
            $this->info('Found ' . count($productsWithIssues) . ' products with stock discrepancies:');
            $this->newLine();
            
            $totalAdjustments = 0;
            $fixedProducts = 0;
            
            $progressBar = $this->output->createProgressBar(count($productsWithIssues));
            $progressBar->start();
            
            foreach ($productsWithIssues as $product) {
                $discrepancy = $product->total_actual_stock - $product->calculated_stock;
                
                // Get location batches for this product with their individual discrepancies
                $locationBatches = DB::select("
                    SELECT 
                        lb.id as loc_batch_id,
                        lb.location_id,
                        lb.qty as actual_qty,
                        b.batch_no,
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
                    LEFT JOIN stock_histories sh ON sh.loc_batch_id = lb.id
                    WHERE b.product_id = ?
                    GROUP BY lb.id, lb.location_id, lb.qty, b.batch_no
                    ORDER BY lb.id
                ", [$product->product_id]);
                
                $productAdjustments = 0;
                
                foreach ($locationBatches as $batch) {
                    $batchCalculated = $batch->total_in - $batch->total_out;
                    $batchActual = floatval($batch->actual_qty);
                    $batchDiscrepancy = $batchActual - $batchCalculated;
                    
                    if ($batchDiscrepancy != 0) {
                        // Create adjustment entry
                        StockHistory::create([
                            'loc_batch_id' => $batch->loc_batch_id,
                            'quantity' => $batchDiscrepancy,
                            'stock_type' => StockHistory::STOCK_TYPE_ADJUSTMENT,
                        ]);
                        
                        $productAdjustments += $batchDiscrepancy;
                    }
                }
                
                $totalAdjustments += $productAdjustments;
                $fixedProducts++;
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine(2);
            
            DB::commit();
            
            $this->info('🎉 RECONCILIATION COMPLETED SUCCESSFULLY!');
            $this->info("Products fixed: {$fixedProducts}");
            $this->info("Total adjustments added: +{$totalAdjustments}");
            $this->newLine();
            $this->info('✅ You can now refresh your Product Stock History pages.');
            $this->info('✅ Negative stock warnings should be resolved.');
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->error('All changes have been rolled back.');
            
            return 1;
        }
    }
}