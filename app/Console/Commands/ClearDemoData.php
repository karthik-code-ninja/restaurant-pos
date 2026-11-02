<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

#[Signature('app:clear-demo-data {--force : Force the operation to run without confirmation}')]
#[Description('Remove all demo contents, transactions, and sample data while preserving users, roles, permissions, and settings')]
class ClearDemoData extends Command
{
    /**
     * Tables containing demo / operational data to be truncated.
     * Users, roles, permissions, role_permission, and settings are preserved.
     */
    protected array $demoTables = [
        // 1. Billing, Payments & Orders
        'bill_item_addons',
        'bill_items',
        'payments',
        'bills',

        // 2. Customers & Ledgers
        'customer_ledgers',
        'customers',

        // 3. Menu, Foods, Combos, Addons & Categories
        'combo_items',
        'combos',
        'food_ingredients',
        'foods',
        'add_ons',
        'categories',

        // 4. Restaurant Tables
        'restaurant_tables',

        // 5. Inventory & Stocks
        'inventory_movements',
        'inventory_items',

        // 6. Expenses & Expense Categories
        'expenses',
        'expense_categories',

        // 7. Day Closing
        'day_closings',

        // 8. Purchases, Purchase Orders, Returns & Suppliers
        'purchase_return_items',
        'purchase_returns',
        'purchase_order_items',
        'purchase_orders',
        'purchase_items',
        'purchases',
        'suppliers',

        // 9. Accounting Vouchers, Todos & Reminders
        'accounting_vouchers',
        'todos',
        'reminders',

        // 10. Audit & Login Activity Logs
        'audit_logs',
        'login_logs',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('This will permanently delete all demo contents and transactions, preserving only users and configuration settings. Do you wish to continue?', false)) {
            $this->warn('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info('Starting removal of demo data...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $clearedCount = 0;
        foreach ($this->demoTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $this->line("  ✓ Cleared <comment>{$table}</comment> ({$count} demo rows removed)");
                $clearedCount++;
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Clear application cache
        $this->callSilently('cache:clear');

        $userCount = DB::table('users')->count();
        $this->newLine();
        $this->info("Successfully cleared {$clearedCount} tables.");
        $this->info("Preserved {$userCount} users, roles, permissions, and system settings.");
        $this->info('System is now ready for live data entry.');

        return self::SUCCESS;
    }
}
