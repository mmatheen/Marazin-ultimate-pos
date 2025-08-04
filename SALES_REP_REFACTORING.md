# Sales Rep Module Refactoring - Location Auto-Assignment

## Overview

This refactoring removes redundant location assignment in the `sales_reps` table and makes location automatically derived from `vehicle_locations` relationship.

## Changes Made

### 1. Database Changes

-   **Migration**: `2025_08_02_043928_remove_assigned_location_id_from_sales_reps_table.php`
    -   Removed `assigned_location_id` column from `sales_reps` table
    -   Removed foreign key constraint

### 2. Model Updates

#### SalesRep Model (`app/Models/SalesRep.php`)

-   Removed `assigned_location_id` from `$fillable` array
-   Updated `location()` relationship to use `hasOneThrough` via `VehicleLocation`
-   Added legacy `assignedLocation()` method for backward compatibility

#### Vehicle Model (`app/Models/Vehicle.php`)

-   Updated `salesReps()` relationship from `belongsToMany` to `hasMany`

### 3. Controller Updates

#### SalesRepController (`app/Http/Controllers/Api/SalesRepController.php`)

-   **Validation**: Removed `assigned_location_id` validation
-   **Store Method**:
    -   Validates vehicle is assigned to a location
    -   Prevents assigning vehicle already assigned to another sales rep
    -   Auto-derives location from vehicle_locations
-   **Update Method**: Same validations as store
-   **New Method**: `getAvailableVehicles()` - Returns vehicles assigned to locations but not to sales reps
-   **Response Format**: Uses `location` relationship instead of `assignedLocation`

### 4. API Routes Updates

-   Added new route: `GET /api/sales-reps/available-vehicles`
-   Returns only vehicles that are:
    -   Assigned to locations via `vehicle_locations`
    -   Not already assigned to any sales rep

### 5. Blade View Updates (`resources/views/sales_rep_module/sales_reps/index.blade.php`)

#### Form Changes:

-   Removed location dropdown field
-   Added read-only location display field
-   Added helper text explaining auto-assignment

#### JavaScript Changes:

-   Updated `loadDropdownData()` to use `/api/sales-reps/available-vehicles`
-   Added vehicle change event handler to auto-fill location display
-   Removed `assigned_location_id` error handling
-   Updated DataTable column from `assigned_location.name` to `location.name`

## Business Logic

### Vehicle Assignment Rules:

1. **Vehicle Prerequisites**: Vehicle must be assigned to a location via `vehicle_locations` table
2. **One-to-One Constraint**: Each vehicle can only be assigned to one sales rep at a time
3. **Location Derivation**: Sales rep's location is automatically determined from vehicle's location
4. **Validation**: System prevents assignment if vehicle has no location or is already assigned

### Data Flow:

```
User selects Vehicle → System checks vehicle_locations → Auto-assigns Location → Creates SalesRep record
```

## API Endpoints

### New Endpoint:

-   `GET /api/sales-reps/available-vehicles`
    -   Returns vehicles with locations but without sales rep assignments
    -   Includes location information for UI display

### Updated Endpoints:

-   `POST /api/sales-reps` - Only requires `user_id` and `vehicle_id`
-   `PUT /api/sales-reps/{id}` - Only requires `user_id` and `vehicle_id`
-   `GET /api/sales-reps` - Returns `location` relationship data
-   `GET /api/sales-reps/{id}` - Returns `location` relationship data

## Benefits

1. **Eliminates Redundancy**: Location no longer stored in two places
2. **Data Consistency**: Location always matches vehicle's assigned location
3. **Simplified UI**: Users only select vehicle, location auto-fills
4. **Business Logic**: Enforces proper vehicle-location-salesrep hierarchy
5. **Validation**: Prevents orphaned assignments and conflicts

## Migration Notes

-   **Backward Compatibility**: Legacy `assignedLocation()` method maintained temporarily
-   **Data Integrity**: Existing relationships preserved through `hasOneThrough`
-   **Validation**: Enhanced validation prevents data inconsistencies

## Testing Considerations

1. Test vehicle assignment to sales rep when vehicle has location
2. Test prevention when vehicle has no location
3. Test prevention when vehicle already assigned to another sales rep
4. Test location auto-display in UI when vehicle selected
5. Test edit functionality with existing assignments
6. Test API responses include correct location data
