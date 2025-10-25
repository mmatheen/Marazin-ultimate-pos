# 📊 Stock Report - Location-wise Implementation Guide

## Overview
This implementation provides a **location-wise product stock report** that allows users to filter and view current stock levels across different business locations.

---

## 🎯 Features Implemented

### 1. **Multi-Filter System**
- **Business Location**: Filter by specific location or view all locations
- **Category**: Filter by main category
- **Sub Category**: Filter by sub-category
- **Brand**: Filter by product brand
- **Unit**: Filter by product unit type

### 2. **Summary Dashboard Cards**
Four beautiful gradient cards showing:
- 💰 **Closing Stock (By Purchase Price)**: Total inventory value at cost price
- 💵 **Closing Stock (By Sale Price)**: Total inventory value at selling price
- 📈 **Potential Profit**: Expected profit from current inventory
- 📊 **Profit Margin %**: Overall profit percentage

### 3. **Detailed Stock Table**
Displays for each product:
- ✅ Actions dropdown
- 🏷️ SKU
- 📦 Product Name
- 🔄 Variation
- 📁 Category
- 📍 Location
- 💲 Unit Selling Price
- 📊 Current Stock (with color-coded badges)
- 💰 Stock Value (Purchase Price)
- 💵 Stock Value (Sale Price)
- 📈 Potential Profit
- 📤 Total Unit Sold
- 🔄 Total Unit Transferred
- ⚙️ Total Unit Adjusted

### 4. **Export Options**
- 📄 CSV Export
- 📊 Excel Export
- 🖨️ Print
- 👁️ Column Visibility
- 📕 PDF Export

---

## 📁 Files Modified/Created

### 1. **Controller**: `app/Http/Controllers/ReportController.php`

#### Method: `stockHistory(Request $request)`

**What it does:**
- Fetches all locations, categories, brands, and units for filter dropdowns
- Builds a query to get products based on selected filters
- Calculates stock values and potential profits for each product-location combination
- Returns data to the view

**Key Logic:**
```php
// Location-specific stock
$locationData = $product->locations->where('id', $request->location_id)->first();
$currentStock = $locationData->pivot->qty ?? 0;

// Calculations
$stockByPurchasePrice = $currentStock * $product->original_price;
$stockBySalePrice = $currentStock * $product->retail_price;
$potentialProfit = $stockBySalePrice - $stockByPurchasePrice;
$profitMargin = ($potentialProfit / $stockByPurchasePrice) * 100;
```

---

### 2. **View**: `resources/views/reports/stock_report.blade.php`

**Structure:**
1. **Header Section**: Breadcrumbs and page title
2. **Filter Section**: Form with 5 filter dropdowns
3. **Summary Cards**: 4 gradient cards with totals
4. **Action Buttons**: Export and visibility controls
5. **Data Table**: Interactive DataTable with stock information

**Key Features:**
- Select2 dropdowns for better UX
- DataTables for sorting, searching, and pagination
- Responsive design
- Print-friendly layout
- Color-coded stock badges (Green: >10, Yellow: 1-10, Red: 0)

---

## 🗄️ Database Structure

### Tables Used:

#### 1. **products**
```sql
- id
- product_name
- sku
- unit_id (FK)
- brand_id (FK)
- main_category_id (FK)
- sub_category_id (FK)
- original_price (purchase price)
- retail_price (selling price)
- whole_sale_price
- ...
```

#### 2. **location_product** (Pivot Table)
```sql
- id
- product_id (FK)
- location_id (FK)
- qty (current stock quantity)
- created_at
- updated_at
```

#### 3. **locations**
```sql
- id
- name
- location_id
- address
- ...
```

---

## 🔄 Data Flow

1. **User selects filters** (Location, Category, Brand, etc.)
2. **Form submits** → GET request to `stock.report` route
3. **Controller receives filters** → Builds query
4. **Query fetches products** with relationships (category, brand, locations)
5. **Loop through products** → Get location-specific stock from pivot table
6. **Calculate values**:
   - Stock Value (Purchase) = qty × original_price
   - Stock Value (Sale) = qty × retail_price
   - Potential Profit = Stock Value (Sale) - Stock Value (Purchase)
7. **Return to view** with `$stockData` and `$summaryData`
8. **View renders** → Display in DataTable

---

## 🎨 UI/UX Features

### Color Coding:
- **Stock Badges**:
  - 🟢 Green: Stock > 10 units
  - 🟡 Yellow: Stock 1-10 units
  - 🔴 Red: Out of stock

### Gradient Cards:
- **Info Card**: Purple gradient
- **Success Card**: Pink-red gradient
- **Warning Card**: Blue gradient
- **Primary Card**: Green gradient

### Responsive Design:
- Bootstrap 5 grid system
- Mobile-friendly tables
- Collapsible filters on small screens

---

## 🚀 How to Use

### Step 1: Navigate to Stock Report
```
Menu → Reports → Stock Report
```

### Step 2: Apply Filters
1. Select **Business Location** (or leave as "All locations")
2. Choose **Category** (optional)
3. Select **Sub Category** (optional)
4. Pick **Brand** (optional)
5. Choose **Unit** (optional)
6. Click **Filter** button

### Step 3: View Results
- Summary cards update with totals
- Table shows filtered products
- Use search, sort, and pagination

### Step 4: Export (Optional)
- Click **Export CSV**, **Excel**, or **PDF**
- Or use **Print** button

---

## 📊 Example Query Result

**Filter:** Location = "Main Store"

**Result:**
```
SKU001 | Product A | Category: Electronics | Location: Main Store
- Current Stock: 50 units
- Stock Value (Purchase): $2,500 (50 × $50)
- Stock Value (Sale): $3,500 (50 × $70)
- Potential Profit: $1,000
```

---

## 🔧 Customization Options

### Add More Filters:
Edit `stockHistory()` method in `ReportController.php`:
```php
if ($request->has('supplier_id') && $request->supplier_id != '') {
    $query->where('supplier_id', $request->supplier_id);
}
```

### Change Currency Symbol:
In `stock_report.blade.php`, replace `$` with your currency:
```blade
Rs. {{ number_format($stock['unit_selling_price'], 2) }}
```

### Add Real Sales Data:
Replace placeholder `0` with actual data:
```php
'total_unit_sold' => $product->salesProducts()->sum('quantity'),
```

---

## 🐛 Troubleshooting

### Issue: "No stock data available"
**Solution:** 
- Check if products have location assignments in `location_product` table
- Verify filter selections aren't too restrictive

### Issue: Select2 not working
**Solution:**
- Ensure jQuery is loaded before Select2
- Check browser console for JavaScript errors

### Issue: Summary totals showing 0
**Solution:**
- Check if `original_price` and `retail_price` are set for products
- Verify `qty` in `location_product` table is not null

---

## 📈 Future Enhancements

1. **Add Date Range Filter**: Filter stock by specific date
2. **Stock Movement History**: Show additions/reductions over time
3. **Low Stock Alerts**: Highlight products below alert quantity
4. **Export with Charts**: Include visual charts in PDF exports
5. **Real-time Updates**: Auto-refresh stock data
6. **Barcode Integration**: Scan to filter specific product
7. **Advanced Analytics**: 
   - Stock turnover ratio
   - Days of inventory on hand
   - ABC analysis

---

## 🔐 Permissions

This report requires the permission:
```php
'view stock-report'
```

Controlled by middleware in `ReportController`:
```php
$this->middleware('permission:view stock-report', ['only' => ['stockHistory', 'stockReport']]);
```

---

## 📝 Route

```php
Route::get('/stock-report', [ReportController::class, 'stockHistory'])->name('stock.report');
```

**URL:** `/stock-report`
**Method:** GET
**Parameters:** location_id, category_id, sub_category_id, brand_id, unit_id (all optional)

---

## ✅ Testing Checklist

- [ ] Can view all locations stock
- [ ] Can filter by specific location
- [ ] Can filter by category
- [ ] Can filter by brand
- [ ] Summary cards show correct totals
- [ ] Table displays all columns
- [ ] DataTable sorting works
- [ ] DataTable search works
- [ ] Export buttons functional
- [ ] Responsive on mobile
- [ ] Print layout is clean
- [ ] Color badges display correctly
- [ ] No console errors

---

## 📞 Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Enable debug mode: Set `APP_DEBUG=true` in `.env`
3. Check browser console for JavaScript errors

---

**Implementation Date:** October 25, 2025
**Developer:** GitHub Copilot
**Version:** 1.0
