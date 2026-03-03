{{-- ===========================================================
     POS SERVER CONFIG BRIDGE
     Single source of truth for all PHP-injected values.
     Included BEFORE pos_ajax.blade.php on every POS page.
     Future JS modules will read from window.PosConfig.*
     instead of inline Blade expressions.
     =========================================================== --}}
<script>
    window.PosConfig = {

        // --- Permissions (mirrors loose consts already defined in pos.blade.php) ---
        permissions: {
            allowedPriceTypes:      {!! json_encode($allowedPriceTypes ?? ['retail', 'wholesale', 'special', 'max_retail']) !!},
            canEditUnitPrice:       {!! json_encode($canEditUnitPrice ?? true) !!},
            canEditDiscount:        {!! json_encode($canEditDiscount ?? true) !!},
            priceValidationEnabled: {!! json_encode((int)($priceValidationEnabled ?? 1)) !!},
            canEditSale:            {!! json_encode(auth()->check() && auth()->user()->can('edit sale')) !!},
            canDeleteSale:          {!! json_encode(auth()->check() && auth()->user()->can('delete sale')) !!},
            canEditProduct:         {!! json_encode(auth()->check() && auth()->user()->can('edit product')) !!},
            canDeleteProduct:       {!! json_encode(auth()->check() && auth()->user()->can('delete product')) !!},
        },

        // --- Free Quantity feature flags ---
        freeQty: {
            enabled: {!! json_encode((bool)($freeQtyEnabled ?? true)) !!},
            canUse:  {!! json_encode((bool)($canUseFreeQty ?? false)) !!},
        },

        // --- Auth ---
        auth: {
            userId:         {!! json_encode(auth()->user()->id ?? null) !!},
            isSalesRepUser: {!! json_encode(auth()->check() && auth()->user()->roles->contains(fn($r) => $r->name === 'Sales Rep' || $r->name === 'sales rep' || $r->key === 'sales_rep')) !!},
        },

        // --- Misc item (Cash Item quick-entry product) ---
        miscItemProductId: {{ $miscItemProductId ?? 0 }},

        // --- API Routes ---
        // Hardcoded URLs currently scattered inline in pos_ajax.blade.php.
        // Every future JS module reads from here instead of embedding strings.
        routes: {
            // Same as stock adjustment, sale return, purchase, etc. (Web ProductController)
            productAutocomplete:   '/products/stocks/autocomplete',
            productDetails:        '/initial-product-details',
            productStocks:         '/products/stocks',
            productQuickAdd:       '/products/quick-add',
            salesStore:            '/sales/store',
            salesUpdate:           '/sales/update/',      // append sale ID
            salesEdit:             '/sales/edit/',         // append sale ID
            salesSuspended:        '/sales/suspended',
            salesDeleteSuspended:  '/sales/delete-suspended/', // append sale ID
            salesPrintTransaction: '/sales/print-recent-transaction/', // append sale ID
            categories:            '/categories',
            subcategories:         '/subcategories/',      // append category ID
            brands:                '/brands',
            locations:             '/locations',
            customerById:          '/customer-get-by-id/', // append customer ID
            customerCreditInfo:    '/customer/credit-info/', // append customer ID
            customerPreviousPrice: '/customer-previous-price',
            logPricingError:       '/pos/log-pricing-error',
            salesRepCheckStatus:   '/sales-reps/check-status',
        },

        // --- Sound asset URLs ---
        sounds: {
            success: '{{ asset('assets/sounds/success.mp3') }}',
            error:   '{{ asset('assets/sounds/error.mp3') }}',
            warning: '{{ asset('assets/sounds/warning.mp3') }}',
        },

        // --- Dashboard URL (for Go Back / Go Home navigation) ---
        dashboardUrl: "{{ route('dashboard') }}",

    };

    // ── Backward-compatible loose globals ──────────────────────────
    // Some JS modules still reference these as bare variable names.
    // They mirror values already in window.PosConfig above.
    const allowedPriceTypes      = window.PosConfig.permissions.allowedPriceTypes;
    const canEditUnitPrice       = window.PosConfig.permissions.canEditUnitPrice;
    const canEditDiscount        = window.PosConfig.permissions.canEditDiscount;
    const priceValidationEnabled = window.PosConfig.permissions.priceValidationEnabled;
    const canEditSale            = window.PosConfig.permissions.canEditSale;
    const canDeleteSale          = window.PosConfig.permissions.canDeleteSale;
    const canEditProduct         = window.PosConfig.permissions.canEditProduct;
    const canDeleteProduct       = window.PosConfig.permissions.canDeleteProduct;

    const freeQtyEnabled    = window.PosConfig.freeQty.enabled;
    const canUseFreeQty     = window.PosConfig.freeQty.canUse;
    const showFreeQtyColumn = freeQtyEnabled && canUseFreeQty;

    const userId            = window.PosConfig.auth.userId;
    const miscItemProductId = window.PosConfig.miscItemProductId;

    window.dashboardUrl     = window.PosConfig.dashboardUrl;
</script>
