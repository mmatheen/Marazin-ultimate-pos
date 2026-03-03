import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/receipt-settings.js', // Receipt configuration module
                // POS modules (Phase 2+)
                'resources/js/pos/pos-utils.js',
                'resources/js/pos/pos-cache.js',
                'resources/js/pos/pos-ui.js',
                'resources/js/pos/pos-customer.js',
                'resources/js/pos/pos-salesrep.js',
                'resources/js/pos/pos-location.js',
                'resources/js/pos/pos-product-grid.js',
                'resources/js/pos/pos-autocomplete.js',
                'resources/js/pos/pos-cart.js',
                'resources/js/pos/pos-product-display.js',
                'resources/js/pos/pos-billing.js',
                'resources/js/pos/pos-product-select.js',
                'resources/js/pos/pos-sale.js',
                'resources/js/pos/pos-sales-list.js',
                'resources/js/pos/pos-salesrep-display.js',
                'resources/js/pos/pos-receipt.js',
                'resources/js/pos/pos-helpers.js',
                'resources/js/pos/pos-hotkeys.js',
                'resources/js/pos/pos-init.js',
                'resources/js/pos/pos-page.js',
                'resources/js/pos/pos-payment.js',
            ],
            refresh: true,
        }),
    ],
});
