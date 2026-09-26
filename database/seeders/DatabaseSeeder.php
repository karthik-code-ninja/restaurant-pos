<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\Category;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\ExpenseCategory;
use App\Models\Food;
use App\Models\FoodIngredient;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Permission;
use App\Models\RestaurantTable;
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
            'dayclosing.view', 'dayclosing.manage', 'reports.view', 'audit.view'
        ])->pluck('id');
        $managerRole->permissions()->sync($managerPerms);

        $cashierPerms = Permission::whereIn('slug', [
            'dashboard.view', 'food.view', 'table.view', 'pos.billing', 'bill.reprint',
            'dayclosing.view', 'dayclosing.manage'
        ])->pluck('id');
        $cashierRole->permissions()->sync($cashierPerms);

        $staffPerms = Permission::whereIn('slug', [
            'table.view', 'pos.billing'
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

        // 5. CATEGORIES & FOODS
        $starters = Category::firstOrCreate(['name' => 'Starters'], ['description' => 'Appetizers & Starters', 'is_active' => true, 'sort_order' => 1]);
        $mainCourse = Category::firstOrCreate(['name' => 'Main Course'], ['description' => 'Curries & Gravies', 'is_active' => true, 'sort_order' => 2]);
        $biriyani = Category::firstOrCreate(['name' => 'Biriyani Special'], ['description' => 'Aromatic Dum Biriyani', 'is_active' => true, 'sort_order' => 3]);
        $breads = Category::firstOrCreate(['name' => 'Breads & Roti'], ['description' => 'Naan, Roti & Parotta', 'is_active' => true, 'sort_order' => 4]);
        $beverages = Category::firstOrCreate(['name' => 'Beverages & Desserts'], ['description' => 'Drinks, Juices & Desserts', 'is_active' => true, 'sort_order' => 5]);

        $foods = [
            ['code' => 'FD101', 'name' => 'Chicken 65', 'category_id' => $starters->id, 'price' => 180.00, 'tax_rate' => 5.00, 'is_veg' => false],
            ['code' => 'FD102', 'name' => 'Paneer Tikka', 'category_id' => $starters->id, 'price' => 160.00, 'tax_rate' => 5.00, 'is_veg' => true],
            ['code' => 'FD103', 'name' => 'Crispy Corn', 'category_id' => $starters->id, 'price' => 120.00, 'tax_rate' => 5.00, 'is_veg' => true],
            ['code' => 'FD201', 'name' => 'Butter Chicken', 'category_id' => $mainCourse->id, 'price' => 260.00, 'tax_rate' => 5.00, 'is_veg' => false],
            ['code' => 'FD202', 'name' => 'Paneer Butter Masala', 'category_id' => $mainCourse->id, 'price' => 210.00, 'tax_rate' => 5.00, 'is_veg' => true],
            ['code' => 'FD203', 'name' => 'Dal Makhani', 'category_id' => $mainCourse->id, 'price' => 170.00, 'tax_rate' => 5.00, 'is_veg' => true],
            ['code' => 'FD301', 'name' => 'Chicken Dum Biriyani', 'category_id' => $biriyani->id, 'price' => 240.00, 'tax_rate' => 5.00, 'is_veg' => false],
            ['code' => 'FD302', 'name' => 'Mutton Dum Biriyani', 'category_id' => $biriyani->id, 'price' => 320.00, 'tax_rate' => 5.00, 'is_veg' => false],
            ['code' => 'FD303', 'name' => 'Veg Dum Biriyani', 'category_id' => $biriyani->id, 'price' => 180.00, 'tax_rate' => 5.00, 'is_veg' => true],
            ['code' => 'FD401', 'name' => 'Butter Naan', 'category_id' => $breads->id, 'price' => 45.00, 'tax_rate' => 5.00, 'is_veg' => true],
            ['code' => 'FD402', 'name' => 'Garlic Naan', 'category_id' => $breads->id, 'price' => 55.00, 'tax_rate' => 5.00, 'is_veg' => true],
            ['code' => 'FD403', 'name' => 'Tandoori Roti', 'category_id' => $breads->id, 'price' => 30.00, 'tax_rate' => 5.00, 'is_veg' => true],
            ['code' => 'FD501', 'name' => 'Fresh Lime Soda', 'category_id' => $beverages->id, 'price' => 50.00, 'tax_rate' => 5.00, 'is_veg' => true],
            ['code' => 'FD502', 'name' => 'Gulab Jamun (2 Pcs)', 'category_id' => $beverages->id, 'price' => 60.00, 'tax_rate' => 5.00, 'is_veg' => true],
        ];

        foreach ($foods as $food) {
            Food::firstOrCreate(['code' => $food['code']], $food);
        }

        // 6. ADD-ONS
        $addons = [
            ['name' => 'Extra Cheese', 'price' => 35.00, 'tax_rate' => 5.00, 'is_active' => true],
            ['name' => 'Mayonnaise Dip', 'price' => 20.00, 'tax_rate' => 5.00, 'is_active' => true],
            ['name' => 'Raita Bowl', 'price' => 25.00, 'tax_rate' => 5.00, 'is_active' => true],
            ['name' => 'Boiled Egg', 'price' => 15.00, 'tax_rate' => 5.00, 'is_active' => true],
            ['name' => 'Extra Biriyani Gravy', 'price' => 30.00, 'tax_rate' => 5.00, 'is_active' => true],
        ];

        foreach ($addons as $addon) {
            AddOn::firstOrCreate(['name' => $addon['name']], $addon);
        }

        // 7. COMBOS
        $biriyaniFood = Food::where('code', 'FD301')->first();
        $limeSoda = Food::where('code', 'FD501')->first();

        $combo = Combo::firstOrCreate(
            ['code' => 'CMB101'],
            [
                'name' => 'Biriyani Feast Combo',
                'price' => 270.00,
                'tax_rate' => 5.00,
                'is_active' => true,
                'description' => '1 Chicken Biriyani + 1 Fresh Lime Soda (Save ₹20)',
            ]
        );

        if ($biriyaniFood && $limeSoda) {
            ComboItem::firstOrCreate(['combo_id' => $combo->id, 'food_id' => $biriyaniFood->id], ['quantity' => 1]);
            ComboItem::firstOrCreate(['combo_id' => $combo->id, 'food_id' => $limeSoda->id], ['quantity' => 1]);
        }

        // 8. RESTAURANT TABLES
        $tables = [
            ['table_number' => 'T1', 'name' => 'Window Table 1', 'floor' => 'Ground Floor', 'section' => 'AC Dining', 'capacity' => 4, 'status' => 'available'],
            ['table_number' => 'T2', 'name' => 'Window Table 2', 'floor' => 'Ground Floor', 'section' => 'AC Dining', 'capacity' => 4, 'status' => 'available'],
            ['table_number' => 'T3', 'name' => 'Family Booth 1', 'floor' => 'Ground Floor', 'section' => 'Family Lounge', 'capacity' => 6, 'status' => 'available'],
            ['table_number' => 'T4', 'name' => 'Family Booth 2', 'floor' => 'Ground Floor', 'section' => 'Family Lounge', 'capacity' => 6, 'status' => 'available'],
            ['table_number' => 'T5', 'name' => 'Couple Table 1', 'floor' => 'Ground Floor', 'section' => 'Main Hall', 'capacity' => 2, 'status' => 'available'],
            ['table_number' => 'T6', 'name' => 'Couple Table 2', 'floor' => 'Ground Floor', 'section' => 'Main Hall', 'capacity' => 2, 'status' => 'available'],
            ['table_number' => 'T7', 'name' => 'Terrace Table 1', 'floor' => 'First Floor', 'section' => 'Rooftop Lounge', 'capacity' => 4, 'status' => 'available'],
            ['table_number' => 'T8', 'name' => 'Terrace Table 2', 'floor' => 'First Floor', 'section' => 'Rooftop Lounge', 'capacity' => 4, 'status' => 'available'],
            ['table_number' => 'T9', 'name' => 'VIP Table 1', 'floor' => 'First Floor', 'section' => 'VIP Cabin', 'capacity' => 8, 'status' => 'available'],
            ['table_number' => 'T10', 'name' => 'Party Table 1', 'floor' => 'First Floor', 'section' => 'Party Hall', 'capacity' => 12, 'status' => 'available'],
        ];

        foreach ($tables as $t) {
            RestaurantTable::firstOrCreate(['table_number' => $t['table_number']], $t);
        }

        // 9. INVENTORY ITEMS & INGREDIENT MAPPING
        $rawRice = InventoryItem::firstOrCreate(
            ['code' => 'RAW01'],
            ['name' => 'Basmati Rice', 'unit' => 'kg', 'opening_stock' => 100.000, 'current_stock' => 85.500, 'min_stock_alert' => 15.000, 'is_active' => true]
        );

        $rawChicken = InventoryItem::firstOrCreate(
            ['code' => 'RAW02'],
            ['name' => 'Fresh Chicken', 'unit' => 'kg', 'opening_stock' => 50.000, 'current_stock' => 40.000, 'min_stock_alert' => 10.000, 'is_active' => true]
        );

        $rawOil = InventoryItem::firstOrCreate(
            ['code' => 'RAW03'],
            ['name' => 'Refined Cooking Oil', 'unit' => 'l', 'opening_stock' => 40.000, 'current_stock' => 32.000, 'min_stock_alert' => 5.000, 'is_active' => true]
        );

        $rawPaneer = InventoryItem::firstOrCreate(
            ['code' => 'RAW04'],
            ['name' => 'Fresh Paneer', 'unit' => 'kg', 'opening_stock' => 20.000, 'current_stock' => 14.500, 'min_stock_alert' => 4.000, 'is_active' => true]
        );

        $rawLemon = InventoryItem::firstOrCreate(
            ['code' => 'RAW05'],
            ['name' => 'Fresh Lemon', 'unit' => 'pcs', 'opening_stock' => 150.000, 'current_stock' => 95.000, 'min_stock_alert' => 20.000, 'is_active' => true]
        );

        // Map Chicken Biriyani ingredients
        if ($biriyaniFood) {
            FoodIngredient::firstOrCreate(
                ['food_id' => $biriyaniFood->id, 'inventory_item_id' => $rawRice->id],
                ['quantity' => 0.250] // 250g rice per biriyani
            );
            FoodIngredient::firstOrCreate(
                ['food_id' => $biriyaniFood->id, 'inventory_item_id' => $rawChicken->id],
                ['quantity' => 0.300] // 300g chicken per biriyani
            );
            FoodIngredient::firstOrCreate(
                ['food_id' => $biriyaniFood->id, 'inventory_item_id' => $rawOil->id],
                ['quantity' => 0.050] // 50ml oil per biriyani
            );
        }

        // Map Paneer Butter Masala
        $paneerFood = Food::where('code', 'FD202')->first();
        if ($paneerFood) {
            FoodIngredient::firstOrCreate(
                ['food_id' => $paneerFood->id, 'inventory_item_id' => $rawPaneer->id],
                ['quantity' => 0.200]
            );
        }

        // Map Fresh Lime Soda
        if ($limeSoda) {
            FoodIngredient::firstOrCreate(
                ['food_id' => $limeSoda->id, 'inventory_item_id' => $rawLemon->id],
                ['quantity' => 1.000] // 1 lemon per soda
            );
        }

        // 10. EXPENSE CATEGORIES
        $expCategories = [
            ['name' => 'Vegetables & Groceries', 'description' => 'Daily market supplies and fresh vegetables'],
            ['name' => 'Meat & Poultry', 'description' => 'Fresh chicken, mutton, and seafood'],
            ['name' => 'Dairy & Bakery', 'description' => 'Milk, butter, paneer, and bakery products'],
            ['name' => 'Electricity & Gas Utility', 'description' => 'LPG commercial cylinders and power bills'],
            ['name' => 'Staff Welfare & Salary', 'description' => 'Staff tea, snacks, and advances'],
            ['name' => 'Repairs & Maintenance', 'description' => 'Kitchen equipment, plumbing, electrical maintenance'],
        ];

        foreach ($expCategories as $cat) {
            ExpenseCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
