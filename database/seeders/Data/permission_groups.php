<?php

/**
 * Canonical permission groups (Spatie group_name) and permission names.
 *
 * @var array<string, list<string>>
 */
return [
    // 1. Authentication & User Management
    '1. user-management' => [
        'create user',
        'edit user',
        'view user',
        'delete user',
        'manage user profile',
        'change user password'
    ],

    '2. role-management' => [
        'create role',
        'edit role',
        'view role',
        'delete role',
        'manage all roles',
        'manage role hierarchy'
    ],

    '3. role-permission-management' => [
        'create role-permission',
        'edit role-permission',
        'view role-permission',
        'delete role-permission',
        'assign permissions'
    ],

    '4. sales-commission-agent-management' => [
        'create sales-commission-agent',
        'edit sales-commission-agent',
        'view sales-commission-agent',
        'delete sales-commission-agent'
    ],

    // 2. Contact Management
    '5. supplier-management' => [
        'create supplier',
        'edit supplier',
        'view supplier',
        'delete supplier',
        'import supplier',
        'export supplier'
    ],

    '6. customer-management' => [
        'create customer',
        'edit customer',
        'view customer',
        'delete customer',
        'import customer',
        'export customer'
    ],

    '7. customer-group-management' => [
        'create customer-group',
        'edit customer-group',
        'view customer-group',
        'delete customer-group'
    ],

    // 3. Product Management
    '8. product-management' => [
        'create product',
        'add product',
        'edit product',
        'view product',
        'delete product',
        'import product',
        'export product',
        'view product history',
        'manage opening stock',
        'view product details',
        'manage product variations',
        'duplicate product',
        'edit batch prices'
    ],

    '9. unit-management' => [
        'create unit',
        'edit unit',
        'view unit',
        'delete unit'
    ],

    '10. brand-management' => [
        'create brand',
        'edit brand',
        'view brand',
        'delete brand'
    ],

    '11. main-category-management' => [
        'create main-category',
        'edit main-category',
        'view main-category',
        'delete main-category'
    ],

    '12. sub-category-management' => [
        'create sub-category',
        'edit sub-category',
        'view sub-category',
        'delete sub-category'
    ],

    '13. warranty-management' => [
        'create warranty',
        'edit warranty',
        'view warranty',
        'delete warranty'
    ],

    '14. variation-management' => [
        'create variation',
        'edit variation',
        'view variation',
        'delete variation',
        'create variation-title',
        'edit variation-title',
        'view variation-title',
        'delete variation-title'
    ],

    // 4. Sales Management
    '15. sale-management' => [
        'view all sales',
        'view own sales',
        'create sale',
        'edit sale',
        'delete sale',
        'view sale details',
        'access pos',
        'print sale invoice',
        'email sale invoice'
    ],

    '16. sale-return-management' => [
        'view sale-return',
        'create sale-return',
        'edit sale-return',
        'delete sale-return',
        'print return invoice'
    ],

    '17. pos-management' => [
        'access pos',
        'create job-ticket',
        'create quotation',
        'save draft',
        'create sale-order',
        'view sale-order',
        'edit sale-order',
        'delete sale-order',
        'convert sale-order to invoice',
        'suspend sale',
        'credit sale',
        'card payment',
        'cheque payment',
        'multiple payment methods',
        'cash payment',
        'discount application',
        'select retail price',
        'select wholesale price',
        'select special price',
        'select max retail price',
        'edit unit price in pos',
        'edit discount in pos',
        'use free quantity',
        'quick price entry'
    ],

    // 5. Purchase Management
    '18. purchase-management' => [
        'view purchase',
        'create purchase',
        'edit purchase',
        'delete purchase',
        'print purchase order',
        'email purchase order',
        // Supplier Free Claim permissions
        'view supplier claims',
        'create supplier claims',
        'receive supplier claims',
    ],

    '19. purchase-return-management' => [
        'view purchase-return',
        'create purchase-return',
        'edit purchase-return',
        'delete purchase-return'
    ],

    // 6. Payment Management
    '20. payment-management' => [
        'view payments',
        'create payment',
        'edit payment',
        'delete payment',
        'bulk sale payment',
        'bulk purchase payment',
        'view payment history',
        'manage cheque',
        'view cheque',
        'approve cheque',
        'reject cheque',
        'view cheque-management'
    ],

    // 6b. Cash Register (POS drawer)
    '20b. cash-register' => [
        'open register',
        'close register',
        'pay in',
        'pay out',
        'add expense from pos',
        'view cash register',
    ],

    // 7. Expense Management
    '21. expense-management' => [
        'create expense',
        'edit expense',
        'view expense',
        'delete expense',
        'approve expense',
        'export expense'
    ],

    '22. parent-expense-management' => [
        'create parent-expense',
        'edit parent-expense',
        'view parent-expense',
        'delete parent-expense'
    ],

    '23. child-expense-management' => [
        'create child-expense',
        'edit child-expense',
        'view child-expense',
        'delete child-expense'
    ],

    // 8. Stock Management
    '24. stock-transfer-management' => [
        'view stock-transfer',
        'create stock-transfer',
        'edit stock-transfer',
        'delete stock-transfer',
        'approve stock-transfer'
    ],

    '25. stock-adjustment-management' => [
        'view stock-adjustment',
        'create stock-adjustment',
        'edit stock-adjustment',
        'delete stock-adjustment'
    ],

    '26. opening-stock-management' => [
        'view opening-stock',
        'create opening-stock',
        'edit opening-stock',
        'import opening-stock',
        'export opening-stock'
    ],

    // 9. Inventory Management
    '27. inventory-management' => [
        'view inventory',
        'adjust inventory',
        'view stock levels',
        'low stock alerts',
        'batch management',
        'imei management'
    ],

    // 10. Location Management
    '28. location-management' => [
        'create location',
        'create sublocation',
        'edit location',
        'view location',
        'delete location',
        'manage location settings'
    ],

    // 11. Discount Management
    '29. discount-management' => [
        'view discount',
        'create discount',
        'edit discount',
        'delete discount'
    ],

    // 12. Sales Rep Management
    '30. sales-rep-management' => [
        'view sales-rep',
        'create sales-rep',
        'edit sales-rep',
        'delete sales-rep',
        'assign routes',
        'view assigned routes',
        'manage sales targets',
        'view sales rep performance'
    ],

    // 13. Route Management
    '31. route-management' => [
        'view routes',
        'create route',
        'edit route',
        'delete route',
        'assign cities to route'
    ],

    // 14. Vehicle Management
    '32. vehicle-management' => [
        'view vehicles',
        'create vehicle',
        'edit vehicle',
        'delete vehicle',
        'track vehicle',
        'assign vehicle to location'
    ],

    // 15. Reports Management
    '33. report-management' => [
        'view daily-report',
        'view sales-report',
        'view purchase-report',
        'view stock-report',
        'view profit-loss-report',
        'view payment-report',
        'view customer-report',
        'view supplier-report',
        'view expense-report',
        'export reports'
    ],

    // 16. Settings Management
    '34. settings-management' => [
        'view settings',
        'edit business-settings',
        'edit backorder-settings',
        'edit tax-settings',
        'edit email-settings',
        'edit sms-settings',
        'sms.send',
        'backup database',
        'restore database',
        'manage currencies',
        'manage selling-price-groups'
    ],

    // 17. Print & Label Management
    '35. print-label-management' => [
        'print product-labels',
        'print barcodes',
        'design labels',
        'batch print labels'
    ],

    // 18. Dashboard Management
    '36. dashboard-management' => [
        'view dashboard',
        'view sales-analytics',
        'view purchase-analytics',
        'view stock-analytics',
        'view financial-overview'
    ],

    // 19. Import/Export Management
    '37. import-export-management' => [
        'import products',
        'export products',
        'import customers',
        'export customers',
        'import suppliers',
        'export suppliers',
        'import opening-stock',
        'export opening-stock',
        'download templates'
    ],

    // 20. Profile Management
    '38. profile-management' => [
        'view own-profile',
        'edit own-profile',
        'change own-password'
    ],

    // 21. Master Admin Management (Only for Master Super Admin)
    '39. master-admin-management' => [
        'access master admin panel',
        'manage all locations',
        'manage all roles',
        'manage role hierarchy',
        'create super admin',
        'edit super admin',
        'delete super admin',
        'view all shops data',
        'system wide reports',
        'global settings',
        'manage system backups',
        'view system logs',
        'manage master permissions',
        'override location scope',
        'manage system roles',
        'access production database',
        'manage system maintenance'
    ]
];
