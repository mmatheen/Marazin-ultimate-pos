<?php

namespace App\Console\Commands;

use App\Jobs\SendSmsJob;
use App\Models\Sale;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendPaymentReminderSms extends Command
{
    protected $signature = 'sms:send-payment-reminders';

    protected $description = 'Send payment reminder SMS messages for outstanding sales';

    public function handle(): int
    {
        $setting = Setting::first();

        if (! $setting || ! filled($setting->sms_user_id) || ! filled($setting->sms_api_key) || ! filled($setting->sms_sender_id)) {
            $this->warn('SMS gateway credentials are missing.');
            return self::FAILURE;
        }

        $sales = Sale::with('customer')
            ->where('transaction_type', 'invoice')
            ->where('status', 'final')
            ->whereIn('payment_status', ['due', 'partial'])
            ->where('total_due', '>', 0)
            ->whereNotNull('customer_id')
            ->orderBy('sales_date', 'asc')
            ->get();

        $queued = 0;

        foreach ($sales as $sale) {
            $mobileNo = $sale->customer?->mobile_no;

            if (! filled($mobileNo) || ! $sale->customer?->allow_sms) {
                continue;
            }

            $dueAmount = number_format((float) $sale->total_due, 2);
            $saleDate = $sale->sales_date ? Carbon::parse($sale->sales_date)->format('Y-m-d') : '';
            $reference = $sale->invoice_no ?: $sale->order_number ?: ('Sale #' . $sale->id);

            $message = trim(
                "Reminder: {$reference} has an outstanding balance of LKR {$dueAmount}. " .
                "Please settle it at your earliest convenience. " .
                ($saleDate ? "Sale date: {$saleDate}." : '')
            );

            SendSmsJob::dispatch([$mobileNo], $message)->afterCommit();
            $queued++;
        }

        $this->info("Queued {$queued} payment reminder SMS message(s).");

        return self::SUCCESS;
    }
}
