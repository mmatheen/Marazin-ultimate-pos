<?php

/**
 * Recommended default permissions for a "Cashier" role (POS-focused).
 * Used only when the role is first created via BuiltInRoleBootstrapper.
 * Existing Cashier roles keep whatever an admin assigned in Role & Permissions.
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
    'view own sales',
    'create sale',
    'cash payment',
    'card payment',
    'credit sale',
    'view location',
    'view main-category',
    'view sub-category',
    'view brand',
    'select retail price',
    'save draft',
    'suspend sale',
    'open register',
    'close register',
    'pay in',
    'pay out',
    'view cash register',
    'view own-profile',
    'edit own-profile',
    'change own-password',
];
