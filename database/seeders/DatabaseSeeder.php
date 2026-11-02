<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ROLES
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Full administrative access']
        );

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Manager', 'description' => 'Restaurant operational manager']
        );

        $cashierRole = Role::firstOrCreate(
            ['slug' => 'cashier'],
            ['name' => 'Cashier', 'description' => 'POS billing and checkout operator']
        );

        $staffRole = Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'description' => 'Floor staff and waiters']
        );

        // 2. PERMISSIONS
        $permissions = [
            ['slug' => 'dashboard.view', 'name' => 'Dashboard View', 'module' => 'Dashboard'],
            ['slug' => 'food.view', 'name' => 'Food View', 'module' => 'Food'],
            ['slug' => 'food.create', 'name' => 'Food Create', 'module' => 'Food'],
            ['slug' => 'food.edit', 'name' => 'Food Edit', 'module' => 'Food'],
            ['slug' => 'food.delete', 'name' => 'Food Delete', 'module' => 'Food'],
            ['slug' => 'table.view', 'name' => 'Table View', 'module' => 'Table'],
            ['slug' => 'table.manage', 'name' => 'Table Management', 'module' => 'Table'],
            ['slug' => 'pos.billing', 'name' => 'POS Billing', 'module' => 'POS'],
            ['slug' => 'bill.edit', 'name' => 'Bill Edit', 'module' => 'POS'],
            ['slug' => 'bill.cancel', 'name' => 'Bill Cancel', 'module' => 'POS'],
            ['slug' => 'bill.reprint', 'name' => 'Bill Reprint', 'module' => 'POS'],
            ['slug' => 'inventory.view', 'name' => 'Inventory View', 'module' => 'Inventory'],
            ['slug' => 'inventory.manage', 'name' => 'Inventory Management', 'module' => 'Inventory'],
            ['slug' => 'expense.view', 'name' => 'Expense View', 'module' => 'Expense'],
            ['slug' => 'expense.manage', 'name' => 'Expense Management', 'module' => 'Expense'],
            ['slug' => 'dayclosing.view', 'name' => 'Day Closing View', 'module' => 'Day Closing'],
            ['slug' => 'dayclosing.manage', 'name' => 'Day Closing Manage', 'module' => 'Day Closing'],
            ['slug' => 'reports.view', 'name' => 'Reports View', 'module' => 'Reports'],
            ['slug' => 'users.manage', 'name' => 'Users Management', 'module' => 'Users'],
            ['slug' => 'settings.manage', 'name' => 'Settings Management', 'module' => 'Settings'],
            ['slug' => 'backup.manage', 'name' => 'Backup Management', 'module' => 'Backup'],
            ['slug' => 'audit.view', 'name' => 'Audit Log View', 'module' => 'Audit'],
        ];

        $allPermissionIds = [];
        foreach ($permissions as $perm) {
            $p = Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
            $allPermissionIds[] = $p->id;
        }

        // Attach permissions
        $adminRole->permissions()->sync($allPermissionIds);

        $managerPerms = Permission::whereIn('slug', [
            'dashboard.view', 'food.view', 'food.create', 'food.edit',
            'table.view', 'table.manage', 'pos.billing', 'bill.edit', 'bill.cancel', 'bill.reprint',
            'inventory.view', 'inventory.manage', 'expense.view', 'expense.manage',
            'dayclosing.view', 'dayclosing.manage', 'reports.view', 'audit.view',
        ])->pluck('id');
        $managerRole->permissions()->sync($managerPerms);

        $cashierPerms = Permission::whereIn('slug', [
            'dashboard.view', 'food.view', 'table.view', 'pos.billing', 'bill.reprint',
            'dayclosing.view', 'dayclosing.manage',
        ])->pluck('id');
        $cashierRole->permissions()->sync($cashierPerms);

        $staffPerms = Permission::whereIn('slug', [
            'dashboard.view', 'table.view', 'pos.billing',
        ])->pluck('id');
        $staffRole->permissions()->sync($staffPerms);

        // 3. DEFAULT USERS
        User::firstOrCreate(
            ['email' => 'admin@restaurant.com'],
            [
                'name' => 'Restaurant Admin',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'phone' => '9876543210',
                'status' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager@restaurant.com'],
            [
                'name' => 'Restaurant Manager',
                'password' => Hash::make('password123'),
                'role_id' => $managerRole->id,
                'phone' => '9876543211',
                'status' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'cashier@restaurant.com'],
            [
                'name' => 'Head Cashier',
                'password' => Hash::make('password123'),
                'role_id' => $cashierRole->id,
                'phone' => '9876543212',
                'status' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@restaurant.com'],
            [
                'name' => 'Floor Staff',
                'password' => Hash::make('password123'),
                'role_id' => $staffRole->id,
                'phone' => '9876543213',
                'status' => true,
            ]
        );

        // 4. SETTINGS
        $defaultSettings = [
            // Restaurant Details
            'restaurant_name' => ['Restro Restaurant', 'restaurant', 'string'],
            'restaurant_address' => ['123 Food Street, R.S. Puram, Coimbatore, Tamil Nadu - 641002', 'restaurant', 'string'],
            'restaurant_contact' => ['+91 98765 43210', 'restaurant', 'string'],
            'restaurant_email' => ['contact@Restro.in', 'restaurant', 'string'],
            'restaurant_gstin' => ['33AAAAA0000A1Z5', 'restaurant', 'string'],
            'restaurant_logo' => ['', 'restaurant', 'string'],

            // Invoice
            'invoice_prefix' => ['INV-', 'invoice', 'string'],
            'invoice_start_number' => ['1001', 'invoice', 'integer'],
            'invoice_format' => ['prefix_date_number', 'invoice', 'string'],

            // POS Printer
            'printer_type' => ['80mm', 'printer', 'string'], // 58mm or 80mm
            'auto_print' => ['1', 'printer', 'boolean'],

            // Receipt
            'receipt_header' => ['Welcome to Restro Restaurant!', 'receipt', 'string'],
            'receipt_footer' => ['Thank you for dining with us! Please visit again.', 'receipt', 'string'],
            'show_logo_on_receipt' => ['0', 'receipt', 'boolean'],
            'print_restaurant_copy' => ['1', 'receipt', 'boolean'],
            'print_customer_copy' => ['1', 'receipt', 'boolean'],

            // Tax
            'tax_type' => ['exclusive', 'tax', 'string'], // inclusive or exclusive
            'default_gst_rate' => ['5.00', 'tax', 'decimal'],
            'cgst_rate' => ['2.50', 'tax', 'decimal'],
            'sgst_rate' => ['2.50', 'tax', 'decimal'],
            'igst_rate' => ['5.00', 'tax', 'decimal'],

            // Payment Methods
            'enable_cash' => ['1', 'payment', 'boolean'],
            'enable_upi' => ['1', 'payment', 'boolean'],
            'enable_card' => ['1', 'payment', 'boolean'],

            // General
            'currency_symbol' => ['₹', 'general', 'string'],
            'currency_code' => ['INR', 'general', 'string'],
            'date_format' => ['d/m/Y', 'general', 'string'],
            'time_format' => ['h:i A', 'general', 'string'],
        ];

        foreach ($defaultSettings as $key => [$val, $grp, $typ]) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $val, 'group' => $grp, 'type' => $typ]
            );
        }
    }
}
