<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $phones,
        public string $message,
    ) {
    }

    public function handle(SmsService $smsService): void
    {
        if (count($this->phones) > 1) {
            $smsService->sendBulk($this->phones, $this->message);
            return;
        }

        $smsService->sendSingle($this->phones[0] ?? '', $this->message);
    }
}
