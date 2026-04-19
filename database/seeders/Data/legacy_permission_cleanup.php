<?php

/**
 * Legacy DB cleanup: old group labels, old permission labels, and known duplicate pairs.
 * Used by LegacyPermissionMigrator — edit here, not in PHP logic.
 */
return [
    'group_name_renames' => [
        '1. user management' => '1. user-management',
        '2. role management' => '2. role-management',
        '3. role & permission-management' => '3. role-permission-management',
        '12. sub-catagory-management' => '12. sub-category-management',
        '18. product-purchase-management' => '18. purchase-management',
        '19. product-purchase-return-management' => '19. purchase-return-management',
        '22. stock-adjustment-management' => '24. stock-adjustment-management',
        '23. stock-adjustment-management' => '24. stock-adjustment-management',
        '27. pos-button-management' => '17. pos-management',
        '26. product-discount-management' => '28. discount-management',
    ],

    'permission_name_migrations' => [
        'edit sub-catagory' => 'edit sub-category',
        'view sub-catagory' => 'view sub-category',
        'delete sub-catagory' => 'delete sub-category',
        'Add & Edit Opening Stock product' => 'manage opening stock',
        'product Full History' => 'view product history',
        'show one product details' => 'view product details',
        'all sale' => 'view all sales',
        'own sale' => 'view own sales',
        'pos page' => 'access pos',
        'view return-sale' => 'view sale-return',
        'add return-sale' => 'create sale-return',
        'add stock-transfer' => 'create stock-transfer',
        'add stock-adjustment' => 'create stock-adjustment',
        'add purchase' => 'create purchase',
        'add purchase-return' => 'create purchase-return',
        'view import-product' => 'import product',
        'create import-product' => 'import product',
    ],

    'duplicate_canonical_merge' => [
        'add product' => 'create product',
        'add sale' => 'create sale',
    ],
];
