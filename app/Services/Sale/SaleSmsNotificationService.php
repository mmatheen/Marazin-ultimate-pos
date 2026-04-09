<?php

namespace App\Services\Sale;

use App\Jobs\SendSmsJob;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use App\Services\Invoice\InvoiceShortUrlService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SaleSmsNotificationService
{
    public function dispatchForSale(Sale $sale): void
    {
        if ($sale->transaction_type !== 'invoice') {
            return;
        }

        if (! $sale->customer_id || (int) $sale->customer_id === 1) {
            return;
        }

        $customer = $sale->customer;

        if (! $customer || blank($customer->mobile_no)) {
            return;
        }

        if (! $customer->allow_sms) {
            return;
        }

        $setting = Setting::first();

        if (! $setting) {
            return;
        }

        $reference = $sale->order_number ?: $sale->invoice_no ?: ('Sale #' . $sale->id);
        $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        if ($customerName === '') {
            $customerName = $customer->full_name ?? 'Customer';
        }
        $customerName = strtoupper($customerName);

        $locationName = strtoupper($sale->location?->name ?? $setting->business_name ?? 'OUR STORE');
        $saleDate = $sale->sales_date
            ? Carbon::parse($sale->sales_date)->format('d M Y')
            : now()->format('d M Y');

        if (blank($sale->invoice_token)) {
            $sale->forceFill(['invoice_token' => (string) Str::uuid()])->saveQuietly();
            $sale->refresh();
        }

        $invoiceLink = '';
        if ($this->shouldIncludeInvoiceLinkInSms($sale)) {
            try {
                /** @var InvoiceShortUrlService $shortUrlService */
                $shortUrlService = app(InvoiceShortUrlService::class);
                $invoiceLink = $shortUrlService->getOrCreateForSale($sale);
            } catch (\Throwable $e) {
                Log::warning('Invoice short URL generation failed for SMS dispatch.', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $totalAmount = number_format((float) ($sale->final_total ?? 0), 2);
        $dueAmount = number_format((float) ($sale->total_due ?? 0), 2);
        $ledgerBalance = (float) $customer->calculateBalanceFromLedger();
        $outstandingAmount = number_format(max(0, $ledgerBalance), 2);

        $message = "DEAR {$customerName},\n\n"
            . "LOCATION: {$locationName}\n"
            . "INVOICE: {$reference}\n"
            . "DATE: {$saleDate}\n"
            . "TOTAL: RS. {$totalAmount}\n"
            . "PAID: RS. " . number_format((float) ($sale->total_paid ?? 0), 2) . "\n"
            . "BALANCE B/F: RS. {$outstandingAmount}\n\n";

        if ((float) ($sale->total_due ?? 0) > 0) {
            $message .= "THIS IS YOUR OUTSTANDING DUE.\n\n";
        }

        $message .= "THANK YOU FOR SHOPPING WITH US!";

        if ($invoiceLink !== '') {
            $message .= "\nView: {$invoiceLink}";
        }

        SendSmsJob::dispatch([$customer->mobile_no], $message)->afterCommit();
    }

    private function shouldIncludeInvoiceLinkInSms(Sale $sale): bool
    {
        if (! $sale->user_id) {
            return false;
        }

        $creator = User::withoutGlobalScopes()->with('roles')->find($sale->user_id);
        if (! $creator) {
            return false;
        }

        if (method_exists($creator, 'isMasterSuperAdmin') && $creator->isMasterSuperAdmin()) {
            return true;
        }

        $roleNames = $creator->roles->pluck('name');
        $roleKeys = $creator->roles->pluck('key');

        return $roleNames->contains('Master Super Admin') || $roleKeys->contains('master_super_admin');
    }
}
