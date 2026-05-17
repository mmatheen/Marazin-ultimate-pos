@php
    use App\Services\Sms\CustomerSmsOptInPolicy;

    $user = auth()->user();
    $customerAjaxBootstrap = [
        'isSalesRep' => $user->hasRole('Sales Rep'),
        'canViewCustomer' => $user->can('view customer'),
        'canEditCustomer' => $user->can('edit customer'),
        'canDeleteCustomer' => $user->can('delete customer'),
        'canViewAllSales' => $user->can('view all sales') || $user->can('view own sales'),
        'canViewDueReport' => $user->can('view customer-report'),
        'canViewContactDetail' => $user->can('view customer-report'),
        'canViewLedger' => $user->can('view customer-report'),
        'canViewCity' => $user->can('view city'),
        'canManageCustomerSmsOptIn' => CustomerSmsOptInPolicy::canManageOptIn($user),
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
