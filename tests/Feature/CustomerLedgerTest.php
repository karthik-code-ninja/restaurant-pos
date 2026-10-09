<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Food;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerLedgerTest extends TestCase
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
            'email' => 'admin_'.uniqid().'@restaurant.test',
            'status' => true,
            'role_id' => $adminRole->id,
        ]);
    }

    public function test_pos_index_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('pos.index'));

        $response->assertOk();
        $response->assertSee('Customer Ledger Account');
    }

    public function test_customer_can_be_created_with_opening_balance(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('customers.store'), [
                'name' => 'John Doe',
                'phone' => '9876543210',
                'email' => 'john@example.com',
                'credit_limit' => 5000.00,
                'opening_balance' => 1200.00,
                'opening_balance_type' => 'due',
                'notes' => 'Regular corporate customer',
            ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', [
            'name' => 'John Doe',
            'phone' => '9876543210',
            'balance' => 1200.00,
        ]);

        $customer = Customer::where('phone', '9876543210')->first();
        $this->assertNotNull($customer);
        $this->assertDatabaseHas('customer_ledgers', [
            'customer_id' => $customer->id,
            'type' => 'debit',
            'amount' => 1200.00,
            'balance_after' => 1200.00,
        ]);
    }

    public function test_customer_quick_store_from_pos_api(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('pos.customers.quick-store'), [
                'name' => 'Alice Corporate',
                'phone' => '9123456780',
                'credit_limit' => 10000.00,
                'opening_balance' => 500.00,
                'opening_balance_type' => 'due',
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('customer.name', 'Alice Corporate');

        $this->assertDatabaseHas('customers', [
            'name' => 'Alice Corporate',
            'phone' => '9123456780',
            'balance' => 500.00,
        ]);
    }

    public function test_customer_search_api(): void
    {
        Customer::create([
            'name' => 'Robert Fastfood',
            'phone' => '9988776655',
            'balance' => 250.00,
            'credit_limit' => 2000.00,
            'status' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('pos.customers.search', ['query' => 'Robert']));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(1, 'customers');
        $response->assertJsonPath('customers.0.name', 'Robert Fastfood');
        $this->assertEquals(250.0, (float) $response->json('customers.0.balance'));
    }

    public function test_pos_billing_with_credit_payment_updates_ledger_and_balance(): void
    {
        $category = Category::create([
            'name' => 'Beverages',
            'is_active' => true,
        ]);

        $food = Food::create([
            'category_id' => $category->id,
            'code' => 'COFFEE01',
            'name' => 'Cold Coffee',
            'price' => 100.00,
            'tax_rate' => 5.00,
            'is_veg' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Mark Spencer',
            'phone' => '9443322110',
            'balance' => 0.00,
            'credit_limit' => 3000.00,
            'status' => true,
        ]);

        // Complete a POS bill on Credit: 2 Cold Coffees = Subtotal 200 + Tax 5% (10) = Grand Total 210.00
        $payload = [
            'order_type' => 'counter',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'items' => [
                [
                    'item_type' => 'food',
                    'item_id' => $food->id,
                    'code' => $food->code,
                    'name' => $food->name,
                    'unit_price' => 100.00,
                    'quantity' => 2,
                    'discount_type' => 'fixed',
                    'discount_value' => 0,
                    'tax_rate' => 5.00,
                ],
            ],
            'payments' => [
                [
                    'payment_method' => 'credit',
                    'amount' => 210.00,
                    'reference_number' => 'CREDIT-PO-01',
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('pos.pay'), $payload);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        // Verify Customer balance updated to 210.00 (positive = owes money)
        $customer->refresh();
        $this->assertEquals(210.00, (float) $customer->balance);

        // Verify CustomerLedger record created
        $this->assertDatabaseHas('customer_ledgers', [
            'customer_id' => $customer->id,
            'type' => 'debit',
            'amount' => 210.00,
            'balance_after' => 210.00,
            'payment_mode' => 'credit_sale',
        ]);

        // Verify thermal receipt renders without error and displays customer balance
        $billId = $response->json('bill_id');
        $this->assertNotNull($billId);
        $printResponse = $this->actingAs($this->adminUser)
            ->get(route('print.bill', $billId));
        $printResponse->assertOk();
        $printResponse->assertSee('Customer Balance:');
    }

    public function test_cancelling_credit_bill_reverts_customer_balance(): void
    {
        $category = Category::create([
            'name' => 'Desserts',
            'is_active' => true,
        ]);

        $food = Food::create([
            'category_id' => $category->id,
            'code' => 'ICECREAM01',
            'name' => 'Vanilla Ice Cream',
            'price' => 100.00,
            'tax_rate' => 5.00,
            'is_veg' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Reversal Test Customer',
            'phone' => '9112233445',
            'balance' => 0.00,
            'status' => true,
        ]);

        $payRes = $this->actingAs($this->adminUser)
            ->postJson(route('pos.pay'), [
                'order_type' => 'counter',
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'items' => [
                    [
                        'item_type' => 'food',
                        'item_id' => $food->id,
                        'code' => $food->code,
                        'name' => $food->name,
                        'unit_price' => 100.00,
                        'quantity' => 1,
                        'discount_type' => 'fixed',
                        'discount_value' => 0,
                        'tax_rate' => 5.00,
                    ],
                ],
                'payments' => [
                    [
                        'payment_method' => 'credit',
                        'amount' => 105.00,
                    ],
                ],
            ]);

        $payRes->assertOk();
        $billId = $payRes->json('bill_id');
        $this->assertNotNull($billId);

        $customer->refresh();
        $this->assertEquals(105.00, (float) $customer->balance);

        // Cancel the bill
        $cancelRes = $this->actingAs($this->adminUser)
            ->postJson(route('pos.cancel', $billId), [
                'reason' => 'Guest cancelled table',
            ]);

        $cancelRes->assertOk();

        // Customer balance should be restored to 0.00
        $customer->refresh();
        $this->assertEquals(0.00, (float) $customer->balance);

        // Assert reversal credit entry exists
        $this->assertDatabaseHas('customer_ledgers', [
            'customer_id' => $customer->id,
            'type' => 'credit',
            'amount' => 105.00,
            'balance_after' => 0.00,
        ]);
    }

    public function test_settling_payment_against_customer_due_reduces_balance(): void
    {
        $customer = Customer::create([
            'name' => 'David Miller',
            'phone' => '9554433221',
            'balance' => 1500.00,
            'credit_limit' => 5000.00,
            'status' => true,
        ]);

        // Settle ₹1000 cash payment against due
        $response = $this->actingAs($this->adminUser)
            ->post(route('customers.payment', $customer), [
                'amount' => 1000.00,
                'payment_mode' => 'cash',
                'reference_number' => 'REC-1001',
                'notes' => 'Part payment received at counter',
            ]);

        $response->assertRedirect();

        $customer->refresh();
        // 1500 - 1000 = 500 remaining due
        $this->assertEquals(500.00, (float) $customer->balance);

        $this->assertDatabaseHas('customer_ledgers', [
            'customer_id' => $customer->id,
            'type' => 'credit',
            'amount' => 1000.00,
            'balance_after' => 500.00,
            'payment_mode' => 'cash',
        ]);
    }

    public function test_customer_ledger_json_endpoint_returns_recent_entries(): void
    {
        $customer = Customer::create([
            'name' => 'Emma Watson',
            'phone' => '9778899001',
            'balance' => 300.00,
            'status' => true,
        ]);

        CustomerLedger::create([
            'customer_id' => $customer->id,
            'entry_date' => now(),
            'type' => 'debit',
            'amount' => 300.00,
            'balance_after' => 300.00,
            'payment_mode' => 'opening_balance',
            'description' => 'Opening balance',
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('pos.customers.ledger.json', $customer));

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('customer.name', 'Emma Watson');
        $response->assertJsonCount(1, 'entries');
        $response->assertJsonPath('entries.0.type', 'debit');
        $this->assertEquals(300.0, (float) $response->json('entries.0.amount'));
    }

    public function test_pos_split_payment_with_partial_credit_records_correct_ledger_amount(): void
    {
        $category = Category::create([
            'name' => 'Pizza',
            'is_active' => true,
        ]);

        $food = Food::create([
            'category_id' => $category->id,
            'code' => 'PIZZA01',
            'name' => 'Farmhouse Pizza',
            'price' => 200.00,
            'tax_rate' => 5.00,
            'is_veg' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'name' => 'Split Pay Customer',
            'phone' => '9888877777',
            'balance' => 0.00,
            'status' => true,
        ]);

        // Bill: 1 Pizza = 200 + 5% Tax (10) = 210.00
        // Split: 100 Cash + 110 Credit
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('pos.pay'), [
                'order_type' => 'counter',
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'items' => [
                    [
                        'item_type' => 'food',
                        'item_id' => $food->id,
                        'code' => $food->code,
                        'name' => $food->name,
                        'unit_price' => 200.00,
                        'quantity' => 1,
                        'discount_type' => 'fixed',
                        'discount_value' => 0,
                        'tax_rate' => 5.00,
                    ],
                ],
                'payments' => [
                    [
                        'payment_method' => 'cash',
                        'amount' => 100.00,
                    ],
                    [
                        'payment_method' => 'credit',
                        'amount' => 110.00,
                        'reference_number' => 'SPLIT-CREDIT-REF',
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        // Verify Customer balance only increased by the 110.00 credit portion
        $customer->refresh();
        $this->assertEquals(110.00, (float) $customer->balance);

        // Verify only 1 ledger entry was created for the credit amount (110.00)
        $this->assertDatabaseHas('customer_ledgers', [
            'customer_id' => $customer->id,
            'type' => 'debit',
            'amount' => 110.00,
            'balance_after' => 110.00,
        ]);
    }
}
