<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Food;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\Reminder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class QuickAccessAccountsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::where('slug', 'admin')->first()
            ?? Role::create(['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Admin']);

        $this->adminUser = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin_qa_'.uniqid().'@restaurant.test',
            'status' => true,
            'role_id' => $adminRole->id,
        ]);
    }

    public function test_quick_access_dashboard_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('quick-access'));

        $response->assertOk();
        $response->assertSee('QUICK ACCESS');
        $response->assertSee('TO DO LIST');
        $response->assertSee('REMINDER');
        $response->assertSee('DASH BOARD');
        $response->assertSee('SALES');
        $response->assertSee('PURCHASE');
        $response->assertSee('EXPENSE');
        $response->assertSee('CASH BALANCE');
        $response->assertSee('BANK BALANCE');
        $response->assertSee('STOCK VALUE');
        $response->assertSee('CUSTOMER BALANCE');
    }

    public function test_quick_access_data_ajax_endpoint_returns_json_metrics(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('quick-access.data'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'metrics' => [
                'sales' => ['today', 'month', 'fy', 'fy_lacs'],
                'purchase' => ['today', 'month', 'fy', 'fy_lacs'],
                'expense' => ['today', 'month', 'fy', 'fy_lacs'],
                'cash_balance' => ['today', 'month', 'fy', 'fy_lacs'],
                'bank_balance' => ['today', 'month', 'fy', 'fy_lacs'],
                'stock_value' => ['today', 'month', 'fy', 'fy_lacs'],
                'customer_balance' => ['today', 'month', 'fy', 'fy_lacs'],
            ],
            'fyInfo',
            'timestamp',
        ]);
    }

    public function test_quick_access_todays_profit_endpoint(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('quick-access.profit'));

        $response->assertOk();
        $response->assertJsonStructure([
            'date',
            'sales',
            'cogs',
            'purchases',
            'expenses',
            'gross_profit',
            'net_profit',
            'margin_percent',
        ]);
    }

    public function test_todo_creation_and_completion_toggle(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('todos.store'), [
                'title' => 'Test Kitchen Stock Verification',
                'priority' => 'high',
                'due_date' => now()->toDateString(),
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $todo = Todo::where('title', 'Test Kitchen Stock Verification')->first();
        $this->assertNotNull($todo);
        $this->assertFalse($todo->is_completed);

        // Toggle complete (F12)
        $toggleResponse = $this->actingAs($this->adminUser)
            ->postJson(route('todos.toggle', $todo));

        $toggleResponse->assertOk();
        $this->assertTrue($todo->fresh()->is_completed);

        // Delete todo
        $delResponse = $this->actingAs($this->adminUser)
            ->deleteJson(route('todos.destroy', $todo));

        $delResponse->assertOk();
        $this->assertNull(Todo::find($todo->id));
    }

    public function test_reminder_creation_and_dismissal(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('reminders.store'), [
                'title' => 'Test Supplier Payment Due Reminder',
                'reminder_date' => now()->toDateString(),
                'reminder_time' => '04:00 PM',
                'notes' => 'Settle bill before delivery',
            ]);

        $response->assertOk();
        $reminder = Reminder::where('title', 'Test Supplier Payment Due Reminder')->first();
        $this->assertNotNull($reminder);
        $this->assertFalse($reminder->is_dismissed);

        // Dismiss
        $dismissResponse = $this->actingAs($this->adminUser)
            ->postJson(route('reminders.dismiss', $reminder));

        $dismissResponse->assertOk();
        $this->assertTrue($reminder->fresh()->is_dismissed);
    }

    public function test_purchase_creation_updates_stock_and_records_voucher(): void
    {
        $supplier = Supplier::create([
            'name' => 'Apex Test Supplies',
            'phone' => '9988776655',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $category = Category::firstOrCreate(['name' => 'Staples'], ['is_active' => true]);

        $food = Food::first() ?? Food::create([
            'category_id' => $category->id,
            'name' => 'Basmati Test Rice',
            'code' => 'BTR-01',
            'price' => 200,
            'cost_price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $initialStock = $food->stock;

        $response = $this->actingAs($this->adminUser)
            ->post(route('purchases.store'), [
                'purchase_date' => now()->toDateString(),
                'invoice_no' => 'INV-TEST-001',
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'payment_status' => 'partial',
                'payment_method' => 'bank',
                'discount_amount' => 50,
                'paid_amount' => 500,
                'items' => [
                    [
                        'item_type' => 'food',
                        'item_id' => $food->id,
                        'item_name' => $food->name,
                        'quantity' => 5,
                        'unit' => 'Pcs',
                        'unit_price' => 150,
                        'tax_percent' => 5,
                    ],
                ],
            ]);

        $response->assertRedirect();

        // Verify stock incremented
        $this->assertEquals($initialStock + 5, $food->fresh()->stock);

        // Verify purchase recorded
        $purchase = Purchase::where('invoice_no', 'INV-TEST-001')->first();
        $this->assertNotNull($purchase);
        $this->assertEquals(5, $purchase->items->first()->quantity);

        // Verify supplier due updated
        $this->assertGreaterThan(0, $supplier->fresh()->current_balance);
    }

    public function test_purchase_orders_and_conversion(): void
    {
        $supplier = Supplier::create([
            'name' => 'Dairy Test Vendor',
            'phone' => '9888877771',
        ]);

        $category = Category::firstOrCreate(['name' => 'Dairy'], ['is_active' => true]);

        $food = Food::create([
            'category_id' => $category->id,
            'name' => 'Test Cheese 1kg',
            'code' => 'TC-01',
            'price' => 450,
            'cost_price' => 380,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('purchase-orders.store'), [
                'po_date' => now()->toDateString(),
                'expected_date' => now()->addDays(3)->toDateString(),
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'items' => [
                    [
                        'item_type' => 'food',
                        'item_id' => $food->id,
                        'item_name' => 'Test Cheese 1kg',
                        'quantity' => 3,
                        'unit' => 'Kg',
                        'unit_price' => 400,
                    ],
                ],
            ]);

        $response->assertRedirect(route('purchase-orders.index'));

        $po = PurchaseOrder::latest('id')->first();
        $this->assertNotNull($po);
        $this->assertEquals('sent', $po->status);

        // Convert PO to Purchase
        $convertResponse = $this->actingAs($this->adminUser)
            ->post(route('purchase-orders.convert', $po));

        $convertResponse->assertRedirect();
        $this->assertEquals('received', $po->fresh()->status);
    }

    public function test_purchase_return_debit_note_creation(): void
    {
        $supplier = Supplier::create([
            'name' => 'Return Test Vendor',
            'phone' => '9888877772',
        ]);

        $category = Category::firstOrCreate(['name' => 'Fresh Supplies'], ['is_active' => true]);

        $food = Food::create([
            'category_id' => $category->id,
            'name' => 'Return Test Food',
            'code' => 'RTF-01',
            'price' => 120,
            'cost_price' => 90,
            'stock' => 10,
            'is_active' => true,
        ]);

        $initialStock = $food->stock;

        $response = $this->actingAs($this->adminUser)
            ->post(route('purchase-returns.store'), [
                'return_date' => now()->toDateString(),
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'refund_status' => 'adjusted',
                'reason' => 'Damaged packet',
                'items' => [
                    [
                        'item_type' => 'food',
                        'item_id' => $food->id,
                        'item_name' => $food->name,
                        'quantity' => 1,
                        'unit' => 'Pcs',
                        'unit_price' => 100,
                    ],
                ],
            ]);

        $response->assertRedirect();

        $pr = PurchaseReturn::latest('id')->first();
        $this->assertNotNull($pr);
        $this->assertEquals(100, $pr->total_amount);
    }

    public function test_customer_receipt_voucher_settles_balance(): void
    {
        $customer = Customer::create([
            'name' => 'VIP Customer',
            'phone' => '9876500001',
            'balance' => 5000,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.receipt.store'), [
                'voucher_date' => now()->toDateString(),
                'customer_id' => $customer->id,
                'amount' => 2000,
                'payment_method' => 'cash',
                'deposit_to' => 'Cash in Hand Account',
                'narration' => 'Part payment settlement',
            ]);

        $response->assertRedirect(route('accounting.receipt'));

        $this->assertEquals(3000, (float) $customer->fresh()->balance);
    }

    public function test_vendor_payment_voucher(): void
    {
        $supplier = Supplier::create([
            'name' => 'Metro Vendor',
            'phone' => '9876500002',
            'opening_balance' => 4000,
            'current_balance' => 4000,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounting.payment.store'), [
                'voucher_date' => now()->toDateString(),
                'supplier_id' => $supplier->id,
                'amount' => 1500,
                'payment_method' => 'bank',
                'paid_from' => 'Primary Bank Account (HDFC)',
                'narration' => 'Advance settlement',
            ]);

        $response->assertRedirect(route('accounting.payment'));

        $this->assertEquals(2500, (float) $supplier->fresh()->current_balance);
    }

    public function test_all_reports_and_masters_views_render_200(): void
    {
        $routes = [
            route('accounting.ledgers'),
            route('accounting.receipt'),
            route('accounting.payment'),
            route('accounting.receivables'),
            route('accounting.payables'),
            route('accounting.trial-balance'),
            route('accounting.ledger-statement'),
            route('accounting.voucher-register'),
            route('accounting.business-view'),
            route('accounting.sales-register'),
            route('inventory-reports.stock-status'),
            route('inventory-reports.stock-vouchers'),
            route('inventory-reports.movement-summary'),
            route('inventory-reports.barcode-print'),
            route('inventory-reports.gst-reports'),
            route('purchases.index'),
            route('purchases.create'),
            route('purchase-orders.index'),
            route('purchase-returns.index'),
        ];

        foreach ($routes as $url) {
            $resp = $this->actingAs($this->adminUser)->get($url);
            $resp->assertOk();
        }
    }
}
