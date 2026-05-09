@php
    $customerAjaxBootstrap = [
        'isSalesRep' => auth()->user()->hasRole('Sales Rep'),
        'canViewCustomer' => auth()->user()->can('view customer'),
        'canEditCustomer' => auth()->user()->can('edit customer'),
        'canDeleteCustomer' => auth()->user()->can('delete customer'),
        'routes' => [
            'listSale' => route('list-sale'),
            'dueReport' => route('due.report'),
            'viewContactBase' => rtrim(url('/customer/view-contact'), '/'),
        ],
    ];
@endphp
<script>
    window.CustomerAjaxBootstrap = @json($customerAjaxBootstrap);
</script>
@vite(['resources/js/contact/customer_ajax.js'])
