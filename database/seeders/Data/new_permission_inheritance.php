<?php

/**
 * Grants for non–Master/Super roles when a new permission appears in the catalog.
 * A role receives `key` only if it already has at least one permission from `value`.
 *
 * Master Super Admin and Super Admin are handled separately in the seeder
 * (see grantNewCatalogPermissionsToAdmins).
 *
 * @return array<string, list<string>>
 */
return [
    'create sale-order' => ['save draft', 'create sale'],
    'view sale-order' => ['save draft', 'view all sales', 'view own sales'],
    'edit sale-order' => ['save draft', 'create sale'],
    'manage cheque' => ['cheque payment', 'create payment'],
    'view cheque' => ['cheque payment', 'view payments'],
    'approve cheque' => ['cheque payment', 'edit payment'],
    'reject cheque' => ['cheque payment', 'edit payment'],
    'view cheque-management' => ['cheque payment', 'view payments'],

    'view supplier claims' => ['view purchase', 'create purchase', 'view supplier'],
    'create supplier claims' => ['create purchase', 'edit purchase'],
    'receive supplier claims' => ['create purchase', 'edit purchase', 'view purchase'],

    'use free quantity' => ['access pos', 'create sale'],

    'open register' => ['access pos'],
    'close register' => ['access pos'],
    'pay in' => ['access pos'],
    'pay out' => ['access pos'],
    'add expense from pos' => ['access pos', 'create expense'],
    'view cash register' => ['access pos'],

    // Backorder module toggle — anyone who could edit general business settings gets the new granular permission
    'edit backorder-settings' => ['edit business-settings'],
];
