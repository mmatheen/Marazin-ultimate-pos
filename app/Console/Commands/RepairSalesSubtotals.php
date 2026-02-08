<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RepairSalesSubtotals extends Command
{
    protected $signature = 'sales:repair-subtotals {--dry-run : Show what would be fixed without making changes} {--update-ledgers : Also update ledger entries}';

    protected $description = 'Recalculate and fix sales subtotals, final_totals, and ledger entries from sales_products';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $updateLedgers = $this->option('update-ledgers');

        $this->info('🔍 Scanning for sales with incorrect calculations...');
        if ($updateLedgers) {
            $this->info('📊 Ledger entries will also be updated');
        }

        $sales = DB::table('sales')->get();
        $fixedCount = 0;
        $ledgerFixedCount = 0;
        $errors = [];

        foreach ($sales as $sale) {
            // Calculate correct subtotal from sales_products
            $correctSubtotal = DB::table('sales_products')
                ->where('sale_id', $sale->id)
                ->sum(DB::raw('quantity * price'));

            // Calculate correct final_total
            $discount = $sale->discount_amount ?? 0;
            if ($sale->discount_type === 'percentage') {
                $discountAmount = ($correctSubtotal * $discount / 100);
            } else {
                $discountAmount = $discount;
            }

            $correctFinalTotal = $correctSubtotal - $discountAmount + ($sale->shipping_charges ?? 0);

            // Calculate correct total_due (using generated column logic)
            $correctTotalDue = $correctFinalTotal - $sale->total_paid;

            // Check if needs fixing
            $needsFix = (abs($sale->subtotal - $correctSubtotal) > 0.01) ||
                       (abs($sale->final_total - $correctFinalTotal) > 0.01);

            if ($needsFix) {
                $subtotalDiff = $correctSubtotal - $sale->subtotal;
                $finalTotalDiff = $correctFinalTotal - $sale->final_total;

                $this->warn("❌ Sale ID {$sale->id} (Invoice: {$sale->invoice_no}):");
                $this->line("   Subtotal: Rs " . number_format($sale->subtotal, 2) . " → Rs " . number_format($correctSubtotal, 2) . " (Diff: Rs " . number_format($subtotalDiff, 2) . ")");
                $this->line("   Discount: Rs " . number_format($discountAmount, 2) . " ({$sale->discount_type})");
                $this->line("   Shipping: Rs " . number_format($sale->shipping_charges ?? 0, 2));
                $this->line("   Final Total: Rs " . number_format($sale->final_total, 2) . " → Rs " . number_format($correctFinalTotal, 2) . " (Diff: Rs " . number_format($finalTotalDiff, 2) . ")");
                $this->line("   Total Due will auto-calculate: Rs " . number_format($correctTotalDue, 2));

                if (!$isDryRun) {
                    try {
                        DB::beginTransaction();

                        // Update sales table
                        DB::table('sales')
                            ->where('id', $sale->id)
                            ->update([
                                'subtotal' => $correctSubtotal,
                                'final_total' => $correctFinalTotal,
                                'updated_at' => now(),
                            ]);

                        // Update ledger entries if requested and sale has ledger entry
                        if ($updateLedgers && $sale->transaction_type === 'invoice' && $sale->customer_id != 1) {
                            $ledgerUpdated = $this->updateLedgerEntry($sale->id, $correctFinalTotal);
                            if ($ledgerUpdated) {
                                $ledgerFixedCount++;
                                $this->line("   📊 Ledger updated");
                            }
                        }

                        DB::commit();
                        $this->info("   ✅ Fixed!");
                        $fixedCount++;
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("   ❌ Error: " . $e->getMessage());
                        $errors[] = "Sale {$sale->id}: " . $e->getMessage();
                    }
                }
            }
        }

        if ($isDryRun) {
            $this->info("\n🔍 Dry run completed. Found issues in {$fixedCount} sales.");
            $this->info("Run without --dry-run to fix these sales.");
            if ($updateLedgers) {
                $this->info("Add --update-ledgers to also fix ledger entries.");
            }
        } else {
            $this->info("\n✅ Repair completed!");
            $this->info("Fixed {$fixedCount} sales records.");
            if ($updateLedgers) {
                $this->info("Updated {$ledgerFixedCount} ledger entries.");
            }

            if (count($errors) > 0) {
                $this->error("\n⚠️  Errors encountered:");
                foreach ($errors as $error) {
                    $this->error("  - {$error}");
                }
            }
        }

        return 0;
    }

    /**
     * Update ledger entry for a sale
     */
    protected function updateLedgerEntry($saleId, $correctFinalTotal)
    {
        try {
            // Get the sale invoice number
            $sale = DB::table('sales')->where('id', $saleId)->first();
            if (!$sale || !$sale->invoice_no) {
                return false;
            }

            // Find the ledger entry for this sale
            $ledgerEntry = DB::table('ledgers')
                ->where('reference_no', $sale->invoice_no)
                ->where('transaction_type', 'sale')
                ->first();

            if ($ledgerEntry) {
                // Update the debit amount (customer owes this amount)
                DB::table('ledgers')
                    ->where('id', $ledgerEntry->id)
                    ->update([
                        'debit' => $correctFinalTotal,
                        'updated_at' => now(),
                    ]);

                Log::info("Ledger entry updated for sale", [
                    'sale_id' => $saleId,
                    'ledger_id' => $ledgerEntry->id,
                    'old_debit' => $ledgerEntry->debit,
                    'new_debit' => $correctFinalTotal
                ]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Failed to update ledger entry", [
                'sale_id' => $saleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
