<?php

namespace App\Services;

class PosVatCalculatorService
{
    private const MONEY_SCALE = 100;
    private const QTY_SCALE = 10000;
    private const PERCENT_SCALE = 10000;

    public static function forPurchase(float $unitCostExVat, float $taxPercent, float $quantity): array
    {
        $unitCostCents = self::toCents($unitCostExVat);
        $taxScaled = self::toPercentScaled($taxPercent);
        $qtyUnits = self::toQtyUnits(max($quantity, 0));

        $vatPerUnitCents = self::roundDiv($unitCostCents * $taxScaled, self::PERCENT_SCALE);
        $netUnitCostCents = $unitCostCents + $vatPerUnitCents;
        $vatTotalCents = self::roundDiv($vatPerUnitCents * $qtyUnits, self::QTY_SCALE);

        return [
            'tax_percent' => round($taxPercent, 2),
            'vat_per_unit' => self::fromCents($vatPerUnitCents),
            'net_unit_cost' => self::fromCents($netUnitCostCents),
            'vat_total' => self::fromCents($vatTotalCents),
        ];
    }

    public static function forSale(
        float $sellingPrice,
        float $taxPercent,
        float $unitCostExVat,
        float $quantity,
        string $sellingPriceTaxType = 'inclusive'
    ): array
    {
        $vatPerUnit = self::saleVatPortionPerUnit($sellingPrice, $taxPercent, $sellingPriceTaxType);
        $saleExclVatPerUnit = $sellingPriceTaxType === 'exclusive'
            ? self::fromCents(self::toCents($sellingPrice))
            : self::fromCents(self::toCents($sellingPrice - $vatPerUnit));
        $profitPerUnit = self::fromCents(self::toCents($saleExclVatPerUnit - $unitCostExVat));

        $qtyUnits = self::toQtyUnits(max($quantity, 0));
        $vatPerUnitCents = self::toCents($vatPerUnit);
        $profitPerUnitCents = self::toCents($profitPerUnit);

        return [
            'tax_percent' => round($taxPercent, 2),
            'vat_per_unit' => $vatPerUnit,
            'sale_excl_vat_per_unit' => $saleExclVatPerUnit,
            'vat_total' => self::fromCents(self::roundDiv($vatPerUnitCents * $qtyUnits, self::QTY_SCALE)),
            'profit_per_unit' => $profitPerUnit,
            'profit_total' => self::fromCents(self::roundDiv($profitPerUnitCents * $qtyUnits, self::QTY_SCALE)),
        ];
    }

    public static function forPurchaseReturn(float $unitCostExVat, float $taxPercent, float $quantity): array
    {
        return self::forPurchase($unitCostExVat, $taxPercent, $quantity);
    }

    public static function forSaleReturn(
        float $sellingPrice,
        float $taxPercent,
        float $unitCostExVat,
        float $quantity,
        string $sellingPriceTaxType = 'inclusive'
    ): array
    {
        $saleMetrics = self::forSale($sellingPrice, $taxPercent, $unitCostExVat, $quantity, $sellingPriceTaxType);

        return [
            'tax_percent' => $saleMetrics['tax_percent'],
            'vat_per_unit' => $saleMetrics['vat_per_unit'],
            'sale_excl_vat_per_unit' => $saleMetrics['sale_excl_vat_per_unit'],
            'vat_total' => $saleMetrics['vat_total'],
            'profit_per_unit' => $saleMetrics['profit_per_unit'],
            'profit_reversal_total' => $saleMetrics['profit_total'],
        ];
    }

    private static function saleVatPortionPerUnit(float $sellingPrice, float $taxPercent, string $sellingPriceTaxType): float
    {
        if ($taxPercent <= 0) {
            return 0.0;
        }

        $sellingPriceCents = self::toCents($sellingPrice);
        $taxScaled = self::toPercentScaled($taxPercent);

        if ($sellingPriceTaxType === 'exclusive') {
            $vatCents = self::roundDiv($sellingPriceCents * $taxScaled, self::PERCENT_SCALE);
            return self::fromCents($vatCents);
        }

        // Inclusive VAT portion: gross * rate / (100 + rate)
        $denominatorScaled = self::PERCENT_SCALE + $taxScaled;
        $vatCents = self::roundDiv($sellingPriceCents * $taxScaled, $denominatorScaled);

        return self::fromCents($vatCents);
    }

    private static function toCents(float $amount): int
    {
        return (int) round($amount * self::MONEY_SCALE);
    }

    private static function fromCents(int $cents): float
    {
        return round($cents / self::MONEY_SCALE, 2);
    }

    private static function toQtyUnits(float $quantity): int
    {
        return (int) round($quantity * self::QTY_SCALE);
    }

    private static function toPercentScaled(float $percent): int
    {
        return (int) round($percent * 100);
    }

    private static function roundDiv(int $numerator, int $denominator): int
    {
        if ($denominator === 0) {
            return 0;
        }

        $half = intdiv($denominator, 2);
        if ($numerator >= 0) {
            return intdiv($numerator + $half, $denominator);
        }

        return -intdiv(abs($numerator) + $half, $denominator);
    }
}
