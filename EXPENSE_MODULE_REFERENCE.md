# Expense Module - Complete Structure Reference

## 📁 File Structure

### Models (`app/Models/`)
```
Expense.php                 - Main expense model with payment tracking
ExpenseParentCategory.php   - Parent expense categories
ExpenseSubCategory.php     - Sub-categories under parents
ExpenseItem.php            - Individual expense line items
ExpensePayment.php         - Payment tracking for expenses
```

### Controllers (`app/Http/Controllers/`)
```
ExpenseController.php      - Complete CRUD with advanced filtering
```

### Views (`resources/views/expense/`)
```
expense_list.blade.php     - DataTable listing with filters
create_expense.blade.php   - Multi-step expense creation form
```

### Database (`database/migrations/`)
```
create_expense_parent_categories_table.php
create_expense_sub_categories_table.php
create_expenses_table.php
create_expense_items_table.php
create_expense_payments_table.php
```

### Commands (`app/Console/Commands/`)
```
MigrateAllPermissions.php  - Production-safe permission migration
```

## 🔗 Navigation Integration

### Sidebar Menu (Updated)
Added expense management section in main navigation with proper permission gates:
```php
@can('view expense')
    <li class="nav-item">
        <a href="#" class="nav-link">
            <i class="nav-icon fas fa-money-bill-wave text-danger"></i>
            <p>Expense Management <i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            @can('create expense')
                <li class="nav-item">
                    <a href="{{ route('expenses.create') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Add Expense</p>
                    </a>
                </li>
            @endcan
            <li class="nav-item">
                <a href="{{ route('expenses.index') }}" class="nav-link">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Expense List</p>
                </a>
            </li>
            @can('create expense-category')
                <li class="nav-item">
                    <a href="{{ route('expense-categories.index') }}" class="nav-link">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Manage Categories</p>
                    </a>
                </li>
            @endcan
        </ul>
    </li>
@endcan
```

## 🔒 Permission Structure

### Expense Permissions (Added to RolesAndPermissionsSeeder.php)
```php
'21. expense-management' => [
    'create expense',
    'view expense', 
    'edit expense',
    'delete expense',
    'create expense-category',
    'view expense-category',
    'edit expense-category', 
    'delete expense-category',
    'view expense-reports',
    'export expense-data'
],
```

### Role Assignments
- **Super Admin**: All expense permissions
- **Manager**: All expense permissions  
- **Accountant**: All expense permissions
- **Cashier**: View expense, create expense (limited)
- **Sales Rep**: View expense (read-only)
- **Staff**: View expense (basic access)

## 🌟 Key Features

### Expense Management
✅ **Hierarchical Categories**: Parent → Sub-category structure  
✅ **Multi-Item Expenses**: Multiple line items per expense  
✅ **Payment Tracking**: Partial/full payment support  
✅ **Supplier Integration**: Link expenses to suppliers  
✅ **File Attachments**: Receipt/document uploads  
✅ **Advanced Filtering**: Date, category, supplier, payment status  
✅ **Audit Trail**: Created/updated tracking  

### User Interface  
✅ **Responsive Design**: Bootstrap-based responsive UI  
✅ **DataTables**: Advanced sorting, searching, pagination  
✅ **AJAX Operations**: Dynamic category loading  
✅ **Multi-step Forms**: Guided expense creation  
✅ **Real-time Updates**: Live payment status tracking  

### Security & Permissions
✅ **Role-based Access**: Granular permission control  
✅ **Middleware Protection**: Route-level security  
✅ **Input Validation**: Comprehensive form validation  
✅ **CSRF Protection**: Laravel security features  

## 🚀 Quick Start

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Migrate Permissions (Production-Safe)
```bash
# Test first
php artisan permissions:migrate-all --dry-run

# Run migration
php artisan permissions:migrate-all
```

### 3. Seed Categories (Optional)
```bash
php artisan db:seed --class=ExpenseCategorySeeder
```

### 4. Clear Caches
```bash
php artisan permission:cache-reset
php artisan cache:clear
```

## 🔧 Routes

### Expense Routes (Add to routes/web.php)
```php
Route::middleware(['auth'])->group(function () {
    Route::resource('expenses', ExpenseController::class);
    Route::get('expenses/{expense}/payments', [ExpenseController::class, 'payments'])->name('expenses.payments');
    Route::post('expenses/{expense}/add-payment', [ExpenseController::class, 'addPayment'])->name('expenses.add-payment');
});
```

## 📊 Database Relationships

```
ExpenseParentCategory (1) → (∞) ExpenseSubCategory
ExpenseSubCategory (1) → (∞) Expense
Supplier (1) → (∞) Expense
Expense (1) → (∞) ExpenseItem
Expense (1) → (∞) ExpensePayment
User (1) → (∞) Expense (created_by)
```

## 🎯 Usage Examples

### Creating an Expense
1. Navigate to "Add Expense" from sidebar
2. Select supplier and expense category
3. Add expense items with descriptions and amounts
4. Upload receipts (optional)
5. Record payment information
6. Submit expense

### Managing Categories
1. Go to "Manage Categories" 
2. Create parent categories (e.g., "Office Supplies")
3. Add sub-categories (e.g., "Stationery", "Equipment")
4. Assign expenses to appropriate categories

### Payment Tracking
- View payment status on expense list
- Add partial payments to expenses
- Track outstanding balances
- Generate payment reports

## 🛠️ Customization

### Adding New Fields
1. Create migration for new columns
2. Update model fillable/casts
3. Modify form views
4. Update controller validation

### Custom Reports
1. Add new methods to ExpenseController
2. Create report views
3. Add navigation links
4. Set appropriate permissions

---

**✅ Status**: Complete expense module with production-ready permission migration system. All components tested and integrated.