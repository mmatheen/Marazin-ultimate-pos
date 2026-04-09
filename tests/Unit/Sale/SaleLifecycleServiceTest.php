<?php

namespace Tests\Unit\Sale;

use App\Models\Sale;
use App\Services\Sale\SaleLifecycleService;
use Tests\TestCase;

class SaleLifecycleServiceTest extends TestCase
{
    public function test_it_applies_computed_totals_and_payment_status_for_final_sales(): void
    {
        $sale = new Sale([
            'status' => 'final',
            'subtotal' => 1000,
            'discount_type' => 'fixed',
            'discount_amount' => 100,
            'shipping_charges' => 50,
            'total_paid' => 200,
        ]);

        app(SaleLifecycleService::class)->applyComputedTotalsAndStatus($sale);

        $this->assertSame(950.0, (float) $sale->final_total);
        $this->assertSame(750.0, (float) $sale->total_due);
        $this->assertSame('Partial', $sale->payment_status);
    }

    public function test_it_assigns_invoice_token_for_invoice_creation(): void
    {
        $sale = new Sale([
            'transaction_type' => 'invoice',
            'invoice_token' => null,
        ]);

        app(SaleLifecycleService::class)->ensureInvoiceTokenForCreation($sale);

        $this->assertNotEmpty($sale->invoice_token);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $sale->invoice_token
        );
    }
}
