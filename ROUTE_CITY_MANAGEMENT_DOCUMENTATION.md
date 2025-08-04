# Route City Management System - Implementation Documentation

## Overview

This document outlines the comprehensive implementation of a Route City Management system for the Laravel Sales Rep Module. The system enables sales representatives to be assigned to routes, which in turn can be linked to multiple cities, while maintaining data integrity and preventing duplicates.

## Database Architecture

### Tables Structure

1. **vehicles** - Vehicle management
2. **vehicle_locations** - Links vehicles to locations
3. **sales_reps** - Sales representative records (user_id, vehicle_id)
4. **cities** - City master data (id, name, district, province)
5. **routes** - Route records (id, name, sales_rep_id)
6. **route_cities** - Many-to-many relationship between routes and cities

### Key Relationships

-   Sales Rep → Vehicle (belongsTo)
-   Sales Rep → Location (hasOneThrough vehicle_locations)
-   Sales Rep → Routes (hasMany)
-   Route → Cities (belongsToMany through route_cities)
-   Route → Sales Rep (belongsTo)

## API Controllers

### 1. RouteCityController (Enhanced)

**Location:** `app/Http/Controllers/Api/RouteCityController.php`

**Key Features:**

-   **index()** - Lists all route-city assignments with detailed information
-   **store()** - Assigns multiple cities to a route with duplicate prevention
-   **show()** - Shows all cities linked to a specific route
-   **update()** - Updates cities assigned to a route (replaces existing)
-   **destroy()** - Removes a city from a route
-   **getAvailableCities()** - Helper method to get unassigned cities for a route

**Response Format:**

```json
{
    "status": true,
    "message": "Route-city assignments retrieved successfully.",
    "data": [...],
    "errors": {...}
}
```

**Validation Rules:**

-   route_id must exist in routes table
-   city_ids must be array of existing city IDs
-   Prevents duplicate route-city combinations
-   Transaction-based operations with rollback on failure

### 2. RouteController (Enhanced)

**Location:** `app/Http/Controllers/Api/RouteController.php`

**New Features:**

-   Enhanced to support sales rep assignment
-   Validation to ensure one route per sales rep
-   Route deletion removes associated city assignments
-   **getAvailableSalesReps()** - Returns sales reps not assigned to any route

**Key Improvements:**

-   Comprehensive error handling
-   Transaction support
-   Relationship loading for performance
-   Proper validation and business logic enforcement

### 3. SalesRepController (Enhanced)

**Location:** `app/Http\Controllers\Api\SalesRepController.php`

**Enhanced Features:**

-   **index()** method now includes route and cities information
-   Returns structured data with route details and assigned cities
-   Maintains backward compatibility

## Web Controllers

### 1. Web RouteCityController

**Location:** `app/Http/Controllers/Web/RouteCityController.php`

**Features:**

-   Full CRUD operations for route-city management
-   DataTables integration
-   Form validation
-   Helper methods for AJAX requests
-   **getData()** - Provides data for DataTables
-   **getAvailableCities()** - AJAX endpoint for available cities
-   **getAssignedCities()** - AJAX endpoint for assigned cities

## Routes

### API Routes

**Location:** `routes/api.php`

```php
// Core resource routes
Route::apiResource('vehicles', VehicleController::class);
Route::apiResource('vehicle-locations', VehicleLocationController::class);
Route::apiResource('sales-reps', SalesRepController::class);
Route::apiResource('routes', RouteController::class);
Route::apiResource('cities', CityController::class);
Route::apiResource('route-cities', RouteCityController::class);
Route::apiResource('sales-rep-targets', SalesRepTargetController::class);

// Helper routes
Route::get('/routes/available-sales-reps', [RouteController::class, 'getAvailableSalesReps']);
Route::get('/route-cities/available-cities/{routeId}', [RouteCityController::class, 'getAvailableCities']);
```

### Web Routes

**Location:** `routes/web.php`

```php
Route::group(['prefix' => 'sales-rep'], function () {
    // Route Cities Management
    Route::get('/route-cities', [RouteCityController::class, 'index'])->name('route-cities.index');
    Route::get('/route-cities/create', [RouteCityController::class, 'create'])->name('route-cities.create');
    Route::post('/route-cities', [RouteCityController::class, 'store'])->name('route-cities.store');
    Route::get('/route-cities/{id}', [RouteCityController::class, 'show'])->name('route-cities.show');
    Route::get('/route-cities/{id}/edit', [RouteCityController::class, 'edit'])->name('route-cities.edit');
    Route::put('/route-cities/{id}', [RouteCityController::class, 'update'])->name('route-cities.update');
    Route::delete('/route-cities/{id}', [RouteCityController::class, 'destroy'])->name('route-cities.destroy');

    // AJAX Helper Routes
    Route::get('/route-cities-data', [RouteCityController::class, 'getData'])->name('route-cities.data');
    Route::get('/route-cities/available-cities/{routeId}', [RouteCityController::class, 'getAvailableCities']);
    Route::get('/route-cities/assigned-cities/{routeId}', [RouteCityController::class, 'getAssignedCities']);
});
```

## Blade Views

### 1. Route Cities Index

**Location:** `resources/views/sales_rep_module/route_cities/index.blade.php`

**Features:**

-   DataTables integration with server-side processing
-   Real-time search and filtering
-   Action buttons (View, Edit, Delete)
-   Responsive design
-   Auto-refreshing data
-   Success/Error message handling

### 2. Route Cities Create

**Location:** `resources/views/sales_rep_module/route_cities/create.blade.php`

**Features:**

-   Route selection dropdown
-   Multi-city selection with grid layout
-   Province filtering
-   Select All/Deselect All functionality
-   Real-time selection counter
-   Already assigned city detection
-   Form validation

### 3. Route Cities Edit

**Location:** `resources/views/sales_rep_module/route_cities/edit.blade.php`

**Features:**

-   Route information display
-   Current assignments visualization
-   City search functionality
-   Province filtering
-   Bulk city management
-   Pre-selected current assignments

### 4. Route Cities Show

**Location:** `resources/views/sales_rep_module/route_cities/show.blade.php`

**Features:**

-   Detailed route information
-   Cities organized by province tabs
-   Search functionality
-   City cards with hover effects
-   Empty state handling
-   Edit route cities action

### 5. Enhanced Sales Rep Index

**Location:** `resources/views/sales_rep_module/sales_reps/index.blade.php`

**New Features:**

-   Added Route and Cities columns to DataTable
-   Route management buttons
-   City count and preview
-   Action buttons for route assignment
-   Enhanced UI with better information display

## Key Features Implemented

### 1. Data Integrity

-   Unique route-city combinations enforced
-   One route per sales rep validation
-   Vehicle-location validation before assignment
-   Transaction-based operations

### 2. User Experience

-   Intuitive multi-city selection interface
-   Real-time search and filtering
-   Province-based organization
-   Responsive design for mobile devices
-   Clear success/error messaging

### 3. Performance Optimization

-   Efficient relationship loading
-   Server-side DataTables processing
-   AJAX-based updates
-   Optimized database queries

### 4. Business Logic

-   Location auto-resolution from vehicle assignments
-   Route assignment workflow
-   City availability checking
-   Duplicate prevention mechanisms

## API Endpoints Summary

### Route Cities

-   `GET /api/route-cities` - List all assignments
-   `POST /api/route-cities` - Assign cities to route
-   `GET /api/route-cities/{id}` - Show route cities
-   `PUT /api/route-cities/{id}` - Update route cities
-   `DELETE /api/route-cities/{id}` - Remove city from route
-   `GET /api/route-cities/available-cities/{routeId}` - Get available cities

### Routes

-   `GET /api/routes` - List routes with sales rep and cities
-   `POST /api/routes` - Create route with sales rep assignment
-   `GET /api/routes/{id}` - Show route details
-   `PUT /api/routes/{id}` - Update route and sales rep
-   `DELETE /api/routes/{id}` - Delete route and city assignments
-   `GET /api/routes/available-sales-reps` - Get unassigned sales reps

### Sales Reps

-   `GET /api/sales-reps` - List with route and cities information
-   Other CRUD operations remain unchanged

## Usage Examples

### 1. Assign Multiple Cities to Route

```javascript
// POST /api/route-cities
{
    "route_id": 1,
    "city_ids": [1, 2, 3, 4]
}
```

### 2. Create Route with Sales Rep

```javascript
// POST /api/routes
{
    "name": "Northern Route",
    "sales_rep_id": 2
}
```

### 3. Update Route Cities

```javascript
// PUT /api/route-cities/1
{
    "city_ids": [1, 2, 5, 6]
}
```

## Future Enhancements

1. **Route Optimization** - Add distance calculation between cities
2. **Performance Metrics** - Track sales rep performance by route
3. **Route Analytics** - Generate reports on route efficiency
4. **Mobile App Support** - API-ready for mobile applications
5. **Route Scheduling** - Add time-based route planning
6. **Bulk Operations** - Enhanced bulk city assignment features

## Testing Recommendations

1. **Unit Tests** - Test model relationships and validation rules
2. **Feature Tests** - Test API endpoints and business logic
3. **Browser Tests** - Test web interface functionality
4. **Data Integrity Tests** - Verify duplicate prevention and constraints

## Deployment Notes

1. Ensure all migrations are run
2. Verify relationship configurations in models
3. Test API endpoints for proper response formats
4. Validate web interface functionality
5. Check permission and authentication settings

This implementation provides a robust, scalable solution for managing route-city assignments within the Sales Rep Module, maintaining data integrity while providing an excellent user experience.
