<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('unit', 50)->default('kg'); // kg, g, l, ml, pcs
            $table->decimal('opening_stock', 12, 3)->default(0);
            $table->decimal('current_stock', 12, 3)->default(0);
            $table->decimal('min_stock_alert', 12, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('is_active');
            $table->index('code');
        });

        Schema::create('food_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity', 12, 3); // consumption quantity per food unit
            $table->timestamps();

            $table->unique(['food_id', 'inventory_item_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->enum('type', ['opening', 'sale_deduction', 'adjustment_add', 'adjustment_reduce', 'cancellation_reversal']);
            $table->decimal('quantity', 12, 3);
            $table->decimal('previous_stock', 12, 3);
            $table->decimal('current_stock', 12, 3);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable(); // e.g. App\Models\Bill
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['inventory_item_id', 'created_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('food_ingredients');
        Schema::dropIfExists('inventory_items');
    }
};
