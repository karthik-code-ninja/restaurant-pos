<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->enum('order_type', ['table', 'counter'])->default('counter');
            $table->foreignId('table_id')->nullable()->constrained('restaurant_tables')->nullOnDelete();
            $table->foreignId('cashier_id')->constrained('users');
            $table->enum('status', ['draft', 'held', 'pending', 'completed', 'cancelled'])->default('pending');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            
            // Financial calculations
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('cgst_total', 12, 2)->default(0);
            $table->decimal('sgst_total', 12, 2)->default(0);
            $table->decimal('igst_total', 12, 2)->default(0);
            
            $table->decimal('rounding_difference', 8, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            
            // Print & Audit Counters
            $table->unsignedInteger('printed_count')->default(0);
            $table->unsignedInteger('reprinted_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            
            // Stock & Split/Merge references
            $table->boolean('is_stock_deducted')->default(false);
            $table->foreignId('parent_bill_id')->nullable()->constrained('bills')->nullOnDelete();
            
            $table->timestamp('completed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('invoice_number');
            $table->index('status');
            $table->index('order_type');
            $table->index('created_at');
            $table->index('completed_at');
            $table->index('cashier_id');
        });

        Schema::create('bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->enum('item_type', ['food', 'combo'])->default('food');
            $table->unsignedBigInteger('item_id')->nullable(); // food_id or combo_id
            $table->string('food_code');
            $table->string('item_name');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('quantity', 8, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('cgst_amount', 10, 2)->default(0);
            $table->decimal('sgst_amount', 10, 2)->default(0);
            $table->decimal('igst_amount', 10, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('bill_id');
            $table->index('food_code');
        });

        Schema::create('bill_item_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_item_id')->constrained('bill_items')->cascadeOnDelete();
            $table->foreignId('add_on_id')->nullable()->constrained('add_ons')->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('bills')->cascadeOnDelete();
            $table->enum('payment_method', ['cash', 'upi', 'card']);
            $table->decimal('amount', 12, 2);
            $table->string('reference_number')->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->timestamps();

            $table->index(['bill_id', 'payment_method']);
            $table->index('payment_method');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bill_item_addons');
        Schema::dropIfExists('bill_items');
        Schema::dropIfExists('bills');
    }
};
