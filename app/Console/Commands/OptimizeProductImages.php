<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Images\OptimizedImageUploader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeProductImages extends Command
{
    protected $signature = 'images:optimize-products
        {--dry-run : Show what would change, but don\'t write files or DB}
        {--only-missing : Only optimize when current file is missing from products/ folder}
        {--limit=0 : Max number of products to process (0 = no limit)}
        {--chunk=100 : DB chunk size}
    ';

    protected $description = 'Optimize existing product images into /public/assets/images/products and update DB paths.';

    public function handle(OptimizedImageUploader $uploader): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $onlyMissing = (bool) $this->option('only-missing');
        $limit = max(0, (int) $this->option('limit'));
        $chunk = max(10, (int) $this->option('chunk'));

        $processed = 0;
        $changed = 0;
        $skipped = 0;
        $missing = 0;

        $this->info('Starting product image optimization…');
        if ($dryRun) $this->warn('DRY RUN: no files/DB will be modified.');

        $query = Product::query()
            ->select(['id', 'product_image'])
            ->whereNotNull('product_image')
            ->where('product_image', '!=', '');

        $query->orderBy('id')->chunkById($chunk, function ($products) use (
            $uploader,
            $dryRun,
            $onlyMissing,
            $limit,
            &$processed,
            &$changed,
            &$skipped,
            &$missing
        ) {
            foreach ($products as $product) {
                if ($limit > 0 && $processed >= $limit) {
                    return false;
                }

                $processed++;

                $current = trim((string) $product->getRawOriginal('product_image'));
                if ($current === '') {
                    $skipped++;
                    continue;
                }

                // Normalize current relative path
                $rel = ltrim(str_replace('\\', '/', $current), '/');
                $rel = preg_replace('#^assets/images/#', '', $rel);

                $currentAbs = public_path('assets/images/' . $rel);
                if (!is_file($currentAbs)) {
                    $missing++;
                    $this->line("Missing file for product {$product->id}: {$rel}");
                    continue;
                }

                if ($onlyMissing) {
                    // If already in products/ and exists, skip
                    if (str_starts_with($rel, 'products/') && is_file(public_path('assets/images/' . $rel))) {
                        $skipped++;
                        continue;
                    }
                }

                // If already in products/ and already matches our optimized naming scheme, skip (avoid churn).
                if (str_starts_with($rel, 'products/')) {
                    $base = basename($rel);
                    $isNewPattern = (bool) preg_match('/^[a-f0-9]{16}_[0-9]{12}_[A-Za-z0-9]{6}\.jpg$/', $base);
                    $isOldPattern = (bool) preg_match('/^[a-f0-9]{20}_[0-9]{8}_[0-9]{6}_[A-Za-z0-9]{8}\.jpg$/', $base);
                    if ($isNewPattern || $isOldPattern) {
                        $skipped++;
                        continue;
                    }
                }

                // Use uploader: read file as UploadedFile is not available here, so use manager directly via temp copy.
                // We can pass a Symfony UploadedFile by constructing, but simplest is to use a temporary UploadedFile wrapper.
                $tmpPath = $currentAbs;

                $result = $uploader->storeOptimized(
                    file: new \Illuminate\Http\UploadedFile($tmpPath, basename($tmpPath), null, null, true),
                    category: 'products',
                    alsoWebp: false
                );

                $optimizedPath = ltrim((string) $result['path'], '/'); // assets/images/products/x.jpg
                $optimizedRel = str_replace('assets/images/', '', $optimizedPath); // products/x.jpg

                if ($optimizedRel === $rel) {
                    $skipped++;
                    continue;
                }

                $this->line("Product {$product->id}: {$rel}  ->  {$optimizedRel}" . ($result['deduped'] ? ' (deduped)' : ''));

                if (!$dryRun) {
                    DB::transaction(function () use ($product, $optimizedRel) {
                        Product::query()->where('id', $product->id)->update([
                            'product_image' => $optimizedRel,
                        ]);
                    });
                }

                $changed++;
            }

            return true;
        });

        $this->newLine();
        $this->info("Done. processed={$processed}, changed={$changed}, skipped={$skipped}, missing_files={$missing}");

        return Command::SUCCESS;
    }
}

