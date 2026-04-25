<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class PruneUnreferencedProductImages extends Command
{
    protected $signature = 'images:prune-products
        {--dry-run : Show what would be deleted, but do not delete}
        {--limit=0 : Max number of deletions to perform (0 = no limit)}
        {--chunk=500 : DB chunk size}
    ';

    protected $description = 'Delete unreferenced product images from public/assets/images/products (safe, DB-driven).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $chunk = max(50, (int) $this->option('chunk'));

        $dir = public_path('assets/images/products');
        if (!is_dir($dir)) {
            $this->warn("Directory not found: {$dir}");
            return Command::SUCCESS;
        }

        $this->info('Scanning referenced product images from database…');

        // Build a set of referenced filenames within products/ folder.
        $referenced = [];

        Product::query()
            ->select(['id', 'product_image'])
            ->whereNotNull('product_image')
            ->where('product_image', '!=', '')
            ->orderBy('id')
            ->chunkById($chunk, function ($products) use (&$referenced) {
                foreach ($products as $p) {
                    $raw = trim((string) $p->getRawOriginal('product_image'));
                    if ($raw === '') continue;

                    $raw = str_replace('\\', '/', ltrim($raw, '/'));
                    $raw = preg_replace('#^assets/images/#', '', $raw);

                    // We only prune within products/ directory.
                    if (str_starts_with($raw, 'products/')) {
                        $file = basename($raw);
                        if ($file !== '') $referenced[$file] = true;
                        continue;
                    }

                    // Legacy: DB may store only filename (assumed root); if the file exists in products/, keep it.
                    $file = basename($raw);
                    if ($file !== '') {
                        $referenced[$file] = true;
                    }
                }
            });

        $this->info('Scanning files in products folder…');
        $files = glob($dir . DIRECTORY_SEPARATOR . '*') ?: [];

        $totalFiles = 0;
        $kept = 0;
        $candidates = 0;
        $deleted = 0;

        foreach ($files as $abs) {
            if (!is_file($abs)) continue;

            $totalFiles++;
            $base = basename($abs);

            // Safety: never delete placeholders / non-product assets, even if present.
            if (in_array($base, ['.gitkeep', 'No Product Image Available.png', 'ARB Logo.png'], true)) {
                $kept++;
                continue;
            }

            if (isset($referenced[$base])) {
                $kept++;
                continue;
            }

            $candidates++;

            $rel = str_replace(public_path(), '', $abs);
            $rel = str_replace('\\', '/', $rel);
            $this->line("UNREFERENCED: {$rel}");

            if ($dryRun) {
                continue;
            }

            if ($limit > 0 && $deleted >= $limit) {
                $this->warn("Delete limit reached ({$limit}). Stopping.");
                break;
            }

            @unlink($abs);
            $deleted++;
        }

        $this->newLine();
        $this->info("Done. total_files={$totalFiles}, kept={$kept}, unreferenced={$candidates}, deleted={$deleted}" . ($dryRun ? ' (dry-run)' : ''));

        return Command::SUCCESS;
    }
}

