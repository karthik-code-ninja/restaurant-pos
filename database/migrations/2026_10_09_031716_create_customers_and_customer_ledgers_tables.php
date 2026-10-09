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
        // 1. Customers Table
        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone', 20)->nullable()->index();
                $table->string('email')->nullable();
                $table->text('address')->nullable();
                $table->string('gstin', 20)->nullable();
                $table->decimal('credit_limit', 12, 2)->default(0.00);
                $table->decimal('balance', 12, 2)->default(0.00); // Positive: Customer Due. Negative: Advance Deposit.
                $table->boolean('status')->default(true);
                $table->text('notes')->nullable();
                $table->softDeletes();
                $table->timestamps();

                $table->index('name');
                $table->index('balance');
                $table->index('status');
            });
        }

        // 2. Add customer_id to bills table if not present
        if (Schema::hasTable('bills') && ! Schema::hasColumn('bills', 'customer_id')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->foreignId('customer_id')
                    ->nullable()
                    ->after('customer_phone')
                    ->constrained('customers')
                    ->nullOnDelete();
            });
        }

        // 3. Customer Ledgers Table
        if (! Schema::hasTable('customer_ledgers')) {
            Schema::create('customer_ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->dateTime('entry_date')->useCurrent();
                $table->enum('type', ['debit', 'credit']); // debit: increases due, credit: payment received/reduces due
                $table->decimal('amount', 12, 2);
                $table->decimal('balance_after', 12, 2);
                $table->string('payment_mode', 50)->nullable(); // credit_sale, cash, upi, card, bank_transfer, cheque, adjustment
                $table->string('reference_number', 100)->nullable();
                $table->string('description', 255)->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['customer_id', 'entry_date']);
                $table->index('type');
                $table->index('bill_id');
            });
        }

        // 4. Update payments table payment_method to VARCHAR(50) so 'credit' and future methods work seamlessly
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE payments MODIFY COLUMN payment_method VARCHAR(50) NOT NULL');
        } elseif (Schema::hasTable('payments')) {
            try {
                Schema::table('payments', function (Blueprint $table) {
                    $table->string('payment_method', 50)->change();
                });
            } catch (Throwable $e) {
                // SQLite in-memory ignores enum constraints anyway
            }
        }

        // 5. Ensure customer permissions exist
        if (Schema::hasTable('permissions')) {
            $permissions = [
                ['slug' => 'customer.view', 'name' => 'View Customers & Ledger', 'module' => 'Customers'],
                ['slug' => 'customer.manage', 'name' => 'Manage Customers & Ledger', 'module' => 'Customers'],
            ];

            foreach ($permissions as $perm) {
                $existing = DB::table('permissions')->where('slug', $perm['slug'])->first();
                if (! $existing) {
                    $permId = DB::table('permissions')->insertGetId([
                        'slug' => $perm['slug'],
                        'name' => $perm['name'],
                        'module' => $perm['module'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $permId = $existing->id;
                }

                // Assign to admin, manager, and cashier roles
                if (Schema::hasTable('roles') && Schema::hasTable('role_permission')) {
                    $roleIds = DB::table('roles')->whereIn('slug', ['admin', 'manager', 'cashier'])->pluck('id');
                    foreach ($roleIds as $roleId) {
                        $existsInRole = DB::table('role_permission')
                            ->where('role_id', $roleId)
                            ->where('permission_id', $permId)
                            ->exists();

                        if (! $existsInRole) {
                            DB::table('role_permission')->insert([
                                'role_id' => $roleId,
                                'permission_id' => $permId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ledgers');

        if (Schema::hasTable('bills') && Schema::hasColumn('bills', 'customer_id')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
                $table->dropColumn('customer_id');
            });
        }

        Schema::dropIfExists('customers');
    }
};
