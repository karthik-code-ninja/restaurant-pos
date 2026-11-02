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
        // 1. Accounting Vouchers Table
        Schema::create('accounting_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique(); // e.g. RCP-2026-0001, PMT-2026-0001
            $table->enum('voucher_type', ['receipt', 'payment', 'sales', 'purchase', 'purchase_return', 'journal', 'contra']);
            $table->date('voucher_date');
            $table->string('party_type')->nullable(); // customer, supplier, expense, general
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_name')->nullable();
            $table->string('debit_ledger')->nullable();
            $table->string('credit_ledger')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('payment_method')->default('cash'); // cash, bank, upi, cheque
            $table->string('reference_no')->nullable();
            $table->text('narration')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 2. Todos Table
        Schema::create('todos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->date('due_date')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 3. Reminders Table
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->date('reminder_date');
            $table->string('reminder_time', 20)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_dismissed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('todos');
        Schema::dropIfExists('accounting_vouchers');
    }
};
