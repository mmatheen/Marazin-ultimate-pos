<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\ShortUrl;
use App\Services\Sale\SaleReceiptService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicInvoiceController extends Controller
{
    public function redirectByCode(string $code, Request $request, SaleReceiptService $saleReceiptService)
    {
        $shortUrl = ShortUrl::where('code', $code)->firstOrFail();

        if ($shortUrl->expires_at && Carbon::now()->greaterThan($shortUrl->expires_at)) {
            abort(410, 'This invoice link has expired.');
        }

        $shortUrl->increment('clicks');
        $shortUrl->update(['last_accessed_at' => now()]);

        $token = $this->extractTokenFromUrl((string) $shortUrl->original_url);
        if (! $token) {
            abort(404, 'Invalid invoice short link.');
        }

        return $this->renderInvoiceByToken($token, $request, $saleReceiptService);
    }

    public function showInvoice(string $token, Request $request, SaleReceiptService $saleReceiptService)
    {
        return $this->renderInvoiceByToken($token, $request, $saleReceiptService);
    }

    private function renderInvoiceByToken(string $token, Request $request, SaleReceiptService $saleReceiptService)
    {
        $sale = Sale::withoutLocationScope()
            ->where('invoice_token', $token)
            ->firstOrFail();

        // Bypass location scope for public link rendering while token controls access.
        $html = $saleReceiptService->getHtml((int) $sale->id, $request->query('layout'), true);

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function extractTokenFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path)) {
            return null;
        }

        $segments = explode('/', trim($path, '/'));
        $token = end($segments);

        if (! is_string($token) || ! Str::isUuid($token)) {
            return null;
        }

        return $token;
    }
}
