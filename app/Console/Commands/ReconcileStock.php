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
            
            $this->info('🔍 Finding products with stock discrepancies...');
            
            // Get all products first
            $products = DB::select("
                SELECT DISTINCT p.id, p.product_name, p.sku
                FROM products p
                JOIN batches b ON p.id = b.product_id
                JOIN location_batches lb ON b.id = lb.batch_id
                WHERE lb.qty > 0
                ORDER BY p.id
            ");
            
            if (empty($products)) {
                $this->info('✅ No products found with location batches!');
                DB::rollBack();
                return 0;
            }
            
            $this->info('Checking ' . count($products) . ' products for stock discrepancies...');
            $this->newLine();
            
            $totalAdjustments = 0;
            $fixedProducts = 0;
            $productsNeedingFix = [];
            
            $progressBar = $this->output->createProgressBar(count($products));
            $progressBar->start();
            
            // Check each product for discrepancies
            foreach ($products as $product) {
                // Get actual stock from location_batches
                $actualStockResult = DB::select("
                    SELECT SUM(lb.qty) as total_actual_stock
                    FROM location_batches lb
                    JOIN batches b ON lb.batch_id = b.id
                    WHERE b.product_id = ?
                ", [$product->id]);
                
                $actualStock = $actualStockResult[0]->total_actual_stock ?? 0;
                
                // Get calculated stock from stock_histories
                $calculatedStockResult = DB::select("
                    SELECT 
                        COALESCE(SUM(
                            CASE 
                                WHEN sh.stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'sale_reversal', 'transfer_in', 'adjustment') 
                                THEN sh.quantity 
                                ELSE 0 
                            END
                        ), 0) as total_in,
                        COALESCE(SUM(
                            CASE 
                                WHEN sh.stock_type IN ('sale', 'purchase_return', 'purchase_return_reversal', 'transfer_out') 
                                THEN ABS(sh.quantity) 
                                ELSE 0 
                            END
                        ), 0) as total_out
                    FROM stock_histories sh
                    JOIN location_batches lb ON sh.loc_batch_id = lb.id
                    JOIN batches b ON lb.batch_id = b.id
                    WHERE b.product_id = ?
                ", [$product->id]);
                
                $totalIn = $calculatedStockResult[0]->total_in ?? 0;
                $totalOut = $calculatedStockResult[0]->total_out ?? 0;
                $calculatedStock = $totalIn - $totalOut;
                
                // Check if there's a discrepancy and calculated stock is negative
                if ($actualStock > 0 && $calculatedStock < 0) {
                    $discrepancy = $actualStock - $calculatedStock;
                    $productsNeedingFix[] = [
                        'id' => $product->id,
                        'name' => $product->product_name,
                        'sku' => $product->sku,
                        'actual' => $actualStock,
                        'calculated' => $calculatedStock,
                        'discrepancy' => $discrepancy
                    ];
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine(2);
            
            if (empty($productsNeedingFix)) {
                $this->info('✅ No products found with negative stock issues!');
                DB::rollBack();
                return 0;
            }
            
            $this->info('Found ' . count($productsNeedingFix) . ' products with negative stock issues:');
            $this->newLine();
            
            // Process each product that needs fixing
            foreach ($productsNeedingFix as $productData) {
                $this->line("Fixing: {$productData['name']} (ID: {$productData['id']})");
                $this->line("  Actual: {$productData['actual']}, Calculated: {$productData['calculated']}, Need: +{$productData['discrepancy']}");
                
                // Get location batches for this product with their individual discrepancies
                $locationBatches = DB::select("
                    SELECT 
                        lb.id as loc_batch_id,
                        lb.qty as actual_qty,
                        COALESCE(SUM(
                            CASE 
                                WHEN sh.stock_type IN ('opening_stock', 'purchase', 'sales_return_with_bill', 'sales_return_without_bill', 'sale_reversal', 'transfer_in', 'adjustment') 
                                THEN sh.quantity 
                                ELSE 0 
                            END
                        ), 0) as total_in,
                        COALESCE(SUM(
                            CASE 
                                WHEN sh.stock_type IN ('sale', 'purchase_return', 'purchase_return_reversal', 'transfer_out') 
                                THEN ABS(sh.quantity) 
                                ELSE 0 
                            END
                        ), 0) as total_out
                    FROM location_batches lb
                    JOIN batches b ON lb.batch_id = b.id
                    LEFT JOIN stock_histories sh ON sh.loc_batch_id = lb.id
                    WHERE b.product_id = ?
                    GROUP BY lb.id, lb.qty
                    ORDER BY lb.id
                ", [$productData['id']]);
                
                $productAdjustments = 0;
                
                foreach ($locationBatches as $batch) {
                    $batchCalculated = $batch->total_in - $batch->total_out;
                    $batchActual = floatval($batch->actual_qty);
                    $batchDiscrepancy = $batchActual - $batchCalculated;
                    
                    if (abs($batchDiscrepancy) > 0.001) {
                        // Check if adjustment already exists
                        $existingAdjustments = DB::select("
                            SELECT COUNT(*) as count
                            FROM stock_histories
                            WHERE loc_batch_id = ? AND stock_type = 'adjustment'
                        ", [$batch->loc_batch_id]);
                        
                        if ($existingAdjustments[0]->count == 0) {
                            // Create adjustment entry
                            DB::insert("
                                INSERT INTO stock_histories (loc_batch_id, quantity, stock_type, created_at, updated_at)
                                VALUES (?, ?, 'adjustment', NOW(), NOW())
                            ", [$batch->loc_batch_id, $batchDiscrepancy]);
                            
                            $this->line("    ✅ Added adjustment: {$batchDiscrepancy} for loc_batch_id {$batch->loc_batch_id}");
                            $productAdjustments += $batchDiscrepancy;
                        } else {
                            $this->line("    ⚠️  Adjustment already exists for loc_batch_id {$batch->loc_batch_id}");
                        }
                    }
                }
                
                if ($productAdjustments > 0) {
                    $totalAdjustments += $productAdjustments;
                    $fixedProducts++;
                }
                
                $this->newLine();
            }
            
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