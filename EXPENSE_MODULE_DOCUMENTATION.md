# Expense Module - Complete Structure Documentation

## Overview
This document provides a comprehensive overview of the complete expense module structure for your Laravel POS system. The module has been designed to handle all expense operations with proper category-based organization, filtering, and reporting capabilities.

## Module Components

### 1. Models

#### `ExpenseParentCategory` Model
- **File**: `app/Models/ExpenseParentCategory.php`
- **Purpose**: Manages main expense categories
- **Key Features**:
  - Basic category information (name, description)
  - Location-based filtering support
  - Relationships with sub-categories and expenses

#### `ExpenseSubCategory` Model
- **File**: `app/Models/ExpenseSubCategory.php`
- **Purpose**: Manages expense sub-categories
- **Key Features**:
  - Belongs to parent category
  - Category code and description
  - Relationships with expenses

#### `Expense` Model
- **File**: `app/Models/Expense.php`
- **Purpose**: Main expense management
- **Key Features**:
  - Complete expense information
  - Payment tracking
  - Category relationships
  - Supplier relationships
  - File attachments
  - Automatic calculation methods
  - Various scopes for filtering

#### `ExpenseItem` Model
- **File**: `app/Models/ExpenseItem.php`
- **Purpose**: Individual expense items/line items
- **Key Features**:
  - Item details (name, description, quantity, price)
  - Tax calculations
  - Automatic total calculations

#### `ExpensePayment` Model
- **File**: `app/Models/ExpensePayment.php`
- **Purpose**: Expense payment tracking
- **Key Features**:
  - Payment records
  - Multiple payment methods
  - Reference tracking

### 2. Controllers

#### `ExpenseParentCategoryController` (Enhanced)
- **File**: `app/Http/Controllers/ExpenseParentCategoryController.php`
- **Enhancements Made**:
  - Added search functionality
  - Improved response structure
  - Better error handling

#### `ExpenseSubCategoryController` (Existing)
- **File**: `app/Http/Controllers/ExpenseSubCategoryController.php`
- **Status**: Already functional, relationships enhanced

#### `ExpenseController` (New)
- **File**: `app/Http/Controllers/ExpenseController.php`
- **Features**:
  - Complete CRUD operations
  - Advanced filtering (category, sub-category, payment status, date range)
  - File upload handling
  - Payment status tracking
  - Expense reporting
  - Sub-category loading based on parent category

### 3. Database Structure

#### Expenses Table
```sql
CREATE TABLE expenses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    expense_no VARCHAR(255) UNIQUE,
    date DATE,
    reference_no VARCHAR(255) NULL,
    expense_parent_category_id BIGINT,
    expense_sub_category_id BIGINT NULL,
    supplier_id BIGINT NULL,
    payment_status ENUM('pending', 'partial', 'paid'),
    payment_method VARCHAR(255),
    total_amount DECIMAL(15,2),
    paid_amount DECIMAL(15,2),
    due_amount DECIMAL(15,2),
    tax_amount DECIMAL(15,2),
    discount_type ENUM('fixed', 'percentage'),
    discount_amount DECIMAL(15,2),
    shipping_charges DECIMAL(15,2),
    note TEXT NULL,
    attachment VARCHAR(255) NULL,
    created_by BIGINT NULL,
    updated_by BIGINT NULL,
    location_id BIGINT NULL,
    status ENUM('active', 'inactive'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Expense Items Table
```sql
CREATE TABLE expense_items (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    expense_id BIGINT,
    item_name VARCHAR(255),
    description TEXT NULL,
    quantity DECIMAL(15,4),
    unit_price DECIMAL(15,2),
    total DECIMAL(15,2),
    tax_rate DECIMAL(5,2),
    tax_amount DECIMAL(15,2),
    location_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### Expense Payments Table
```sql
CREATE TABLE expense_payments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    expense_id BIGINT,
    payment_date DATE,
    payment_method VARCHAR(255),
    amount DECIMAL(15,2),
    reference_no VARCHAR(255) NULL,
    note TEXT NULL,
    created_by BIGINT NULL,
    location_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### 4. Views Structure

#### Main Expense List View
- **File**: `resources/views/expense/expense_list.blade.php`
- **Features**:
  - Summary dashboard cards
  - Advanced filtering options
  - DataTable with pagination
  - Export functionality
  - Action buttons (view, edit, delete)

#### Create Expense View
- **File**: `resources/views/expense/create_expense.blade.php`
- **Features**:
  - Multi-step form layout
  - Dynamic item addition
  - Category-based sub-category loading
  - File upload support
  - Real-time total calculations
  - Payment information

#### AJAX Scripts
- **List AJAX**: `resources/views/expense/expense_list_ajax.blade.php`
- **Create AJAX**: `resources/views/expense/create_expense_ajax.blade.php`

### 5. Routes Configuration

#### Web Routes
```php
// Main Expense Routes
Route::get('/expense-list', [ExpenseController::class, 'expenseList'])->name('expense.list');
Route::get('/expense-create', [ExpenseController::class, 'create'])->name('expense.create');
Route::get('/expense-edit/{id}', [ExpenseController::class, 'edit'])->name('expense.edit');
Route::get('/expense-show/{id}', [ExpenseController::class, 'show']);
Route::get('/expense-get-all', [ExpenseController::class, 'index']);
Route::post('/expense-store', [ExpenseController::class, 'store'])->name('expense.store');
Route::post('/expense-update/{id}', [ExpenseController::class, 'update']);
Route::delete('/expense-delete/{id}', [ExpenseController::class, 'destroy']);
Route::get('/expense-sub-categories/{parentId}', [ExpenseController::class, 'getSubCategories']);
Route::get('/expense-reports', [ExpenseController::class, 'reports']);
```

#### API Routes
- Same as web routes for AJAX functionality

### 6. Key Features

#### Category Management
- **Hierarchical Structure**: Parent categories → Sub categories → Expenses
- **Dynamic Loading**: Sub-categories load based on selected parent category
- **Filtering**: Filter expenses by category and sub-category

#### Expense Management
- **Auto-generated Expense Numbers**: Format: EXP-YYYY-0001
- **Multi-item Support**: Add multiple items per expense
- **Payment Tracking**: Track partial and full payments
- **File Attachments**: Support for PDF, JPG, PNG files
- **Supplier Integration**: Link expenses to suppliers

#### Advanced Filtering
- **Date Range**: Filter by start and end dates
- **Category**: Filter by parent and sub categories
- **Payment Status**: Filter by pending, partial, or paid
- **Supplier**: Filter by specific suppliers
- **Search**: Text search across expense numbers, references, and notes

#### Reporting & Analytics
- **Summary Cards**: Total expenses, amounts, paid, due
- **Category-wise Reports**: Breakdown by categories
- **Export Functionality**: Excel/CSV export with filters
- **Payment Status Dashboard**: Visual payment status indicators

#### Security & Permissions
- **Role-based Access**: Uses Laravel permissions
- **Permission Gates**:
  - `view expense`
  - `create expense`
  - `edit expense`
  - `delete expense`
  - `view parent-expense`
  - `create parent-expense`
  - `edit parent-expense`
  - `delete parent-expense`
  - `view child-expense`
  - `create child-expense`
  - `edit child-expense`
  - `delete child-expense`

### 7. Usage Instructions

#### Setup Process
1. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

2. **Create Categories**:
   - Navigate to Expense Parent Categories
   - Add main categories (e.g., Office Supplies, Travel, Utilities)
   - Add sub-categories under each parent category

3. **Create Expenses**:
   - Go to expense creation page
   - Select category and sub-category
   - Add expense items
   - Set payment information
   - Save expense

#### Navigation Menu Integration
Add these menu items to your navigation:
```php
// In your menu/sidebar blade file
<li class="submenu">
    <a href="#"><i class="feather-file-text"></i> <span>Expenses</span> <span class="menu-arrow"></span></a>
    <ul>
        @can('view expense')
        <li><a href="{{ route('expense.list') }}">All Expenses</a></li>
        @endcan
        @can('create expense')
        <li><a href="{{ route('expense.create') }}">Create Expense</a></li>
        @endcan
        @can('view parent-expense')
        <li><a href="{{ route('expense-parent-catergory') }}">Categories</a></li>
        @endcan
        @can('view child-expense')
        <li><a href="{{ route('sub-expense-category') }}">Sub Categories</a></li>
        @endcan
    </ul>
</li>
```

### 8. Migration Files Created
- `2024_09_28_001_create_expenses_table.php`
- `2024_09_28_002_create_expense_items_table.php`
- `2024_09_28_003_create_expense_payments_table.php`

### 9. API Endpoints Summary

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/expense-list` | Show expense listing page |
| GET | `/expense-create` | Show expense creation form |
| GET | `/expense-get-all` | Get all expenses with filters |
| POST | `/expense-store` | Create new expense |
| GET | `/expense-show/{id}` | Get specific expense details |
| GET | `/expense-edit/{id}` | Show expense edit form |
| POST | `/expense-update/{id}` | Update expense |
| DELETE | `/expense-delete/{id}` | Delete expense |
| GET | `/expense-sub-categories/{parentId}` | Get sub-categories by parent |
| GET | `/expense-reports` | Get expense reports |

### 10. Next Steps

1. **Test the Module**: Create sample categories and expenses
2. **Customize as Needed**: Adjust fields, validations, or UI based on requirements
3. **Add Permissions**: Set up user roles and permissions
4. **Integrate with Dashboard**: Add expense widgets to main dashboard
5. **Add Notifications**: Implement expense approval workflows if needed

This expense module provides a complete, scalable solution for managing expenses with proper categorization, filtering, and reporting capabilities.