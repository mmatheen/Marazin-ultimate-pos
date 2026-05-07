<?php

namespace App\Enums;

enum LedgerTransactionType: string
{
    case OPENING_BALANCE = 'opening_balance';
    case SALE = 'sale';
    case PURCHASE = 'purchase';
    case PAYMENT = 'payment';
    case PAYMENTS = 'payments';
    case SALE_PAYMENT = 'sale_payment';
    case PURCHASE_PAYMENT = 'purchase_payment';
    case ADVANCE_CREDIT_USAGE = 'advance_credit_usage';
    case CHEQUE_BOUNCE = 'cheque_bounce';
    case BOUNCE_RECOVERY = 'bounce_recovery';

    /**
     * Types that must not have multiple active rows
     * for the same (contact_id, contact_type, reference_no, transaction_type).
     */
    public static function singletonTypes(): array
    {
        return [
            self::OPENING_BALANCE->value,
            self::SALE->value,
            self::PURCHASE->value,
            self::CHEQUE_BOUNCE->value,
            self::BOUNCE_RECOVERY->value,
        ];
    }

    /**
     * Types treated as payment-like transactions for dedupe window logic.
     */
    public static function paymentLikeTypes(): array
    {
        return [
            self::PAYMENT->value,
            self::PAYMENTS->value,
            self::SALE_PAYMENT->value,
            self::PURCHASE_PAYMENT->value,
            self::ADVANCE_CREDIT_USAGE->value,
        ];
    }
}

