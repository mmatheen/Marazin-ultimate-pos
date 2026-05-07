<?php

namespace Tests\Unit\Ledger;

use App\Services\Ledger\LedgerPostingRuleService;
use Tests\TestCase;

class LedgerPostingRuleServiceTest extends TestCase
{
    public function test_sale_for_customer_posts_debit(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'sale',
            'contact_type' => 'customer',
            'amount' => 1200,
            'notes' => '',
        ]);

        $this->assertSame(1200, $result['debit']);
        $this->assertSame(0, $result['credit']);
    }

    public function test_negative_sale_posts_credit_reversal(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'sale',
            'contact_type' => 'customer',
            'amount' => -400,
            'notes' => '',
        ]);

        $this->assertSame(0, $result['debit']);
        $this->assertSame(400, $result['credit']);
    }

    public function test_regular_customer_payment_posts_credit(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'payments',
            'contact_type' => 'customer',
            'amount' => 500,
            'notes' => 'normal cash payment',
        ]);

        $this->assertSame(0, $result['debit']);
        $this->assertSame(500, $result['credit']);
    }

    public function test_explicit_return_payment_note_posts_debit_for_customer(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'payments',
            'contact_type' => 'customer',
            'amount' => 250,
            'notes' => 'return payment - refund issued',
        ]);

        $this->assertSame(250, $result['debit']);
        $this->assertSame(0, $result['credit']);
    }

    public function test_advance_credit_usage_posts_debit_for_customer(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'advance_credit_usage',
            'contact_type' => 'customer',
            'amount' => 300,
            'notes' => '',
        ]);

        $this->assertSame(300, $result['debit']);
        $this->assertSame(0, $result['credit']);
    }

    public function test_unknown_transaction_type_throws_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unknown transaction type');

        app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'non_existing_type',
            'contact_type' => 'customer',
            'amount' => 100,
            'notes' => '',
        ]);
    }

    public function test_payment_adjustment_with_return_reversal_note_posts_credit_for_customer(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'payment_adjustment',
            'contact_type' => 'customer',
            'amount' => 200,
            'notes' => 'return payment reversal - cheque bounce correction',
        ]);

        $this->assertSame(0, $result['debit']);
        $this->assertSame(200, $result['credit']);
    }

    public function test_payment_adjustment_without_return_reversal_note_posts_debit_for_customer(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'payment_adjustment',
            'contact_type' => 'customer',
            'amount' => 200,
            'notes' => 'regular payment edit adjustment',
        ]);

        $this->assertSame(200, $result['debit']);
        $this->assertSame(0, $result['credit']);
    }

    public function test_payment_adjustment_with_return_reversal_note_posts_debit_for_supplier(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'payment_adjustment',
            'contact_type' => 'supplier',
            'amount' => 300,
            'notes' => 'return payment reversal - supplier adjustment',
        ]);

        $this->assertSame(300, $result['debit']);
        $this->assertSame(0, $result['credit']);
    }

    public function test_payment_adjustment_without_return_reversal_note_posts_credit_for_supplier(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'payment_adjustment',
            'contact_type' => 'supplier',
            'amount' => 300,
            'notes' => 'normal supplier payment edit',
        ]);

        $this->assertSame(0, $result['debit']);
        $this->assertSame(300, $result['credit']);
    }

    public function test_payments_with_cash_refund_note_posts_debit_for_customer(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'payments',
            'contact_type' => 'customer',
            'amount' => 150,
            'notes' => 'cash refund for damaged return',
        ]);

        $this->assertSame(150, $result['debit']);
        $this->assertSame(0, $result['credit']);
    }

    public function test_payments_with_cash_refund_note_posts_credit_for_supplier(): void
    {
        $result = app(LedgerPostingRuleService::class)->resolveDebitCredit([
            'transaction_type' => 'payments',
            'contact_type' => 'supplier',
            'amount' => 150,
            'notes' => 'cash refund for supplier return settlement',
        ]);

        $this->assertSame(0, $result['debit']);
        $this->assertSame(150, $result['credit']);
    }
}

