<?php

namespace Tests\Unit\Ledger;

use App\Enums\LedgerTransactionType;
use Tests\TestCase;

class LedgerTransactionTypeTest extends TestCase
{
    public function test_singleton_types_contain_critical_non_duplicate_transactions(): void
    {
        $singletons = LedgerTransactionType::singletonTypes();

        $this->assertContains('opening_balance', $singletons);
        $this->assertContains('sale', $singletons);
        $this->assertContains('purchase', $singletons);
        $this->assertContains('cheque_bounce', $singletons);
        $this->assertContains('bounce_recovery', $singletons);
    }

    public function test_payment_like_types_contain_core_payment_transaction_types(): void
    {
        $paymentLike = LedgerTransactionType::paymentLikeTypes();

        $this->assertContains('payment', $paymentLike);
        $this->assertContains('payments', $paymentLike);
        $this->assertContains('sale_payment', $paymentLike);
        $this->assertContains('purchase_payment', $paymentLike);
        $this->assertContains('advance_credit_usage', $paymentLike);
    }
}

