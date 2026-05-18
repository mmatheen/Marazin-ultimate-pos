<?php

/**
 * Recommended permissions for an "Order" role (POS + sale orders + credit sales).
 * Apply manually in Role & Permissions, or add this role to DefaultRoleMatrixBuilder on first create.
 *
 * @return list<string>
 */
return [
    'view dashboard',
    'access pos',
    'view product',
    'view product details',
    'view customer',
    'create customer',
    'view location',
    'view main-category',
    'view sub-category',
    'view brand',
    'view own sales',
    'create sale',
    'edit sale',
    'view sale details',
    'print sale invoice',
    'create sale-order',
    'view sale-order',
    'edit sale-order',
    'save draft',
    'credit sale',
    'cash payment',
    'card payment',
    'select retail price',
    'open register',
    'close register',
    'view cash register',
    'pay in',
    'pay out',
    'view own-profile',
    'edit own-profile',
    'change own-password',
];
