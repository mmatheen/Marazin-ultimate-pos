<?php

namespace Tests\Unit\Ledger;

use App\Models\Supplier;
use App\Services\Ledger\LedgerBalanceQueryService;
use Tests\TestCase;

class LedgerBalanceQueryServiceTest extends TestCase
{
    public function test_it_builds_supplier_summary_from_transactions(): void
    {
        $supplier = new Supplier([
            'id' => 12,
            'name' => 'Demo Supplier',
            'opening_balance' => 1500,
        ]);

        $transactions = [
            ['transaction_type' => 'purchase', 'debit' => 1000, 'credit' => 0],
            ['transaction_type' => 'purchase', 'debit' => 500, 'credit' => 0],
            ['transaction_type' => 'purchase_return', 'debit' => 0, 'credit' => 200],
            ['transaction_type' => 'payments', 'debit' => 0, 'credit' => 300],
        ];

        $summary = app(LedgerBalanceQueryService::class)->buildSupplierSummary($supplier, $transactions);

        $this->assertSame($supplier, $summary['supplier']);
        $this->assertSame(1500, $summary['opening_balance']);
        $this->assertSame(1500, $summary['total_purchases']);
        $this->assertSame(200, $summary['total_returns']);
        $this->assertSame(300, $summary['total_payments']);
        $this->assertSame(4, $summary['total_transactions']);
        $this->assertArrayHasKey('current_balance', $summary);
    }

    public function test_it_returns_customer_balance_summary_shape(): void
    {
        $summary = app(LedgerBalanceQueryService::class)->getCustomerBalanceSummary(1);

        $this->assertSame(1, $summary['customer_id']);
        $this->assertArrayHasKey('current_balance', $summary);
        $this->assertArrayHasKey('outstanding_amount', $summary);
        $this->assertArrayHasKey('advance_amount', $summary);
        $this->assertArrayHasKey('balance_status', $summary);
        $this->assertArrayHasKey('last_updated', $summary);
    }
}

