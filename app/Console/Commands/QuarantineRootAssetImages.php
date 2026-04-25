<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class QuarantineRootAssetImages extends Command
{
    protected $signature = 'images:quarantine-root
        {--dry-run : Show what would be moved, but do not move anything}
        {--limit=0 : Max number of files to move (0 = no limit)}
        {--to=_root_old : Target folder name under public/assets/images}
    ';

    protected $description = 'Move root files in public/assets/images into a quarantine folder (no delete).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $to = trim((string) $this->option('to'));
        if ($to === '') $to = '_root_old';

        $baseDir = public_path('assets/images');
        if (!is_dir($baseDir)) {
            $this->warn("Directory not found: {$baseDir}");
            return Command::SUCCESS;
        }

        $targetDir = $baseDir . DIRECTORY_SEPARATOR . $to;
        if (!is_dir($targetDir) && !$dryRun) {
            mkdir($targetDir, 0775, true);
        }

        // Safety: never move these (used by UI).
        $keep = [
            'No Product Image Available.png',
            'ARB Logo.png',
            '.gitkeep',
        ];

        $moved = 0;
        $scanned = 0;
        $skipped = 0;

        $this->info('Scanning root files in public/assets/images (excluding subfolders)…');
        if ($dryRun) $this->warn('DRY RUN: no files will be moved.');

        $entries = scandir($baseDir) ?: [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            if (in_array($entry, $keep, true)) {
                $skipped++;
                continue;
            }

            $abs = $baseDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($abs)) {
                // skip directories like products/, css/, etc.
                $skipped++;
                continue;
            }

            $scanned++;

            if ($limit > 0 && $moved >= $limit) {
                $this->warn("Move limit reached ({$limit}). Stopping.");
                break;
            }

            $dest = $targetDir . DIRECTORY_SEPARATOR . $entry;
            // If file name already exists in quarantine, add suffix.
            if (file_exists($dest)) {
                $pi = pathinfo($entry);
                $name = $pi['filename'] ?? $entry;
                $ext = isset($pi['extension']) ? ('.' . $pi['extension']) : '';
                $dest = $targetDir . DIRECTORY_SEPARATOR . $name . '_' . date('YmdHis') . $ext;
            }

            $relFrom = '/assets/images/' . $entry;
            $relTo = '/assets/images/' . $to . '/' . basename($dest);
            $this->line("MOVE: {$relFrom}  ->  {$relTo}");

            if (!$dryRun) {
                // rename = atomic move on same filesystem
                @rename($abs, $dest);
            }

            $moved++;
        }

        $this->newLine();
        $this->info("Done. scanned_files={$scanned}, moved={$moved}, skipped_entries={$skipped}" . ($dryRun ? ' (dry-run)' : ''));

        $this->line('Restore (if needed): move files back from /assets/images/' . $to . ' to /assets/images/');

        return Command::SUCCESS;
    }
}

