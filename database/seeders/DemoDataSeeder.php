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
use App\Models\RestaurantTable;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. CATEGORIES & FOODS
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

        // 2. ADD-ONS
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

        // 3. COMBOS
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

        // 4. RESTAURANT TABLES
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

        // 5. INVENTORY ITEMS & INGREDIENT MAPPING
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
                ['quantity' => 0.250]
            );
            FoodIngredient::firstOrCreate(
                ['food_id' => $biriyaniFood->id, 'inventory_item_id' => $rawChicken->id],
                ['quantity' => 0.300]
            );
            FoodIngredient::firstOrCreate(
                ['food_id' => $biriyaniFood->id, 'inventory_item_id' => $rawOil->id],
                ['quantity' => 0.050]
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
                ['quantity' => 1.000]
            );
        }

        // 6. EXPENSE CATEGORIES
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

