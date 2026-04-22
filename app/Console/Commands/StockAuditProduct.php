<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\LocationBatch;
use App\Models\StockHistory;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockAuditProduct extends Command
{
    protected $signature = 'stock:audit
                            {productId : Product ID to audit}
                            {--location= : Limit to a location_id}
                            {--batch= : Limit to a batch_id}
                            {--limit=5000 : Max stock history rows to print per location batch}
                            {--only-negative : Print only rows where running balance goes below 0}';

    protected $description = 'Audit stock movements for a product and explain negative balances (source of truth: location_batches)';

    public function handle(): int
    {
        $productId = (int) $this->argument('productId');
        $locationId = $this->option('location') !== null ? (int) $this->option('location') : null;
        $batchIdOpt = $this->option('batch') !== null ? (int) $this->option('batch') : null;
        $limit = max(1, (int) $this->option('limit'));
        $onlyNegative = (bool) $this->option('only-negative');

        $this->line("Product ID: <info>{$productId}</info>");
        if ($locationId) $this->line("Location ID filter: <info>{$locationId}</info>");
        if ($batchIdOpt) $this->line("Batch ID filter: <info>{$batchIdOpt}</info>");
        $this->newLine();

        $batches = Batch::query()
            ->where('product_id', $productId)
            ->when($batchIdOpt, fn (Builder $q) => $q->where('id', $batchIdOpt))
            ->orderBy('id')
            ->get(['id', 'batch_no', 'qty', 'free_qty', 'created_at']);

        if ($batches->isEmpty()) {
            $this->error('No batches found for this product.');
            return self::FAILURE;
        }

        $batchIds = $batches->pluck('id')->all();

        $locationBatches = LocationBatch::query()
            ->whereIn('batch_id', $batchIds)
            ->when($locationId, fn (Builder $q) => $q->where('location_id', $locationId))
            ->orderBy('location_id')
            ->orderBy('batch_id')
            ->get(['id', 'batch_id', 'location_id', 'qty', 'free_qty', 'created_at', 'updated_at']);

        if ($locationBatches->isEmpty()) {
            $this->warn('No location_batches found for this product with the given filters.');
            return self::SUCCESS;
        }

        $this->info('Batches (header snapshot)');
        $this->table(
            ['batch_id', 'batch_no', 'batches.qty', 'batches.free_qty', 'created_at'],
            $batches->map(fn ($b) => [
                $b->id,
                $b->batch_no,
                (float) ($b->qty ?? 0),
                (float) ($b->free_qty ?? 0),
                (string) $b->created_at,
            ])->all()
        );

        $this->newLine();
        $this->info('Location Batches (source of truth on-hand)');
        $this->table(
            ['loc_batch_id', 'batch_id', 'location_id', 'lb.qty', 'lb.free_qty', 'updated_at'],
            $locationBatches->map(fn ($lb) => [
                $lb->id,
                $lb->batch_id,
                $lb->location_id,
                (float) ($lb->qty ?? 0),
                (float) ($lb->free_qty ?? 0),
                (string) $lb->updated_at,
            ])->all()
        );

        $this->newLine();

        $hasReferenceCols = Schema::hasColumn('stock_histories', 'reference_id') && Schema::hasColumn('stock_histories', 'reference_type');

        foreach ($locationBatches as $lb) {
            $batch = $batches->firstWhere('id', $lb->batch_id);
            $this->line(str_repeat('-', 90));
            $this->line("LocationBatch <info>{$lb->id}</info> | batch_id <info>{$lb->batch_id}</info> ({$batch?->batch_no}) | location_id <info>{$lb->location_id}</info>");
            $this->line("Current lb.qty/free_qty: <info>" . (float) ($lb->qty ?? 0) . '</info> / <info>' . (float) ($lb->free_qty ?? 0) . '</info>');
            $this->newLine();

            $history = StockHistory::query()
                ->where('loc_batch_id', $lb->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->limit($limit)
                ->get();

            if ($history->isEmpty()) {
                $this->warn('No stock_histories for this location batch.');
                $this->newLine();
                continue;
            }

            $running = 0.0;
            $rows = [];

            foreach ($history as $h) {
                $raw = (float) ($h->quantity ?? 0);
                $effect = $this->movementEffect((string) $h->stock_type, $raw);
                $running = round($running + $effect, 4);

                $isNeg = $running < -0.0001;
                if ($onlyNegative && !$isNeg) {
                    continue;
                }

                $rows[] = [
                    'id' => $h->id,
                    'at' => (string) $h->created_at,
                    'type' => (string) $h->stock_type,
                    'raw_qty' => $raw,
                    'effect_qty' => $effect,
                    'running' => $running,
                    'note' => $this->inferNote($productId, $lb->location_id, $lb->batch_id, $h),
                ];
            }

            $this->line('Running balance is computed from stock_histories with type-aware transfer direction.');
            $this->line('Note: This is an audit view; authoritative on-hand is location_batches.');
            $this->newLine();

            $headers = ['id', 'at', 'type', 'raw_qty', 'effect_qty', 'running', 'note'];
            if ($hasReferenceCols) {
                $headers[] = 'ref';
                $rows = array_map(function ($r) use ($history) {
                    return $r;
                }, $rows);
            }

            // Add reference columns only if present.
            if ($hasReferenceCols) {
                $rows = array_map(function ($r) use ($history) {
                    // placeholder; we will enrich below by refetching in a keyed lookup for performance
                    return $r;
                }, $rows);
            }

            // Enrich refs efficiently when available.
            if ($hasReferenceCols && !empty($rows)) {
                $ids = array_column($rows, 'id');
                $refLookup = DB::table('stock_histories')
                    ->whereIn('id', $ids)
                    ->select(['id', 'reference_type', 'reference_id'])
                    ->get()
                    ->keyBy('id');

                foreach ($rows as &$r) {
                    $ref = $refLookup->get($r['id']);
                    $r['ref'] = $ref ? (($ref->reference_type ?? '') . ':' . ($ref->reference_id ?? '')) : '';
                }
                unset($r);
            }

            $this->table($headers, $rows);

            $this->newLine();
            $this->line("End running balance: <info>{$running}</info> | Current location_batches qty/free: <info>" . (float) ($lb->qty ?? 0) . '</info> / <info>' . (float) ($lb->free_qty ?? 0) . '</info>');

            if ($running < -0.0001) {
                $this->warn('Audit running balance is negative for this location batch. Investigate the first row where running dropped below 0.');
            }
            if ((float) ($lb->qty ?? 0) < -0.0001) {
                $this->error('location_batches.qty is negative (this is why UI shows negative stock).');
            }

            $this->newLine();
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function movementEffect(string $type, float $rawQty): float
    {
        $abs = abs($rawQty);

        // Transfer convention in this codebase often stores positive qty for both directions,
        // so we infer direction from stock_type.
        if ($type === StockHistory::STOCK_TYPE_TRANSFER_OUT) {
            return -$abs;
        }
        if ($type === StockHistory::STOCK_TYPE_TRANSFER_IN) {
            return +$abs;
        }

        // For all other types, the quantity already carries the direction in most flows.
        return $rawQty;
    }

    private function inferNote(int $productId, int $locationId, int $batchId, StockHistory $h): string
    {
        $type = (string) ($h->stock_type ?? '');
        $absQty = abs((float) ($h->quantity ?? 0));
        $time = $h->created_at;

        // Try to match a sale row (sales_products + sales) near the same timestamp and qty.
        if (in_array($type, [StockHistory::STOCK_TYPE_SALE, StockHistory::STOCK_TYPE_SALE_REVERSAL, StockHistory::STOCK_TYPE_SALE_ORDER, StockHistory::STOCK_TYPE_SALE_ORDER_REVERSAL], true)) {
            $saleRow = DB::table('sales_products as sp')
                ->join('sales as s', 's.id', '=', 'sp.sale_id')
                ->where('sp.product_id', $productId)
                ->where('sp.location_id', $locationId)
                ->where('sp.batch_id', $batchId)
                ->whereRaw('ABS(sp.quantity + COALESCE(sp.free_quantity,0)) = ?', [$absQty])
                ->whereBetween('sp.created_at', [$time->copy()->subMinutes(10), $time->copy()->addMinutes(10)])
                ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, sp.created_at, ?)) asc', [$time->toDateTimeString()])
                ->select(['s.id as sale_id', 's.invoice_no', 's.order_number', 's.status', 's.transaction_type'])
                ->first();

            if ($saleRow) {
                $label = $saleRow->invoice_no ?: ($saleRow->order_number ?: ('sale:' . $saleRow->sale_id));
                return "sale {$label} ({$saleRow->status}, {$saleRow->transaction_type})";
            }
        }

        // Try to match a sales return product near timestamp.
        if (in_array($type, [StockHistory::STOCK_TYPE_SALE_RETURN_WITH_BILL, StockHistory::STOCK_TYPE_SALE_RETURN_WITHOUT_BILL], true)) {
            $sr = DB::table('sales_return_products as srp')
                ->join('sales_returns as sr', 'sr.id', '=', 'srp.sales_return_id')
                ->where('srp.product_id', $productId)
                ->where('srp.location_id', $locationId)
                ->where('srp.batch_id', $batchId)
                ->whereRaw('ABS(srp.quantity + COALESCE(srp.free_quantity,0)) = ?', [$absQty])
                ->whereBetween('srp.created_at', [$time->copy()->subMinutes(10), $time->copy()->addMinutes(10)])
                ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, srp.created_at, ?)) asc', [$time->toDateTimeString()])
                ->select(['sr.id as return_id'])
                ->first();
            if ($sr) return "sales_return id {$sr->return_id}";
        }

        // Purchase / purchase return matching is schema-dependent in this repo (purchase_return_products uses batch_no as batch_id).
        if ($type === StockHistory::STOCK_TYPE_PURCHASE) {
            try {
                $pp = DB::table('purchase_products as pp')
                    ->join('purchases as p', 'p.id', '=', 'pp.purchase_id')
                    ->where('pp.product_id', $productId)
                    ->where('pp.location_id', $locationId)
                    ->where('pp.batch_id', $batchId)
                    ->whereRaw('ABS(pp.quantity + COALESCE(pp.free_quantity,0)) = ?', [$absQty])
                    ->whereBetween('pp.created_at', [$time->copy()->subMinutes(10), $time->copy()->addMinutes(10)])
                    ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, pp.created_at, ?)) asc', [$time->toDateTimeString()])
                    ->select(['p.id as purchase_id', 'p.ref_no'])
                    ->first();

                if ($pp) {
                    $label = $pp->ref_no ? "({$pp->ref_no})" : '';
                    return "purchase id {$pp->purchase_id} {$label}";
                }
            } catch (\Throwable $e) {
                // Fall through to generic note (schema differences).
            }

            return 'purchase movement (check purchases/purchase_products around this time)';
        }
        if (in_array($type, [StockHistory::STOCK_TYPE_PURCHASE_RETURN, StockHistory::STOCK_TYPE_PURCHASE_RETURN_REVERSAL], true)) {
            try {
                $prp = DB::table('purchase_return_products as prp')
                    ->join('purchase_returns as pr', 'pr.id', '=', 'prp.purchase_return_id')
                    ->where('prp.product_id', $productId)
                    ->whereRaw('prp.batch_no = ?', [$batchId]) // legacy: batch_no stores batches.id
                    ->whereRaw('ABS(prp.quantity + COALESCE(prp.free_quantity,0)) = ?', [$absQty])
                    ->whereBetween('prp.created_at', [$time->copy()->subMinutes(10), $time->copy()->addMinutes(10)])
                    ->orderByRaw('ABS(TIMESTAMPDIFF(SECOND, prp.created_at, ?)) asc', [$time->toDateTimeString()])
                    ->select(['pr.id as purchase_return_id'])
                    ->first();
                if ($prp) return "purchase_return id {$prp->purchase_return_id}";
            } catch (\Throwable $e) {
                // ignore
            }

            return 'purchase_return movement (check purchase_returns around this time)';
        }

        return '';
    }
}

