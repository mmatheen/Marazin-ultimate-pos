<?php

namespace Tests\Unit\Sale;

use App\Services\Sale\SalePaymentStatusService;
use Tests\TestCase;

class SalePaymentStatusServiceTest extends TestCase
{
    public function test_it_returns_due_when_total_paid_is_null(): void
    {
        $status = app(SalePaymentStatusService::class)->deriveForInvoice(1000.0, null);

        $this->assertSame('Due', $status);
    }

    public function test_it_returns_partial_when_some_amount_is_paid(): void
    {
        $status = app(SalePaymentStatusService::class)->deriveForInvoice(1000.0, 200.0);

        $this->assertSame('Partial', $status);
    }

    public function test_it_returns_paid_when_due_is_effectively_zero(): void
    {
        $status = app(SalePaymentStatusService::class)->deriveForInvoice(1000.0, 1000.0);

        $this->assertSame('Paid', $status);
    }
}
