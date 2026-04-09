<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\ShortUrl;
use App\Services\Sale\SaleReceiptService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicInvoiceController extends Controller
{
    public function redirectByCode(string $code): RedirectResponse
    {
        $shortUrl = ShortUrl::where('code', $code)->firstOrFail();

        if ($shortUrl->expires_at && Carbon::now()->greaterThan($shortUrl->expires_at)) {
            abort(410, 'This invoice link has expired.');
        }

        $shortUrl->increment('clicks');
        $shortUrl->update(['last_accessed_at' => now()]);

        return redirect()->to($shortUrl->original_url);
    }

    public function showInvoice(string $token, Request $request, SaleReceiptService $saleReceiptService)
    {
        $sale = Sale::withoutLocationScope()
            ->where('invoice_token', $token)
            ->where('transaction_type', 'invoice')
            ->firstOrFail();

        $html = $saleReceiptService->getHtml((int) $sale->id, $request->query('layout'));

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
