<?php

namespace App\Services\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class OptimizedImageUploader
{
    public function storeOptimized(
        UploadedFile $file,
        string $category = 'products',
        bool $alsoWebp = false
    ): array {
        $originalBytes = (int) ($file->getSize() ?? 0);

        $quality = $this->pickJpegQuality($originalBytes);

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());

        // Prevent huge images from consuming memory; scale down by width only.
        $image = $image->scaleDown(width: 1200);

        $jpgEncoded = $image->toJpeg($quality);
        $jpgBytes = (string) $jpgEncoded;

        // Dedupe (no DB table): same optimized bytes -> same hash prefix -> reuse existing file in folder.
        // Use a shorter prefix to keep filenames reasonable while keeping collision risk extremely low.
        $hash = hash('sha256', $jpgBytes);
        $hashPrefix = substr($hash, 0, 16);

        $subDir = $this->categoryToSubdir($category);
        $subDir = trim($subDir, '/');
        $dir = $subDir !== ''
            ? public_path('assets/images/' . $subDir)
            : public_path('assets/images');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $existingMatches = glob($dir . DIRECTORY_SEPARATOR . $hashPrefix . '_*.jpg') ?: [];
        if (!empty($existingMatches)) {
            $existingAbs = $existingMatches[0];
            $existingRel = str_replace(public_path(), '', $existingAbs);
            $existingRel = str_replace('\\', '/', $existingRel);

            $size = $image->size();

            return [
                'path' => $existingRel,
                'hash' => $hash,
                'bytes' => filesize($existingAbs) ?: strlen($jpgBytes),
                'width' => $size->width(),
                'height' => $size->height(),
                'mime' => 'image/jpeg',
                'deduped' => true,
                'also_webp_path' => null,
            ];
        }

        // Short filename: {hash16}_{yymmddHHMMSS}_{rand6}.jpg
        $filenameBase = $hashPrefix . '_' . now()->format('ymdHis') . '_' . Str::random(6);
        $jpgFilename = $filenameBase . '.jpg';
        $relativeJpgPath = '/assets/images' . ($subDir !== '' ? '/' . $subDir : '') . '/' . $jpgFilename;
        $absJpgPath = $dir . DIRECTORY_SEPARATOR . $jpgFilename;

        file_put_contents($absJpgPath, $jpgBytes, LOCK_EX);

        $alsoWebpPath = null;
        if ($alsoWebp) {
            $webpEncoded = $image->toWebp(75);
            $webpBytes = (string) $webpEncoded;
            $webpFilename = $filenameBase . '.webp';
            $absWebpPath = $dir . DIRECTORY_SEPARATOR . $webpFilename;
            file_put_contents($absWebpPath, $webpBytes, LOCK_EX);
            $alsoWebpPath = '/assets/images' . ($subDir !== '' ? '/' . $subDir : '') . '/' . $webpFilename;
        }

        $size = $image->size();

        return [
            'path' => $relativeJpgPath,
            'hash' => $hash,
            'bytes' => strlen($jpgBytes),
            'width' => $size->width(),
            'height' => $size->height(),
            'mime' => 'image/jpeg',
            'deduped' => false,
            'also_webp_path' => $alsoWebpPath,
        ];
    }

    private function pickJpegQuality(int $originalBytes): int
    {
        // 60–80% quality depending on original size.
        if ($originalBytes >= 3 * 1024 * 1024) return 60;
        if ($originalBytes >= 1 * 1024 * 1024) return 70;
        return 80;
    }

    private function categoryToSubdir(string $category): string
    {
        $category = strtolower(trim($category));
        return match ($category) {
            // Back-compat for systems that store only filename in DB and assume /assets/images/{filename}
            'products_root', 'product_root', 'product' => '',
            'products' => 'products',
            'invoices' => 'invoices',
            'users' => 'users',
            default => preg_replace('/[^a-z0-9_-]+/', '-', $category) ?: 'misc',
        };
    }
}

