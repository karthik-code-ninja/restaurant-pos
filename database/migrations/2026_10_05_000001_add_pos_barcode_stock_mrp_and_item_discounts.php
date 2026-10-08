<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add barcode, mrp, and stock to foods table
        Schema::table('foods', function (Blueprint $table) {
            if (!Schema::hasColumn('foods', 'barcode')) {
                $table->string('barcode', 100)->nullable()->after('code')->index();
            }
            if (!Schema::hasColumn('foods', 'cost_price')) {
                $table->decimal('cost_price', 10, 2)->default(0.00)->after('price');
            }
            if (!Schema::hasColumn('foods', 'mrp')) {
                $table->decimal('mrp', 10, 2)->default(0.00)->after('cost_price');
            }
            if (!Schema::hasColumn('foods', 'stock')) {
                $table->decimal('stock', 12, 3)->default(0.000)->after('mrp');
            }
        });

        // Add item-level discount to bill_items table
        Schema::table('bill_items', function (Blueprint $table) {
            if (!Schema::hasColumn('bill_items', 'discount_type')) {
                $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed')->after('quantity');
            }
            if (!Schema::hasColumn('bill_items', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->default(0.00)->after('discount_type');
            }
            if (!Schema::hasColumn('bill_items', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0.00)->after('discount_value');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            if (Schema::hasColumn('foods', 'stock')) {
                $table->dropColumn('stock');
            }
            if (Schema::hasColumn('foods', 'mrp')) {
                $table->dropColumn('mrp');
            }
            if (Schema::hasColumn('foods', 'barcode')) {
                $table->dropColumn('barcode');
            }
        });

        Schema::table('bill_items', function (Blueprint $table) {
            if (Schema::hasColumn('bill_items', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('bill_items', 'discount_value')) {
                $table->dropColumn('discount_value');
            }
            if (Schema::hasColumn('bill_items', 'discount_type')) {
                $table->dropColumn('discount_type');
            }
        });
    }
};

