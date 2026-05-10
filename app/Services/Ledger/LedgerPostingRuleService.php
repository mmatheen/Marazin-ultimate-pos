<?php

namespace App\Services\Ledger;

class LedgerPostingRuleService
{
    private function normalizeAmountWithReversalFlag(array $data): array
    {
        $rawAmount = $data['amount'] ?? 0;

        return [
            'amount' => abs($rawAmount),
            'is_reversal' => $rawAmount < 0,
        ];
    }

    private function isExplicitReturnPaymentNote(array $data): bool
    {
        $notesLower = isset($data['notes']) ? strtolower((string) $data['notes']) : '';

        return str_starts_with($notesLower, 'return payment -')
            || str_starts_with($notesLower, 'cash refund');
    }

    private function isReturnPaymentReversalNote(array $data): bool
    {
        return isset($data['notes'])
            && strpos(strtolower((string) $data['notes']), 'return payment reversal') !== false;
    }

    /**
     * Resolve debit/credit amounts for a ledger entry payload.
     *
     * @param array{
     *   transaction_type:string,
     *   contact_type:string,
     *   amount:mixed,
     *   notes?:string|null
     * } $data
     * @return array{debit:float|int,credit:float|int}
     */
    public function resolveDebitCredit(array $data): array
    {
        $debit = 0;
        $credit = 0;

        switch ($data['transaction_type']) {
            case 'opening_balance':
                if ($data['contact_type'] === 'customer') {
                    if ($data['amount'] > 0) {
                        $debit = $data['amount'];
                    } else {
                        $credit = abs($data['amount']);
                    }
                } else {
                    if ($data['amount'] > 0) {
                        $credit = $data['amount'];
                    } else {
                        $debit = abs($data['amount']);
                    }
                }
                break;

            case 'sale':
                if ($data['amount'] > 0) {
                    $debit = $data['amount'];
                } else {
                    $credit = abs($data['amount']);
                }
                break;

            case 'purchase':
                $credit = $data['amount'];
                break;

            case 'sale_payment':
            case 'payment':
            case 'payments':
                $normalized = $this->normalizeAmountWithReversalFlag($data);
                $amount = $normalized['amount'];
                $isReversal = $normalized['is_reversal'];
                $isExplicitReturnPaymentNote = $this->isExplicitReturnPaymentNote($data);

                if ($isExplicitReturnPaymentNote) {
                    if ($data['contact_type'] === 'customer') {
                        if ($isReversal) {
                            $credit = $amount;
                        } else {
                            $debit = $amount;
                        }
                    } else {
                        if ($isReversal) {
                            $debit = $amount;
                        } else {
                            $credit = $amount;
                        }
                    }
                } else {
                    if ($data['contact_type'] === 'customer') {
                        if ($isReversal) {
                            $debit = $amount;
                        } else {
                            $credit = $amount;
                        }
                    } else {
                        if ($isReversal) {
                            $credit = $amount;
                        } else {
                            $debit = $amount;
                        }
                    }
                }
                break;

            case 'purchase_payment':
                $debit = $data['amount'];
                break;

            case 'sale_return':
            case 'sale_return_with_bill':
            case 'sale_return_without_bill':
                $credit = $data['amount'];
                break;

            case 'purchase_return':
                $debit = $data['amount'];
                break;

            case 'return_payment':
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount'];
                } else {
                    $credit = $data['amount'];
                }
                break;

            case 'opening_balance_payment':
                if ($data['contact_type'] === 'customer') {
                    $credit = $data['amount'];
                } else {
                    $debit = $data['amount'];
                }
                break;

            case 'discount_given':
                if ($data['contact_type'] === 'customer') {
                    $credit = $data['amount'];
                } else {
                    $debit = $data['amount'];
                }
                break;

            case 'opening_balance_adjustment':
                if ($data['contact_type'] === 'customer') {
                    if ($data['amount'] > 0) {
                        $debit = $data['amount'];
                    } else {
                        $credit = abs($data['amount']);
                    }
                } else {
                    if ($data['amount'] > 0) {
                        $credit = $data['amount'];
                    } else {
                        $debit = abs($data['amount']);
                    }
                }
                break;

            case 'cheque_bounce':
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount'];
                } else {
                    $credit = $data['amount'];
                }
                break;

            case 'advance_payment':
                if ($data['contact_type'] === 'customer') {
                    $credit = $data['amount'];
                } else {
                    $debit = $data['amount'];
                }
                break;

            case 'advance_credit_usage':
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount'];
                } else {
                    $credit = $data['amount'];
                }
                break;

            case 'bank_charges':
                $debit = $data['amount'];
                break;

            case 'penalty':
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount'];
                } else {
                    $credit = $data['amount'];
                }
                break;

            case 'adjustment_debit':
                $debit = $data['amount'];
                break;

            case 'adjustment_credit':
                $credit = $data['amount'];
                break;

            case 'bounce_recovery':
                if ($data['contact_type'] === 'customer') {
                    $credit = $data['amount'];
                } else {
                    $debit = $data['amount'];
                }
                break;

            case 'invoice':
                if ($data['contact_type'] === 'customer') {
                    $debit = $data['amount'];
                } else {
                    $credit = $data['amount'];
                }
                break;

            case 'payment_adjustment':
                $amount = abs($data['amount']);
                $isReturnPayment = $this->isReturnPaymentReversalNote($data);

                if ($isReturnPayment) {
                    if ($data['contact_type'] === 'customer') {
                        $credit = $amount;
                    } else {
                        $debit = $amount;
                    }
                } else {
                    if ($data['contact_type'] === 'customer') {
                        $debit = $amount;
                    } else {
                        $credit = $amount;
                    }
                }
                break;

            case 'sale_adjustment':
                if ($data['contact_type'] === 'customer') {
                    if ($data['amount'] < 0) {
                        $credit = abs($data['amount']);
                    } else {
                        $debit = $data['amount'];
                    }
                } else {
                    if ($data['amount'] < 0) {
                        $debit = abs($data['amount']);
                    } else {
                        $credit = $data['amount'];
                    }
                }
                break;

            case 'purchase_adjustment':
                if ($data['contact_type'] === 'supplier') {
                    if ($data['amount'] < 0) {
                        $credit = abs($data['amount']);
                    } else {
                        $debit = $data['amount'];
                    }
                } else {
                    if ($data['amount'] < 0) {
                        $credit = abs($data['amount']);
                    } else {
                        $debit = $data['amount'];
                    }
                }
                break;

            default:
                if (str_ends_with($data['transaction_type'], '_reversal')) {
                    $baseType = str_replace('_reversal', '', $data['transaction_type']);
                    switch ($baseType) {
                        case 'purchase_return':
                            $credit = $data['amount'];
                            break;

                        case 'sale':
                        case 'purchase':
                        case 'payment':
                        case 'payments':
                        case 'sale_payment':
                        case 'purchase_payment':
                        case 'sale_return':
                        case 'sale_return_with_bill':
                        case 'sale_return_without_bill':
                            if ($data['contact_type'] === 'customer') {
                                if ($data['amount'] > 0) {
                                    $credit = $data['amount'];
                                } else {
                                    $debit = abs($data['amount']);
                                }
                            } else {
                                if ($data['amount'] > 0) {
                                    $debit = $data['amount'];
                                } else {
                                    $credit = abs($data['amount']);
                                }
                            }
                            break;

                        default:
                            throw new \Exception("Unknown reversal transaction type: {$data['transaction_type']}");
                    }
                } else {
                    throw new \Exception("Unknown transaction type: {$data['transaction_type']}");
                }
                break;
        }

        return [
            'debit' => $debit,
            'credit' => $credit,
        ];
    }
}

