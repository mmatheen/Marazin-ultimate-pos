<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->refactorExpensesTable();
        $this->refactorExpenseItemsTable();
        $this->refactorExpensePaymentsTable();
    }

    private function refactorExpensesTable(): void
    {
        if (!Schema::hasTable('expenses')) {
            return;
        }

        if (!$this->indexExists('expenses', 'expenses_supplier_id_index')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->index(['supplier_id'], 'expenses_supplier_id_index');
            });
        }

        if ($this->foreignKeyExists('expenses', 'expenses_updated_by_foreign')) {
            DB::statement('ALTER TABLE expenses DROP FOREIGN KEY expenses_updated_by_foreign');
        }

        if (Schema::hasColumn('expenses', 'payment_status')) {
            try {
                DB::statement('ALTER TABLE expenses DROP INDEX expenses_payment_status_index');
            } catch (\Throwable $e) {
                // Already removed.
            }

            try {
                DB::statement('ALTER TABLE expenses DROP INDEX expenses_supplier_id_payment_status_index');
            } catch (\Throwable $e) {
                // Already removed.
            }
        }

        $dropColumns = [
            'payment_status',
            'payment_method',
            'paid_amount',
            'due_amount',
            'tax_amount',
            'discount_type',
            'discount_amount',
            'shipping_charges',
            'updated_by',
        ];

        foreach ($dropColumns as $column) {
            if (Schema::hasColumn('expenses', $column)) {
                Schema::table('expenses', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        if (Schema::hasColumn('expenses', 'supplier_id')) {
            DB::statement('ALTER TABLE expenses MODIFY supplier_id BIGINT UNSIGNED NULL');
        }
    }

    private function refactorExpenseItemsTable(): void
    {
        if (!Schema::hasTable('expense_items')) {
            return;
        }

        if (!Schema::hasColumn('expense_items', 'amount')) {
            Schema::table('expense_items', function (Blueprint $table) {
                $table->decimal('amount', 15, 2)->default(0)->after('description');
            });
        }

        if (Schema::hasColumn('expense_items', 'total')) {
            DB::statement('UPDATE expense_items SET amount = total WHERE amount = 0 OR amount IS NULL');
        }

        if (Schema::hasColumn('expense_items', 'item_name')) {
            DB::statement("UPDATE expense_items SET description = COALESCE(NULLIF(description, ''), item_name)");
        }

        if (Schema::hasColumn('expense_items', 'location_id')) {
            DB::statement('UPDATE expense_items ei INNER JOIN expenses e ON e.id = ei.expense_id SET ei.location_id = e.location_id WHERE ei.location_id IS NULL');
            DB::statement('ALTER TABLE expense_items MODIFY location_id BIGINT UNSIGNED NOT NULL');
        }

        foreach (['item_name', 'quantity', 'unit_price', 'total', 'tax_rate', 'tax_amount'] as $column) {
            if (Schema::hasColumn('expense_items', $column)) {
                Schema::table('expense_items', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        Schema::table('expense_items', function (Blueprint $table) {
            try {
                $table->index(['expense_id', 'location_id'], 'expense_items_expense_location_index');
            } catch (\Throwable $e) {
                // Index already exists.
            }
        });
    }

    private function refactorExpensePaymentsTable(): void
    {
        if (!Schema::hasTable('expense_payments')) {
            return;
        }

        Schema::table('expense_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('expense_payments', 'paid_from_account_id')) {
                $table->unsignedBigInteger('paid_from_account_id')->nullable()->after('expense_id');
                $table->index('paid_from_account_id', 'expense_payments_paid_from_account_idx');
            }

        });

        if ($this->foreignKeyExists('expense_payments', 'expense_payments_updated_by_foreign')) {
            DB::statement('ALTER TABLE expense_payments DROP FOREIGN KEY expense_payments_updated_by_foreign');
        }

        if (Schema::hasColumn('expense_payments', 'location_id')) {
            DB::statement('UPDATE expense_payments ep INNER JOIN expenses e ON e.id = ep.expense_id SET ep.location_id = e.location_id WHERE ep.location_id IS NULL');
            DB::statement('ALTER TABLE expense_payments MODIFY location_id BIGINT UNSIGNED NOT NULL');
        }

        if (Schema::hasColumn('expense_payments', 'updated_by')) {
            Schema::table('expense_payments', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }

        Schema::table('expense_payments', function (Blueprint $table) {
            try {
                $table->index(['expense_id', 'payment_date'], 'expense_payments_expense_date_idx');
            } catch (\Throwable $e) {
                // Index already exists.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left as no-op to avoid destructive rollback on live accounting data.
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [$database, $table, 'FOREIGN KEY', $constraintName]
        );

        return $result !== null;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::selectOne(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$database, $table, $indexName]
        );

        return $result !== null;
    }
};
