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
        // Add HSN code to foods table
        Schema::table('foods', function (Blueprint $table) {
            if (!Schema::hasColumn('foods', 'hsn_code')) {
                $table->string('hsn_code', 20)->nullable()->after('code');
            }
        });

        // Add waiter and KOT tracking to bills table
        Schema::table('bills', function (Blueprint $table) {
            if (!Schema::hasColumn('bills', 'waiter_id')) {
                $table->foreignId('waiter_id')->nullable()->after('cashier_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('bills', 'waiter_name')) {
                $table->string('waiter_name', 100)->nullable()->after('waiter_id');
            }
            if (!Schema::hasColumn('bills', 'kot_count')) {
                $table->integer('kot_count')->default(0)->after('duplicate_count');
            }
        });

        // Add KOT printed qty and HSN to bill_items table
        Schema::table('bill_items', function (Blueprint $table) {
            if (!Schema::hasColumn('bill_items', 'hsn_code')) {
                $table->string('hsn_code', 20)->nullable()->after('food_code');
            }
            if (!Schema::hasColumn('bill_items', 'kot_printed_qty')) {
                $table->decimal('kot_printed_qty', 12, 3)->default(0)->after('quantity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('foods', function (Blueprint $table) {
            if (Schema::hasColumn('foods', 'hsn_code')) {
                $table->dropColumn('hsn_code');
            }
        });

        Schema::table('bills', function (Blueprint $table) {
            if (Schema::hasColumn('bills', 'waiter_id')) {
                $table->dropForeign(['waiter_id']);
                $table->dropColumn('waiter_id');
            }
            if (Schema::hasColumn('bills', 'waiter_name')) {
                $table->dropColumn('waiter_name');
            }
            if (Schema::hasColumn('bills', 'kot_count')) {
                $table->dropColumn('kot_count');
            }
        });

        Schema::table('bill_items', function (Blueprint $table) {
            if (Schema::hasColumn('bill_items', 'hsn_code')) {
                $table->dropColumn('hsn_code');
            }
            if (Schema::hasColumn('bill_items', 'kot_printed_qty')) {
                $table->dropColumn('kot_printed_qty');
            }
        });
    }
};
