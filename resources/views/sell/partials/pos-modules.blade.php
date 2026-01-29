{{--
    POS Modular JavaScript Loader
    Include this file in pos.blade.php to load all POS modules
--}}

{{-- Load modules in correct order --}}
<script src="{{ asset('js/pos/pos-cache.js') }}"></script>
<script src="{{ asset('js/pos/pos-customer.js') }}"></script>
<script src="{{ asset('js/pos/pos-product.js') }}"></script>
<script src="{{ asset('js/pos/pos-location.js') }}"></script>
<script src="{{ asset('js/pos/pos-salesrep.js') }}"></script>
<script src="{{ asset('js/pos/pos-controller.js') }}"></script>

<script>
    // Log module loading
    console.log('✅ All POS modules loaded successfully');

    // Set auth user ID for sales rep validation
    window.authUserId = {{ auth()->user()->id ?? 'null' }};
</script>
