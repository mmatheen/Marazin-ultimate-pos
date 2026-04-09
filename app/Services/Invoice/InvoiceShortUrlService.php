<?php

namespace App\Services\Invoice;

use App\Models\Sale;
use App\Models\ShortUrl;
use Illuminate\Support\Str;

class InvoiceShortUrlService
{
    private const DEFAULT_CODE_LENGTH = 12;
    private const MAX_GENERATION_ATTEMPTS = 10;

    public function getOrCreateForSale(Sale $sale): string
    {
        if (! filled($sale->invoice_token)) {
            throw new \RuntimeException('Missing invoice token for sale ID: ' . $sale->id);
        }

        $originalUrl = route('public.invoice.show', ['token' => $sale->invoice_token]);

        $shortUrl = ShortUrl::where('original_url', $originalUrl)->first();
        if (! $shortUrl) {
            $shortUrl = ShortUrl::create([
                'code' => $this->generateUniqueCode(),
                'original_url' => $originalUrl,
                'expires_at' => null,
            ]);
        }

        return $this->buildPublicShortUrl($shortUrl->code);
    }

    public function buildPublicShortUrl(string $code): string
    {
        $base = config('services.invoice.short_url_base');

        if (! filled($base)) {
            throw new \RuntimeException('INVOICE_SHORT_URL_BASE is not configured.');
        }

        return rtrim((string) $base, '/') . '/' . $code;
    }

    private function generateUniqueCode(int $length = self::DEFAULT_CODE_LENGTH): string
    {
        for ($attempt = 0; $attempt < self::MAX_GENERATION_ATTEMPTS; $attempt++) {
            $code = bin2hex(random_bytes((int) ceil($length / 2)));
            $code = substr(strtolower($code), 0, $length);

            if (! ShortUrl::where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Failed to generate unique short URL code.');
    }
}
