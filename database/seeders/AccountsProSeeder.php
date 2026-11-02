<?php

namespace Database\Seeders;

use App\Models\AccountingVoucher;
use App\Models\Food;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Reminder;
use App\Models\Supplier;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccountsProSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        // 1. Seed Suppliers if none exist
        if (Supplier::count() === 0) {
            $suppliers = [
                [
                    'name' => 'Metro Cash & Carry India',
                    'company_name' => 'Metro Cash & Carry Wholesale Pvt Ltd',
                    'phone' => '9820011223',
                    'email' => 'orders@metrocashcarry.in',
                    'gstin' => '27AAACM1234F1Z5',
                    'address' => 'Plot 45, MIDC Industrial Area, Mumbai',
                    'opening_balance' => 0.00,
                    'current_balance' => 14500.00,
                    'bank_name' => 'HDFC Bank',
                    'bank_account_no' => '50200012345678',
                    'bank_ifsc' => 'HDFC0000123',
                    'status' => true,
                ],
                [
                    'name' => 'Kisan Fresh Farm Supplies',
                    'company_name' => 'Kisan Agri Fresh Foods LLP',
                    'phone' => '9845099887',
                    'email' => 'support@kisanfarmfresh.com',
                    'gstin' => '27AABCK9876E1Z2',
                    'address' => 'Shop 12, APMC Vegetable Market, Vashi',
                    'opening_balance' => 2500.00,
                    'current_balance' => 6200.00,
                    'bank_name' => 'State Bank of India',
                    'bank_account_no' => '30123456789',
                    'bank_ifsc' => 'SBIN0001234',
                    'status' => true,
                ],
                [
                    'name' => 'Amul Dairy Distributors',
                    'company_name' => 'Gujarat Co-operative Milk Mktg Fed',
                    'phone' => '9821144332',
                    'email' => 'sales@amuldairyagency.in',
                    'gstin' => '27AAACG0001A1Z9',
                    'address' => 'Sector 17, New Mumbai',
                    'opening_balance' => 0.00,
                    'current_balance' => 3800.00,
                    'bank_name' => 'ICICI Bank',
                    'bank_account_no' => '001105001234',
                    'bank_ifsc' => 'ICIC0000011',
                    'status' => true,
                ],
            ];

            foreach ($suppliers as $data) {
                Supplier::create($data);
            }
        }

        $supplierMetro = Supplier::first();
        $supplierKisan = Supplier::skip(1)->first() ?? $supplierMetro;

        // 2. Seed Sample Purchases if none exist
        if (Purchase::count() === 0) {
            $today = Carbon::today();
            $foods = Food::take(3)->get();

            // Today's purchase
            $p1 = Purchase::create([
                'purchase_no' => 'PUR-2026-0001',
                'invoice_no' => 'INV-MET-8841',
                'purchase_date' => $today->toDateString(),
                'supplier_id' => $supplierMetro->id,
                'supplier_name' => $supplierMetro->name,
                'subtotal' => 12500.00,
                'tax_amount' => 625.00,
                'discount_amount' => 0.00,
                'grand_total' => 13125.00,
                'paid_amount' => 5000.00,
                'due_amount' => 8125.00,
                'payment_status' => 'partial',
                'payment_method' => 'bank',
                'notes' => 'Weekly grocery and staples restock',
                'created_by' => $adminId,
            ]);

            PurchaseItem::create([
                'purchase_id' => $p1->id,
                'item_type' => 'food',
                'item_id' => $foods->first()?->id ?? 1,
                'item_name' => $foods->first()?->name ?? 'Basmati Rice Premium 25kg',
                'quantity' => 10,
                'unit' => 'Bags',
                'unit_price' => 950.00,
                'tax_percent' => 5.00,
                'tax_amount' => 475.00,
                'total_amount' => 9975.00,
            ]);

            PurchaseItem::create([
                'purchase_id' => $p1->id,
                'item_type' => 'inventory',
                'item_id' => null,
                'item_name' => 'Cooking Sunflower Oil 15L Tin',
                'quantity' => 2,
                'unit' => 'Tins',
                'unit_price' => 1500.00,
                'tax_percent' => 5.00,
                'tax_amount' => 150.00,
                'total_amount' => 3150.00,
            ]);

            // Earlier this month purchase
            $p2 = Purchase::create([
                'purchase_no' => 'PUR-2026-0002',
                'invoice_no' => 'INV-KIS-4402',
                'purchase_date' => $today->copy()->subDays(3)->toDateString(),
                'supplier_id' => $supplierKisan->id,
                'supplier_name' => $supplierKisan->name,
                'subtotal' => 4500.00,
                'tax_amount' => 0.00,
                'discount_amount' => 100.00,
                'grand_total' => 4400.00,
                'paid_amount' => 4400.00,
                'due_amount' => 0.00,
                'payment_status' => 'paid',
                'payment_method' => 'cash',
                'notes' => 'Fresh vegetables & herbs delivery',
                'created_by' => $adminId,
            ]);

            PurchaseItem::create([
                'purchase_id' => $p2->id,
                'item_type' => 'inventory',
                'item_id' => null,
                'item_name' => 'Assorted Farm Vegetables & Greens',
                'quantity' => 45,
                'unit' => 'Kg',
                'unit_price' => 100.00,
                'tax_percent' => 0.00,
                'tax_amount' => 0.00,
                'total_amount' => 4500.00,
            ]);
        }

        // 3. Seed Sample Purchase Order
        if (PurchaseOrder::count() === 0) {
            $po = PurchaseOrder::create([
                'po_number' => 'PO-2026-0001',
                'po_date' => Carbon::today()->toDateString(),
                'expected_date' => Carbon::today()->addDays(2)->toDateString(),
                'supplier_id' => $supplierMetro->id,
                'supplier_name' => $supplierMetro->name,
                'total_amount' => 8500.00,
                'status' => 'sent',
                'notes' => 'Urgent replenishment for weekend rush',
                'created_by' => $adminId,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'item_type' => 'food',
                'item_id' => 1,
                'item_name' => 'Paneer Fresh Dairy 5Kg pack',
                'quantity' => 4,
                'unit' => 'Pack',
                'unit_price' => 1200.00,
                'total_amount' => 4800.00,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'item_type' => 'inventory',
                'item_id' => null,
                'item_name' => 'Dairy Fresh Butter 500g',
                'quantity' => 20,
                'unit' => 'Pcs',
                'unit_price' => 185.00,
                'total_amount' => 3700.00,
            ]);
        }

        // 4. Seed Sample Purchase Return
        if (PurchaseReturn::count() === 0) {
            $firstPurchase = Purchase::first();
            $pr = PurchaseReturn::create([
                'return_no' => 'PR-2026-0001',
                'return_date' => Carbon::today()->toDateString(),
                'purchase_id' => $firstPurchase?->id,
                'supplier_id' => $supplierMetro->id,
                'supplier_name' => $supplierMetro->name,
                'total_amount' => 950.00,
                'refund_status' => 'adjusted',
                'reason' => 'Damaged packaging during transit',
                'created_by' => $adminId,
            ]);

            PurchaseReturnItem::create([
                'purchase_return_id' => $pr->id,
                'item_type' => 'food',
                'item_id' => 1,
                'item_name' => 'Basmati Rice Premium 25kg',
                'quantity' => 1,
                'unit' => 'Bags',
                'unit_price' => 950.00,
                'total_amount' => 950.00,
            ]);
        }

        // 5. Seed Accounting Vouchers
        if (AccountingVoucher::count() === 0) {
            AccountingVoucher::create([
                'voucher_no' => 'RCP-202611-0001',
                'voucher_type' => 'receipt',
                'voucher_date' => Carbon::today()->toDateString(),
                'party_type' => 'customer',
                'party_id' => 1,
                'party_name' => 'Walk-in Customer / Corporate Client',
                'debit_ledger' => 'Cash Account',
                'credit_ledger' => 'Sundry Debtors',
                'amount' => 3500.00,
                'payment_method' => 'cash',
                'reference_no' => 'CASH-REC-01',
                'narration' => 'Payment received against outstanding bill',
                'created_by' => $adminId,
            ]);

            AccountingVoucher::create([
                'voucher_no' => 'PMT-202611-0001',
                'voucher_type' => 'payment',
                'voucher_date' => Carbon::today()->toDateString(),
                'party_type' => 'supplier',
                'party_id' => $supplierMetro->id,
                'party_name' => $supplierMetro->name,
                'debit_ledger' => 'Sundry Creditors (Metro)',
                'credit_ledger' => 'HDFC Bank Account',
                'amount' => 5000.00,
                'payment_method' => 'bank',
                'reference_no' => 'HDFC-NEFT-99120',
                'narration' => 'Advance against INV-MET-8841',
                'created_by' => $adminId,
            ]);
        }

        // 6. Seed Todos
        if (Todo::count() === 0) {
            Todo::create([
                'user_id' => $adminId,
                'title' => 'Verify supplier invoices with stock receipts',
                'priority' => 'high',
                'due_date' => Carbon::today()->toDateString(),
                'is_completed' => false,
            ]);

            Todo::create([
                'user_id' => $adminId,
                'title' => 'Review GST monthly outward liability report',
                'priority' => 'medium',
                'due_date' => Carbon::today()->addDay()->toDateString(),
                'is_completed' => false,
            ]);

            Todo::create([
                'user_id' => $adminId,
                'title' => 'Prepare weekly cash book tally for day closing',
                'priority' => 'high',
                'due_date' => Carbon::today()->toDateString(),
                'is_completed' => true,
                'completed_at' => Carbon::now(),
            ]);
        }

        // 7. Seed Reminders
        if (Reminder::count() === 0) {
            Reminder::create([
                'user_id' => $adminId,
                'title' => 'Vendor payment settlement due for Metro Cash & Carry',
                'reminder_date' => Carbon::today()->toDateString(),
                'reminder_time' => '05:30 PM',
                'notes' => 'Clear pending due of Rs. 8,125 before next batch delivery',
                'is_dismissed' => false,
            ]);

            Reminder::create([
                'user_id' => $adminId,
                'title' => 'FSSAI License Renewal Inspection',
                'reminder_date' => Carbon::today()->addDays(5)->toDateString(),
                'reminder_time' => '11:00 AM',
                'notes' => 'Keep pest control and water test certificates ready',
                'is_dismissed' => false,
            ]);
        }
    }
}
