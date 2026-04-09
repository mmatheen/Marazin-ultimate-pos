<?php

namespace Tests\Unit\Sale;

use App\Models\Sale;
use App\Services\Sale\SaleCalculationService;
use Tests\TestCase;

class SaleCalculationServiceTest extends TestCase
{
    public function test_it_calculates_final_total_with_fixed_discount_and_shipping(): void
    {
        $sale = new Sale([
            'subtotal' => 1000,
            'discount_type' => 'fixed',
            'discount_amount' => 100,
            'shipping_charges' => 50,
        ]);

        $result = app(SaleCalculationService::class)->calculateFinalTotal($sale);

        $this->assertSame(950.0, $result);
    }

    public function test_it_calculates_final_total_with_percentage_discount(): void
    {
        $sale = new Sale([
            'subtotal' => 1000,
            'discount_type' => 'percentage',
            'discount_amount' => 10,
            'shipping_charges' => 25,
        ]);

        $result = app(SaleCalculationService::class)->calculateFinalTotal($sale);

        $this->assertSame(925.0, $result);
    }

    public function test_it_never_returns_negative_base_total_before_shipping(): void
    {
        $sale = new Sale([
            'subtotal' => 100,
            'discount_type' => 'fixed',
            'discount_amount' => 999,
            'shipping_charges' => 20,
        ]);

        $result = app(SaleCalculationService::class)->calculateFinalTotal($sale);

        $this->assertSame(20.0, $result);
    }
}
