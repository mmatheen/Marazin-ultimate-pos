import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pos/main.js', // Modular POS entry point
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    // Split POS modules into separate chunks for better caching
                    'pos-utils': [
                        'resources/js/pos/utils/formatters.js',
                        'resources/js/pos/utils/helpers.js',
                        'resources/js/pos/utils/validation.js',
                        'resources/js/pos/utils/cache.js',
                    ],
                    'pos-api': [
                        'resources/js/pos/api/client.js',
                        'resources/js/pos/api/products.js',
                        'resources/js/pos/api/customers.js',
                        'resources/js/pos/api/sales.js',
                        'resources/js/pos/api/locations.js',
                    ],
                    'pos-modules': [
                        'resources/js/pos/modules/billing.js',
                        'resources/js/pos/modules/payments.js',
                        'resources/js/pos/modules/discounts.js',
                        'resources/js/pos/modules/imei.js',
                        'resources/js/pos/modules/salesrep.js',
                    ],
                },
            },
        },
    },
});
