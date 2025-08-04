# Sales Rep Module - Complete CRUD Implementation

## Summary

I have successfully created a complete CRUD system for the Sales Rep Module with controllers, API endpoints, Blade views, and Laravel routes that match the structure and coding style of the existing `VehicleController`.

## Created/Updated Models

### 1. City Model (`app/Models/City.php`)

-   **Table**: `cities`
-   **Fillable**: `name`, `district`, `province`
-   **Relationships**: `routes()`, `routeCities()`

### 2. RouteCity Model (`app/Models/RouteCity.php`)

-   **Table**: `route_cities`
-   **Fillable**: `route_id`, `city_id`
-   **Relationships**: `route()`, `city()`

### 3. SalesRepTarget Model (`app/Models/SalesRepTarget.php`)

-   **Table**: `sales_rep_targets`
-   **Fillable**: `sales_rep_id`, `target_amount`, `achieved_amount`, `target_month`
-   **Casts**: `target_amount`, `achieved_amount`, `target_month`
-   **Relationships**: `salesRep()`

### 4. Updated SalesRep Model (`app/Models/SalesRep.php`)

-   **Table**: `sales_reps`
-   **Fillable**: `user_id`, `vehicle_id`, `assigned_location_id`
-   **Relationships**: `user()`, `vehicle()`, `assignedLocation()`, `routes()`, `targets()`

### 5. Updated Route Model (`app/Models/Route.php`)

-   **Table**: `routes`
-   **Fillable**: `name`, `sales_rep_id`
-   **Relationships**: `salesRep()`, `cities()`, `routeCities()`

## Created API Controllers

### 1. CityController (`app/Http/Controllers/Api/CityController.php`)

-   **CRUD Operations**: index, store, show, update, destroy
-   **Validation**: name (required, unique), district, province
-   **Features**: Uses DB transactions, handles relationships cleanup

### 2. RouteCityController (`app/Http/Controllers/Api/RouteCityController.php`)

-   **CRUD Operations**: index, store, show, update, destroy
-   **Validation**: route_id, city_id (with exists checks)
-   **Features**: Prevents duplicate route-city combinations

### 3. SalesRepTargetController (`app/Http/Controllers/Api/SalesRepTargetController.php`)

-   **CRUD Operations**: index, store, show, update, destroy
-   **Validation**: sales_rep_id, target_amount, achieved_amount, target_month
-   **Features**: Prevents duplicate targets per sales rep per month

### 4. RouteController (`app/Http/Controllers/Api/RouteController.php`)

-   **CRUD Operations**: index, store, show, update, destroy
-   **Validation**: name, sales_rep_id, city_ids (array)
-   **Features**: Handles multiple cities attachment, uses sync for updates

## API Routes Added (`routes/api.php`)

```php
// Sales rep and vehicle routes
Route::apiResource('vehicles', VehicleController::class);
Route::apiResource('vehicle-locations', VehicleLocationController::class);
Route::apiResource('sales-reps', SalesRepController::class);
Route::apiResource('routes', RouteController::class);
Route::apiResource('cities', CityController::class);
Route::apiResource('route-cities', RouteCityController::class);
Route::apiResource('sales-rep-targets', SalesRepTargetController::class);

// Helper endpoints for dropdowns
Route::get('/users', function() { /* Users for dropdowns */ });
Route::get('/locations', function() { /* Locations for dropdowns */ });
```

## Created Web Controllers

### 1. CityController (`app/Http/Controllers/Web/CityController.php`)

-   Returns `sales_rep_module.cities.index` view

### 2. SalesRepController (`app/Http/Controllers/Web/SalesRepController.php`)

-   Returns `sales_rep_module.sales_reps.index` view

### 3. RouteController (`app/Http/Controllers/Web/RouteController.php`)

-   Returns `sales_rep_module.routes.index` view

### 4. SalesRepTargetController (`app/Http/Controllers/Web/SalesRepTargetController.php`)

-   Returns `sales_rep_module.targets.index` view

## Web Routes Added (`routes/web.php`)

```php
Route::group(['prefix' => 'sales-rep'], function () {
    Route::get('/vehicles', [VehicleController::class, 'create']);
    Route::get('/vehicle-locations', [VehicleLocationController::class, 'create']);
    Route::get('/sales-reps', [SalesRepController::class, 'create']);
    Route::get('/routes', [RouteController::class, 'create']);
    Route::get('/cities', [CityController::class, 'create']);
    Route::get('/targets', [SalesRepTargetController::class, 'create']);
});
```

## Created Blade Views

### 1. Cities (`resources/views/sales_rep_module/cities/index.blade.php`)

-   **Features**: DataTable with name, district, province
-   **CRUD**: Add/Edit modal, Delete confirmation
-   **AJAX**: Full CRUD operations via API

### 2. Sales Reps (`resources/views/sales_rep_module/sales_reps/index.blade.php`)

-   **Features**: DataTable with user, vehicle, location details
-   **CRUD**: Add/Edit modal with dropdowns, Delete confirmation
-   **AJAX**: Loads users, vehicles, locations dynamically

### 3. Routes (`resources/views/sales_rep_module/routes/index.blade.php`)

-   **Features**: DataTable with route name, sales rep, cities
-   **CRUD**: Add/Edit modal with checkbox cities selection
-   **AJAX**: Multi-city assignment with sync operations

### 4. Sales Rep Targets (`resources/views/sales_rep_module/targets/index.blade.php`)

-   **Features**: DataTable with targets, achievements, percentage
-   **CRUD**: Add/Edit modal with amount fields
-   **AJAX**: Achievement percentage calculation

## API Response Format

All controllers follow the consistent response format:

```json
{
    "status": boolean,
    "message": "string",
    "data": object|array,
    "errors": object (on validation failure)
}
```

## Key Features Implemented

1. **Database Transactions**: All create/update/delete operations use `DB::beginTransaction()` and `DB::commit()`
2. **Validation**: Comprehensive validation rules based on migration constraints
3. **Relationship Management**: Proper cleanup of relationships on delete operations
4. **Unique Constraints**: Prevents duplicate combinations where applicable
5. **Error Handling**: 404 responses when data not found, proper error messaging
6. **Eager Loading**: Efficient queries with relationship loading
7. **Consistent Styling**: Matches existing VehicleController structure and response format

## Access URLs

-   **Cities**: `/sales-rep/cities`
-   **Sales Representatives**: `/sales-rep/sales-reps`
-   **Routes**: `/sales-rep/routes`
-   **Sales Rep Targets**: `/sales-rep/targets`
-   **Vehicles**: `/sales-rep/vehicles` (existing)
-   **Vehicle Locations**: `/sales-rep/vehicle-locations` (existing)

## API Endpoints

All endpoints follow RESTful conventions:

-   `GET /api/{resource}` - List all
-   `POST /api/{resource}` - Create new
-   `GET /api/{resource}/{id}` - Show specific
-   `PUT /api/{resource}/{id}` - Update specific
-   `DELETE /api/{resource}/{id}` - Delete specific

The implementation is complete and ready for use!
