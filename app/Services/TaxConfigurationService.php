<?php

namespace App\Services;

use App\Models\Setting;

class TaxConfigurationService
{
    private static ?float $cachedDefaultTaxPercent = null;
    private static ?string $cachedDefaultSellingPriceTaxType = null;

    public static function defaultTaxPercent(): float
    {
        if (self::$cachedDefaultTaxPercent === null) {
            $value = Setting::value('default_tax_percent');
            self::$cachedDefaultTaxPercent = self::sanitizeTaxPercent($value);
        }

        return self::$cachedDefaultTaxPercent;
    }

    public static function defaultSellingPriceTaxType(): string
    {
        if (self::$cachedDefaultSellingPriceTaxType === null) {
            $value = Setting::value('default_selling_price_tax_type');
            self::$cachedDefaultSellingPriceTaxType = self::normalizeTaxType($value);
        }

        return self::$cachedDefaultSellingPriceTaxType;
    }

    /**
     * Priority order:
     * 1) line-level tax (if explicitly provided)
     * 2) product-level tax (when > 0)
     * 3) global default tax from settings
     */
    public static function resolveTaxPercent(mixed $lineTaxPercent = null, mixed $productTaxPercent = null): float
    {
        if ($lineTaxPercent !== null && $lineTaxPercent !== '') {
            return self::sanitizeTaxPercent($lineTaxPercent);
        }

        if ($productTaxPercent !== null && $productTaxPercent !== '') {
            $productTax = self::sanitizeTaxPercent($productTaxPercent);
            if ($productTax > 0) {
                return $productTax;
            }
        }

        return self::defaultTaxPercent();
    }

    public static function resolveSellingPriceTaxType(?string $productTaxType = null, ?string $lineTaxType = null): string
    {
        if (!empty($lineTaxType)) {
            return self::normalizeTaxType($lineTaxType);
        }

        if (!empty($productTaxType)) {
            return self::normalizeTaxType($productTaxType);
        }

        return self::defaultSellingPriceTaxType();
    }

    private static function sanitizeTaxPercent(mixed $value): float
    {
        $numeric = is_numeric($value) ? (float) $value : 0.0;
        if ($numeric < 0) {
            $numeric = 0;
        }
        if ($numeric > 100) {
            $numeric = 100;
        }

        return round($numeric, 2);
    }

    private static function normalizeTaxType(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['inclusive', 'exclusive'], true)
            ? $normalized
            : 'exclusive';
    }
}
