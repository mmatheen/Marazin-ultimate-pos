# Batch Prices Editing Feature

## Overview
This feature allows you to edit batch prices for products directly from the Product List page. You can modify all price types except the original cost price (unit_cost).

## How to Use

1. **Access the Feature**:
   - Go to the Product List page (`/list-product`)
   - Find the product you want to edit batch prices for
   - Click on the "Actions" dropdown for that product
   - Select "Edit Batch Prices" (shown with a dollar sign icon)

2. **Edit Batch Prices Modal**:
   - The modal will display the product name and SKU at the top
   - A table showing all batches for that product with:
     - Batch Number
     - Current Stock Quantity
     - Cost Price (read-only, not editable)
     - Wholesale Price (editable)
     - Special Price (editable)
     - Retail Price (editable)
     - Maximum Retail Price (editable)
     - Expiry Date (display only)
     - Locations with stock quantities

3. **Making Changes**:
   - Edit any of the price fields (all except Cost Price)
   - All prices must be numeric and greater than or equal to 0
   - Changes are validated before saving

4. **Save Changes**:
   - Click "Save Changes" to update all batch prices
   - The system will validate all inputs
   - Success/error messages will be displayed
   - The product table will refresh to show updated data

## Technical Implementation

### Backend (Laravel)
- **Routes**: Added in `routes/web.php`
  - `GET /product/{productId}/batches` - Fetch batch data
  - `POST /batches/update-prices` - Update batch prices

- **Controller Methods**: Added to `ProductController.php`
  - `getProductBatches($productId)` - Returns product and batch data
  - `updateBatchPrices(Request $request)` - Updates batch prices with validation

### Frontend (JavaScript/HTML)
- **Modal**: Added to `product.blade.php`
- **JavaScript Functions**: Added to `product_ajax.blade.php`
  - `loadBatchPricesModal(productId)` - Loads batch data via AJAX
  - `populateBatchPricesModal(product, batches)` - Populates the modal
  - Save functionality with validation and error handling

### Features
- **Input Validation**: All prices must be numeric and >= 0
- **Error Handling**: Comprehensive error messages for validation failures
- **User Experience**: Loading states, success/error notifications
- **Data Integrity**: Cost price (unit_cost) is protected from modification
- **Real-time Updates**: Product table refreshes after successful updates

## Database Impact
- Updates the `batches` table with new price values
- Does not modify the `unit_cost` field (original purchase cost)
- Maintains data integrity and audit trail

## Security
- CSRF token protection
- Input validation and sanitization
- Authorization through existing middleware
- SQL injection protection via Eloquent ORM

## Notes
- Only active products with existing batches can have their prices edited
- The feature respects existing permissions (requires product edit permission)
- All changes are immediate and permanent once saved
- Cost price cannot be modified to maintain purchase history integrity