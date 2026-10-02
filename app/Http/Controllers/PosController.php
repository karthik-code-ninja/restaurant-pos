<?php

namespace App\Http\Controllers;

use App\Models\AddOn;
use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillItemAddon;
use App\Models\Category;
use App\Models\Combo;
use App\Models\Food;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Models\Setting;
use App\Services\BillCalculationService;
use App\Services\InventoryService;
use App\Services\InvoiceNumberService;
use App\Services\PrinterProfileService;
use App\Services\ReceiptPrintService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        protected BillCalculationService $calculationService,
        protected InventoryService $inventoryService,
        protected InvoiceNumberService $invoiceNumberService,
        protected ReceiptPrintService $receiptPrintService
    ) {}

    /**
     * Render the dedicated POS screen.
     */
    public function index(Request $request): View
    {
        $categories = Category::active()->orderBy('sort_order')->orderBy('name')->get();
        $tables = RestaurantTable::orderBy('floor')->orderBy('table_number')->get();
        $addons = AddOn::active()->orderBy('name')->get();
        $combos = Combo::active()->with('foods')->get();
        $waiters = \App\Models\User::where('status', true)->with('role')->orderBy('name')->get();
        $currency = Setting::get('currency_symbol', '₹');
        $taxType = Setting::get('tax_type', 'exclusive');
        $defaultGst = Setting::get('default_gst_rate', '5.00');
        $printerType = Setting::get('printer_type', '80mm');

        // Check if there is an active table bill requested
        $activeBill = null;
        if ($request->filled('table_id')) {
            $activeBill = Bill::with(['items.addons', 'table', 'waiter'])
                ->where('table_id', $request->table_id)
                ->whereIn('status', ['pending', 'held', 'draft'])
                ->latest()
                ->first();
        } elseif ($request->filled('bill_id')) {
            $activeBill = Bill::with(['items.addons', 'table', 'waiter'])->find($request->bill_id);
        }

        $printerProfiles = PrinterProfileService::getAllProfiles();
        $kitchenPrinters = PrinterProfileService::getKitchenPrinters();
        $counterPrinters = PrinterProfileService::getCounterPrinters();

        return view('pos.index', compact(
            'categories',
            'tables',
            'addons',
            'combos',
            'waiters',
            'currency',
            'taxType',
            'defaultGst',
            'printerType',
            'activeBill',
            'printerProfiles',
            'kitchenPrinters',
            'counterPrinters'
        ));
    }

    /**
     * Fast search foods API for POS.
     */
    public function searchFoods(Request $request): JsonResponse
    {
        $query = Food::active()->with('category');

        $isGlobal = $request->boolean('global_search');
        if (!$isGlobal && $request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('query')) {
            $q = trim($request->input('query'));
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
            $query->orderByRaw("CASE WHEN code = ? THEN 1 WHEN code LIKE ? THEN 2 WHEN name LIKE ? THEN 3 ELSE 4 END", [$q, "{$q}%", "{$q}%"]);
        }

        if ($request->has('veg_only') && $request->boolean('veg_only')) {
            $query->where('is_veg', true);
        }

        $foods = $query->orderBy('name')->limit(50)->get()->map(function ($f) {
            return [
                'id' => $f->id,
                'type' => 'food',
                'code' => $f->code,
                'name' => $f->name,
                'price' => (float) $f->price,
                'tax_rate' => (float) $f->tax_rate,
                'is_veg' => (bool) $f->is_veg,
                'category' => $f->category?->name,
                'image' => $f->image ? asset('storage/' . $f->image) : null,
            ];
        });

        // Also fetch active combos matching search
        $comboQuery = Combo::active()->with('foods');
        if ($request->filled('query')) {
            $q = trim($request->input('query'));
            $comboQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
            $comboQuery->orderByRaw("CASE WHEN code = ? THEN 1 WHEN code LIKE ? THEN 2 WHEN name LIKE ? THEN 3 ELSE 4 END", [$q, "{$q}%", "{$q}%"]);
        }
        $combos = $comboQuery->get()->map(function ($c) {
            return [
                'id' => $c->id,
                'type' => 'combo',
                'code' => $c->code,
                'name' => $c->name,
                'price' => (float) $c->price,
                'tax_rate' => (float) $c->tax_rate,
                'is_veg' => false,
                'category' => 'Combo Special',
                'image' => null,
                'description' => $c->description,
            ];
        });

        return response()->json([
            'success' => true,
            'foods' => $foods,
            'combos' => $combos,
        ]);
    }

    /**
     * Centralized bill calculation preview.
     */
    public function calculate(Request $request): JsonResponse
    {
        $cartItems = $request->input('items', []);
        $discountType = $request->input('discount_type', 'fixed');
        $discountValue = (float) $request->input('discount_value', 0);

        $calculation = $this->calculationService->calculate($cartItems, $discountType, $discountValue);

        return response()->json([
            'success' => true,
            'data' => $calculation,
        ]);
    }

    /**
     * Save Bill (as Draft, Held, or Pending).
     */
    public function saveBill(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bill_id' => ['nullable', 'exists:bills,id'],
            'order_type' => ['required', 'in:table,counter'],
            'table_id' => ['nullable', 'required_if:order_type,table', 'exists:restaurant_tables,id'],
            'waiter_id' => ['nullable', 'exists:users,id'],
            'waiter_name' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'in:draft,held,pending'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'discount_type' => ['nullable', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:food,combo'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.addons' => ['nullable', 'array'],
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $calculation = $this->calculationService->calculate(
                $validated['items'],
                $validated['discount_type'] ?? 'fixed',
                (float) ($validated['discount_value'] ?? 0)
            );

            if (empty($calculation['items'])) {
                return response()->json(['success' => false, 'message' => 'No valid items found in cart.'], 422);
            }

            if (!empty($validated['bill_id'])) {
                $bill = Bill::lockForUpdate()->findOrFail($validated['bill_id']);
                if (in_array($bill->status, ['completed', 'cancelled'])) {
                    return response()->json(['success' => false, 'message' => 'Cannot edit a closed or cancelled bill.'], 422);
                }

                $oldValues = $bill->toArray();
                $bill->update([
                    'order_type' => $validated['order_type'],
                    'table_id' => $validated['order_type'] === 'table' ? $validated['table_id'] : null,
                    'waiter_id' => $validated['waiter_id'] ?? $bill->waiter_id,
                    'waiter_name' => $validated['waiter_name'] ?? $bill->waiter_name,
                    'status' => $validated['status'],
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'subtotal' => $calculation['subtotal'],
                    'discount_type' => $calculation['discount_type'],
                    'discount_value' => $calculation['discount_value'],
                    'discount_amount' => $calculation['discount_amount'],
                    'tax_total' => $calculation['tax_total'],
                    'cgst_total' => $calculation['cgst_total'],
                    'sgst_total' => $calculation['sgst_total'],
                    'igst_total' => $calculation['igst_total'],
                    'rounding_difference' => $calculation['rounding_difference'],
                    'grand_total' => $calculation['grand_total'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Replace items
                $bill->items()->delete();

                AuditLog::log(
                    action: 'bill_edited',
                    module: 'pos',
                    referenceId: (string) $bill->id,
                    oldValues: $oldValues,
                    newValues: $bill->toArray(),
                    description: "Bill #{$bill->invoice_number} updated with status '{$bill->status}'"
                );
            } else {
                $invoiceNum = $this->invoiceNumberService->generate();

                $bill = Bill::create([
                    'invoice_number' => $invoiceNum,
                    'order_type' => $validated['order_type'],
                    'table_id' => $validated['order_type'] === 'table' ? $validated['table_id'] : null,
                    'cashier_id' => Auth::id(),
                    'waiter_id' => $validated['waiter_id'] ?? null,
                    'waiter_name' => $validated['waiter_name'] ?? null,
                    'status' => $validated['status'],
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'subtotal' => $calculation['subtotal'],
                    'discount_type' => $calculation['discount_type'],
                    'discount_value' => $calculation['discount_value'],
                    'discount_amount' => $calculation['discount_amount'],
                    'tax_total' => $calculation['tax_total'],
                    'cgst_total' => $calculation['cgst_total'],
                    'sgst_total' => $calculation['sgst_total'],
                    'igst_total' => $calculation['igst_total'],
                    'rounding_difference' => $calculation['rounding_difference'],
                    'grand_total' => $calculation['grand_total'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                AuditLog::log(
                    action: 'bill_created',
                    module: 'pos',
                    referenceId: (string) $bill->id,
                    description: "Bill #{$bill->invoice_number} created with status '{$bill->status}'"
                );
            }

            // Save items with protected historical prices
            foreach ($calculation['items'] as $itemData) {
                $billItem = BillItem::create([
                    'bill_id' => $bill->id,
                    'item_type' => $itemData['item_type'],
                    'item_id' => $itemData['item_id'],
                    'food_code' => $itemData['food_code'],
                    'hsn_code' => $itemData['hsn_code'] ?? null,
                    'item_name' => $itemData['item_name'],
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $itemData['subtotal'],
                    'tax_rate' => $itemData['tax_rate'],
                    'tax_amount' => $itemData['tax_amount'],
                    'cgst_amount' => $itemData['cgst_amount'],
                    'sgst_amount' => $itemData['sgst_amount'],
                    'igst_amount' => $itemData['igst_amount'],
                    'total' => $itemData['total'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // Save addons if any
                if (!empty($itemData['addons'])) {
                    foreach ($itemData['addons'] as $addon) {
                        BillItemAddon::create([
                            'bill_item_id' => $billItem->id,
                            'add_on_id' => $addon['add_on_id'],
                            'name' => $addon['name'],
                            'price' => $addon['price'],
                            'tax_rate' => $addon['tax_rate'],
                            'tax_amount' => $addon['tax_amount'],
                            'total' => $addon['total'],
                        ]);
                    }
                }
            }

            // Update Table status
            if ($bill->order_type === 'table' && $bill->table_id) {
                $table = RestaurantTable::find($bill->table_id);
                if ($table && $table->status !== 'occupied') {
                    $table->update(['status' => 'occupied']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Bill #{$bill->invoice_number} saved as {$bill->status}.",
                'bill_id' => $bill->id,
                'invoice_number' => $bill->invoice_number,
                'status' => $bill->status,
            ]);
        });
    }

    /**
     * Complete Bill and Process Payment.
     */
    public function completePayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bill_id' => ['nullable', 'exists:bills,id'],
            'order_type' => ['required', 'in:table,counter'],
            'table_id' => ['nullable', 'required_if:order_type,table', 'exists:restaurant_tables,id'],
            'waiter_id' => ['nullable', 'exists:users,id'],
            'waiter_name' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'discount_type' => ['nullable', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:food,combo'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.addons' => ['nullable', 'array'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', 'in:cash,upi,card'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Calculate verified totals
            $calculation = $this->calculationService->calculate(
                $validated['items'],
                $validated['discount_type'] ?? 'fixed',
                (float) ($validated['discount_value'] ?? 0)
            );

            // 2. Validate Payment Total matches Grand Total
            $paymentTotal = array_sum(array_column($validated['payments'], 'amount'));
            if (abs($paymentTotal - $calculation['grand_total']) > 0.05) {
                return response()->json([
                    'success' => false,
                    'message' => "Payment amount (₹{$paymentTotal}) does not match bill grand total (₹{$calculation['grand_total']})."
                ], 422);
            }

            // 3. Find or Create Bill
            if (!empty($validated['bill_id'])) {
                $bill = Bill::lockForUpdate()->findOrFail($validated['bill_id']);
                if ($bill->status === 'completed') {
                    return response()->json(['success' => false, 'message' => 'This bill has already been completed.'], 422);
                }
                $bill->update([
                    'order_type' => $validated['order_type'],
                    'table_id' => $validated['order_type'] === 'table' ? $validated['table_id'] : null,
                    'waiter_id' => $validated['waiter_id'] ?? $bill->waiter_id,
                    'waiter_name' => $validated['waiter_name'] ?? $bill->waiter_name,
                    'status' => 'completed',
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'subtotal' => $calculation['subtotal'],
                    'discount_type' => $calculation['discount_type'],
                    'discount_value' => $calculation['discount_value'],
                    'discount_amount' => $calculation['discount_amount'],
                    'tax_total' => $calculation['tax_total'],
                    'cgst_total' => $calculation['cgst_total'],
                    'sgst_total' => $calculation['sgst_total'],
                    'igst_total' => $calculation['igst_total'],
                    'rounding_difference' => $calculation['rounding_difference'],
                    'grand_total' => $calculation['grand_total'],
                    'notes' => $validated['notes'] ?? null,
                    'completed_at' => now(),
                ]);

                $bill->items()->delete();
                $bill->payments()->delete();
            } else {
                $invoiceNum = $this->invoiceNumberService->generate();

                $bill = Bill::create([
                    'invoice_number' => $invoiceNum,
                    'order_type' => $validated['order_type'],
                    'table_id' => $validated['order_type'] === 'table' ? $validated['table_id'] : null,
                    'cashier_id' => Auth::id(),
                    'waiter_id' => $validated['waiter_id'] ?? null,
                    'waiter_name' => $validated['waiter_name'] ?? null,
                    'status' => 'completed',
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'subtotal' => $calculation['subtotal'],
                    'discount_type' => $calculation['discount_type'],
                    'discount_value' => $calculation['discount_value'],
                    'discount_amount' => $calculation['discount_amount'],
                    'tax_total' => $calculation['tax_total'],
                    'cgst_total' => $calculation['cgst_total'],
                    'sgst_total' => $calculation['sgst_total'],
                    'igst_total' => $calculation['igst_total'],
                    'rounding_difference' => $calculation['rounding_difference'],
                    'grand_total' => $calculation['grand_total'],
                    'notes' => $validated['notes'] ?? null,
                    'completed_at' => now(),
                ]);
            }

            // 4. Save items with historical snapshot
            foreach ($calculation['items'] as $itemData) {
                $billItem = BillItem::create([
                    'bill_id' => $bill->id,
                    'item_type' => $itemData['item_type'],
                    'item_id' => $itemData['item_id'],
                    'food_code' => $itemData['food_code'],
                    'hsn_code' => $itemData['hsn_code'] ?? null,
                    'item_name' => $itemData['item_name'],
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $itemData['subtotal'],
                    'tax_rate' => $itemData['tax_rate'],
                    'tax_amount' => $itemData['tax_amount'],
                    'cgst_amount' => $itemData['cgst_amount'],
                    'sgst_amount' => $itemData['sgst_amount'],
                    'igst_amount' => $itemData['igst_amount'],
                    'total' => $itemData['total'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if (!empty($itemData['addons'])) {
                    foreach ($itemData['addons'] as $addon) {
                        BillItemAddon::create([
                            'bill_item_id' => $billItem->id,
                            'add_on_id' => $addon['add_on_id'],
                            'name' => $addon['name'],
                            'price' => $addon['price'],
                            'tax_rate' => $addon['tax_rate'],
                            'tax_amount' => $addon['tax_amount'],
                            'total' => $addon['total'],
                        ]);
                    }
                }
            }

            // 5. Save Payments
            foreach ($validated['payments'] as $payment) {
                Payment::create([
                    'bill_id' => $bill->id,
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                    'reference_number' => $payment['reference_number'] ?? null,
                    'received_by' => Auth::id(),
                ]);
            }

            // 6. Safe Inventory Deduction
            $this->inventoryService->deductStockForBill($bill);

            // 7. Update Table status to available
            if ($bill->table_id) {
                $table = RestaurantTable::find($bill->table_id);
                if ($table) {
                    $table->update(['status' => 'available']);
                }
            }

            // 8. Audit Log
            AuditLog::log(
                action: 'bill_completed',
                module: 'pos',
                referenceId: (string) $bill->id,
                description: "Bill #{$bill->invoice_number} completed for ₹{$bill->grand_total} via " . implode(', ', array_column($validated['payments'], 'payment_method'))
            );

            // 9. Hardware Counter Printer Routing
            $counterProfiles = PrinterProfileService::getCounterPrinters();
            $directPrintStatus = [];
            foreach ($counterProfiles as $cProf) {
                if (($cProf['connection_type'] ?? '') === 'network' && !empty($cProf['ip_address'])) {
                    $receiptData = $this->receiptPrintService->prepareReceipt($bill);
                    $escpos = PrinterProfileService::generateReceiptEscpos($receiptData, $cProf);
                    $res = PrinterProfileService::printRawEscpos($cProf['ip_address'], (int) ($cProf['port'] ?? 9100), $escpos);
                    $directPrintStatus[] = [
                        'printer' => $cProf['name'],
                        'success' => $res['success'],
                        'message' => $res['message'],
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Bill #{$bill->invoice_number} completed successfully!",
                'bill_id' => $bill->id,
                'invoice_number' => $bill->invoice_number,
                'print_url' => route('print.bill', ['bill' => $bill->id]),
                'counter_printers' => $counterProfiles,
                'direct_prints' => $directPrintStatus,
            ]);
        });
    }

    /**
     * Get Held Bills.
     */
    public function getHeldBills(): JsonResponse
    {
        $heldBills = Bill::with(['table', 'cashier', 'items.addons'])
            ->where('status', 'held')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'bills' => $heldBills,
        ]);
    }

    /**
     * Get Draft Bills.
     */
    public function getDraftBills(): JsonResponse
    {
        $draftBills = Bill::with(['table', 'cashier', 'items.addons'])
            ->where('status', 'draft')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'bills' => $draftBills,
        ]);
    }

    /**
     * Cancel Bill.
     */
    public function cancelBill(Request $request, Bill $bill): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($bill->status === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Bill is already cancelled.'], 422);
        }

        return DB::transaction(function () use ($bill, $request) {
            $reason = $request->reason;
            $oldStatus = $bill->status;

            // Reverse inventory if deducted
            $this->inventoryService->reverseStockForBill($bill, $reason);

            $bill->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
            ]);

            // Release table if occupied
            if ($bill->table_id) {
                $table = RestaurantTable::find($bill->table_id);
                if ($table && $table->status === 'occupied') {
                    $table->update(['status' => 'available']);
                }
            }

            AuditLog::log(
                action: 'bill_cancelled',
                module: 'pos',
                referenceId: (string) $bill->id,
                description: "Bill #{$bill->invoice_number} cancelled by " . Auth::user()->name . ". Reason: {$reason}"
            );

            return response()->json([
                'success' => true,
                'message' => "Bill #{$bill->invoice_number} cancelled successfully.",
            ]);
        });
    }

    /**
     * Clear Cart / Cancel active order and release table.
     */
    public function clearCartOrder(Request $request): JsonResponse
    {
        $billId = $request->input('bill_id');
        $tableId = $request->input('table_id');

        return DB::transaction(function () use ($billId, $tableId) {
            $releasedTable = null;
            $invoiceNum = null;

            // 1. If tableId provided, clean up any active bills on that table
            if ($tableId) {
                $tableBills = Bill::where('table_id', $tableId)
                    // ->whereIn('status', ['draft', 'held', 'pending'])
                    ->get();

                foreach ($tableBills as $tBill) {
                    if (!$invoiceNum) {
                        $invoiceNum = $tBill->invoice_number;
                    }
                    $this->inventoryService->reverseStockForBill($tBill, 'POS cart cleared by cashier');
                    $itemIds = $tBill->items()->pluck('id');
                    if ($itemIds->isNotEmpty()) {
                        BillItemAddon::whereIn('bill_item_id', $itemIds)->delete();
                        $tBill->items()->delete();
                    }
                    $tBill->payments()->delete();
                    $tBill->forceDelete();
                }

                $table = RestaurantTable::find($tableId);
                if ($table) {
                    $table->update(['status' => 'available']);
                    $releasedTable = $table;
                }
            }

            // 2. If specific billId provided and not already deleted
            if ($billId) {
                $bill = Bill::find($billId);
                if ($bill) {
                    $invoiceNum = $invoiceNum ?: $bill->invoice_number;
                    $this->inventoryService->reverseStockForBill($bill, 'POS cart cleared by cashier');

                    if ($bill->table_id) {
                        RestaurantTable::where('id', $bill->table_id)->update(['status' => 'available']);
                        if (!$releasedTable) {
                            $releasedTable = RestaurantTable::find($bill->table_id);
                        }
                    }

                    if (in_array($bill->status, ['draft', 'held', 'pending'])) {
                        $itemIds = $bill->items()->pluck('id');
                        if ($itemIds->isNotEmpty()) {
                            BillItemAddon::whereIn('bill_item_id', $itemIds)->delete();
                            $bill->items()->delete();
                        }
                        $bill->payments()->delete();
                        $bill->forceDelete();
                    } else {
                        $bill->update([
                            'status' => 'cancelled',
                            'cancellation_reason' => 'Cart cleared by cashier',
                            'cancelled_by' => Auth::id(),
                            'cancelled_at' => now(),
                        ]);
                    }
                }
            }

            if ($invoiceNum) {
                AuditLog::log(
                    action: 'bill_cleared',
                    module: 'pos',
                    referenceId: (string) ($billId ?: $invoiceNum),
                    description: "Order #{$invoiceNum} cleared and removed from active POS by " . (Auth::user()?->name ?? 'Cashier')
                );
            }

            return response()->json([
                'success' => true,
                'message' => $invoiceNum 
                    ? "Order #{$invoiceNum} cleared from database and table released." 
                    : "Cart cleared and table set to available.",
                'table_id' => $tableId,
                'table_number' => $releasedTable ? $releasedTable->table_number : null,
            ]);
        });
    }

    /**
     * Split Bill into separate bill.
     */
    public function splitBill(Request $request, Bill $bill): JsonResponse
    {
        $request->validate([
            'split_items' => ['required', 'array', 'min:1'],
            'split_items.*.item_id' => ['required', 'integer'],
            'split_items.*.quantity' => ['required', 'numeric', 'min:1'],
        ]);

        if (in_array($bill->status, ['completed', 'cancelled'])) {
            return response()->json(['success' => false, 'message' => 'Cannot split completed or cancelled bill.'], 422);
        }

        return DB::transaction(function () use ($bill, $request) {
            $bill->loadMissing('items.addons');

            $newBillCart = [];
            $remainingCart = [];

            $splitMap = [];
            foreach ($request->split_items as $si) {
                $splitMap[$si['item_id']] = (float) $si['quantity'];
            }

            foreach ($bill->items as $item) {
                $splitQty = $splitMap[$item->id] ?? 0;
                $currentQty = (float) $item->quantity;

                if ($splitQty > $currentQty) {
                    return response()->json([
                        'success' => false,
                        'message' => "Split quantity ({$splitQty}) for {$item->item_name} exceeds existing quantity ({$currentQty})."
                    ], 422);
                }

                $addons = $item->addons->pluck('add_on_id')->toArray();

                if ($splitQty > 0) {
                    $newBillCart[] = [
                        'item_type' => $item->item_type,
                        'item_id' => $item->item_id,
                        'quantity' => $splitQty,
                        'notes' => $item->notes,
                        'addons' => $addons,
                    ];
                }

                $remQty = $currentQty - $splitQty;
                if ($remQty > 0) {
                    $remainingCart[] = [
                        'item_type' => $item->item_type,
                        'item_id' => $item->item_id,
                        'quantity' => $remQty,
                        'notes' => $item->notes,
                        'addons' => $addons,
                    ];
                }
            }

            if (empty($newBillCart)) {
                return response()->json(['success' => false, 'message' => 'No items selected to split.'], 422);
            }

            // Calculate new bill
            $newCalc = $this->calculationService->calculate($newBillCart, 'fixed', 0);
            $newInvoiceNum = $this->invoiceNumberService->generate();

            $newBill = Bill::create([
                'invoice_number' => $newInvoiceNum,
                'order_type' => $bill->order_type,
                'table_id' => $bill->table_id,
                'cashier_id' => Auth::id(),
                'status' => $bill->status,
                'customer_name' => $bill->customer_name,
                'customer_phone' => $bill->customer_phone,
                'subtotal' => $newCalc['subtotal'],
                'discount_type' => 'fixed',
                'discount_value' => 0,
                'discount_amount' => 0,
                'tax_total' => $newCalc['tax_total'],
                'cgst_total' => $newCalc['cgst_total'],
                'sgst_total' => $newCalc['sgst_total'],
                'igst_total' => $newCalc['igst_total'],
                'rounding_difference' => $newCalc['rounding_difference'],
                'grand_total' => $newCalc['grand_total'],
                'parent_bill_id' => $bill->id,
            ]);

            foreach ($newCalc['items'] as $itemData) {
                $bItem = BillItem::create([
                    'bill_id' => $newBill->id,
                    'item_type' => $itemData['item_type'],
                    'item_id' => $itemData['item_id'],
                    'food_code' => $itemData['food_code'],
                    'item_name' => $itemData['item_name'],
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $itemData['subtotal'],
                    'tax_rate' => $itemData['tax_rate'],
                    'tax_amount' => $itemData['tax_amount'],
                    'cgst_amount' => $itemData['cgst_amount'],
                    'sgst_amount' => $itemData['sgst_amount'],
                    'igst_amount' => $itemData['igst_amount'],
                    'total' => $itemData['total'],
                ]);
            }

            // Update remaining original bill
            if (!empty($remainingCart)) {
                $remCalc = $this->calculationService->calculate($remainingCart, $bill->discount_type, (float) $bill->discount_value);
                $bill->update([
                    'subtotal' => $remCalc['subtotal'],
                    'discount_amount' => $remCalc['discount_amount'],
                    'tax_total' => $remCalc['tax_total'],
                    'cgst_total' => $remCalc['cgst_total'],
                    'sgst_total' => $remCalc['sgst_total'],
                    'rounding_difference' => $remCalc['rounding_difference'],
                    'grand_total' => $remCalc['grand_total'],
                ]);

                $bill->items()->delete();
                foreach ($remCalc['items'] as $itemData) {
                    BillItem::create([
                        'bill_id' => $bill->id,
                        'item_type' => $itemData['item_type'],
                        'item_id' => $itemData['item_id'],
                        'food_code' => $itemData['food_code'],
                        'item_name' => $itemData['item_name'],
                        'unit_price' => $itemData['unit_price'],
                        'quantity' => $itemData['quantity'],
                        'subtotal' => $itemData['subtotal'],
                        'tax_rate' => $itemData['tax_rate'],
                        'tax_amount' => $itemData['tax_amount'],
                        'cgst_amount' => $itemData['cgst_amount'],
                        'sgst_amount' => $itemData['sgst_amount'],
                        'igst_amount' => $itemData['igst_amount'],
                        'total' => $itemData['total'],
                    ]);
                }
            } else {
                // All items moved to new bill
                $bill->update(['status' => 'cancelled', 'cancellation_reason' => "All items split into Bill #{$newBill->invoice_number}"]);
            }

            AuditLog::log(
                action: 'bill_split',
                module: 'pos',
                referenceId: (string) $bill->id,
                description: "Bill #{$bill->invoice_number} split into new Bill #{$newBill->invoice_number}"
            );

            return response()->json([
                'success' => true,
                'message' => "Bill split successfully into #{$newBill->invoice_number}.",
                'new_bill_id' => $newBill->id,
            ]);
        });
    }

    /**
     * Resume Bill details into cart.
     */
    public function resumeBill(Bill $bill): JsonResponse
    {
        $bill->loadMissing(['items.addons', 'table']);

        return response()->json([
            'success' => true,
            'bill' => [
                'id' => $bill->id,
                'invoice_number' => $bill->invoice_number,
                'order_type' => $bill->order_type,
                'table_id' => $bill->table_id,
                'waiter_id' => $bill->waiter_id,
                'waiter_name' => $bill->waiter_name,
                'customer_name' => $bill->customer_name,
                'customer_phone' => $bill->customer_phone,
                'discount_type' => $bill->discount_type,
                'discount_value' => $bill->discount_value,
                'notes' => $bill->notes,
                'items' => $bill->items->map(function ($item) {
                    return [
                        'item_type' => $item->item_type,
                        'item_id' => $item->item_id,
                        'code' => $item->food_code,
                        'name' => $item->item_name,
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => (float) $item->quantity,
                        'tax_rate' => (float) $item->tax_rate,
                        'notes' => $item->notes,
                        'addons' => $item->addons->pluck('add_on_id')->toArray(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Generate KOT (Kitchen Order Ticket) with delta/unprinted items only.
     */
    public function generateKot(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bill_id' => ['nullable', 'exists:bills,id'],
            'table_id' => ['required', 'exists:restaurant_tables,id'],
            'waiter_id' => ['nullable', 'exists:users,id'],
            'waiter_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:food,combo'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
            'items.*.addons' => ['nullable', 'array'],
        ]);

        return DB::transaction(function () use ($validated) {
            $table = RestaurantTable::findOrFail($validated['table_id']);

            // 1. Calculate bill totals
            $calculation = $this->calculationService->calculate($validated['items']);
            if (empty($calculation['items'])) {
                return response()->json(['success' => false, 'message' => 'No valid items found to generate KOT.'], 422);
            }

            // 2. Find or create active bill for this table
            $bill = null;
            if (!empty($validated['bill_id'])) {
                $bill = Bill::lockForUpdate()->find($validated['bill_id']);
            }
            if (!$bill) {
                $bill = Bill::where('table_id', $table->id)
                    ->whereIn('status', ['pending', 'held', 'draft'])
                    ->latest()
                    ->lockForUpdate()
                    ->first();
            }

            if (!$bill) {
                $invoiceNum = $this->invoiceNumberService->generate();
                $bill = Bill::create([
                    'invoice_number' => $invoiceNum,
                    'order_type' => 'table',
                    'table_id' => $table->id,
                    'cashier_id' => Auth::id(),
                    'waiter_id' => $validated['waiter_id'] ?? null,
                    'waiter_name' => $validated['waiter_name'] ?? null,
                    'status' => 'pending',
                    'subtotal' => $calculation['subtotal'],
                    'discount_type' => 'fixed',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'tax_total' => $calculation['tax_total'],
                    'cgst_total' => $calculation['cgst_total'],
                    'sgst_total' => $calculation['sgst_total'],
                    'igst_total' => $calculation['igst_total'],
                    'rounding_difference' => $calculation['rounding_difference'],
                    'grand_total' => $calculation['grand_total'],
                    'notes' => $validated['notes'] ?? null,
                    'kot_count' => 0,
                ]);
            } else {
                $bill->update([
                    'order_type' => 'table',
                    'table_id' => $table->id,
                    'waiter_id' => $validated['waiter_id'] ?? $bill->waiter_id,
                    'waiter_name' => $validated['waiter_name'] ?? $bill->waiter_name,
                    'status' => 'pending',
                    'subtotal' => $calculation['subtotal'],
                    'tax_total' => $calculation['tax_total'],
                    'cgst_total' => $calculation['cgst_total'],
                    'sgst_total' => $calculation['sgst_total'],
                    'rounding_difference' => $calculation['rounding_difference'],
                    'grand_total' => $calculation['grand_total'],
                    'notes' => $validated['notes'] ?? $bill->notes,
                ]);
            }

            // 3. Compare existing items to find delta for KOT
            $existingItems = $bill->items()->get();
            $printedQtyMap = [];
            foreach ($existingItems as $ei) {
                $key = "{$ei->item_type}_{$ei->item_id}_" . trim((string)$ei->notes);
                $printedQtyMap[$key] = ($printedQtyMap[$key] ?? 0) + (float) $ei->kot_printed_qty;
            }

            $kotItemsToPrint = [];

            // Delete and re-create bill items with updated kot_printed_qty
            $bill->items()->delete();

            foreach ($calculation['items'] as $itemData) {
                $key = "{$itemData['item_type']}_{$itemData['item_id']}_" . trim((string)($itemData['notes'] ?? ''));
                $prevPrinted = $printedQtyMap[$key] ?? 0;
                $currentQty = (float) $itemData['quantity'];
                $deltaQty = max(0, $currentQty - $prevPrinted);

                if ($deltaQty > 0) {
                    $kotItemsToPrint[] = [
                        'item_type' => $itemData['item_type'],
                        'item_id' => $itemData['item_id'],
                        'name' => $itemData['item_name'],
                        'quantity' => $deltaQty,
                        'notes' => $itemData['notes'] ?? null,
                        'addons' => $itemData['addons'] ?? [],
                    ];
                }

                $billItem = BillItem::create([
                    'bill_id' => $bill->id,
                    'item_type' => $itemData['item_type'],
                    'item_id' => $itemData['item_id'],
                    'food_code' => $itemData['food_code'],
                    'hsn_code' => $itemData['hsn_code'] ?? null,
                    'item_name' => $itemData['item_name'],
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $currentQty,
                    'kot_printed_qty' => $currentQty, // newly printed
                    'subtotal' => $itemData['subtotal'],
                    'tax_rate' => $itemData['tax_rate'],
                    'tax_amount' => $itemData['tax_amount'],
                    'cgst_amount' => $itemData['cgst_amount'],
                    'sgst_amount' => $itemData['sgst_amount'],
                    'igst_amount' => $itemData['igst_amount'],
                    'total' => $itemData['total'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if (!empty($itemData['addons'])) {
                    foreach ($itemData['addons'] as $addon) {
                        BillItemAddon::create([
                            'bill_item_id' => $billItem->id,
                            'add_on_id' => $addon['add_on_id'],
                            'name' => $addon['name'],
                            'price' => $addon['price'],
                            'tax_rate' => $addon['tax_rate'],
                            'tax_amount' => $addon['tax_amount'],
                            'total' => $addon['total'],
                        ]);
                    }
                }
            }

            // Ensure table is occupied
            if ($table->status !== 'occupied') {
                $table->update(['status' => 'occupied']);
            }

            if (empty($kotItemsToPrint)) {
                return response()->json([
                    'success' => false,
                    'is_all_printed' => true,
                    'message' => "All items for Table {$table->table_number} are already printed on KOT. No newly added items to print.",
                    'bill_id' => $bill->id,
                    'invoice_number' => $bill->invoice_number,
                    'kot_number' => $bill->kot_count,
                    'kot_print_url' => route('pos.kot.print', ['bill' => $bill->id, 'kot' => $bill->kot_count, 'reprint' => 1]),
                ]);
            }

            $bill->increment('kot_count');

            // Store KOT print batch in cache for printing
            $kotBatchData = [
                'kot_number' => $bill->kot_count,
                'bill_id' => $bill->id,
                'invoice_number' => $bill->invoice_number,
                'table_number' => $table->table_number,
                'table_name' => $table->name,
                'waiter' => $bill->waiter_name ?: ($bill->waiter?->name ?? 'Staff'),
                'order_notes' => $bill->notes,
                'date' => date('d/m/Y'),
                'time' => date('h:i A'),
                'printer_type' => Setting::get('printer_type', '80mm'),
                'items' => $kotItemsToPrint,
                'total_quantity' => array_sum(array_column($kotItemsToPrint, 'quantity')),
                'is_reprint' => false,
            ];

            cache()->put("kot_batch_{$bill->id}_{$bill->kot_count}", $kotBatchData, now()->addHours(12));

            AuditLog::log(
                action: 'kot_printed',
                module: 'pos',
                referenceId: (string) $bill->id,
                description: "KOT #{$bill->kot_count} printed for Table {$table->table_number} (" . count($kotItemsToPrint) . " new items)"
            );

            // Hardware Kitchen Printer Routing
            $kitchenProfiles = PrinterProfileService::getKitchenPrinters();
            $directPrintStatus = [];
            foreach ($kitchenProfiles as $kProf) {
                if (($kProf['connection_type'] ?? '') === 'network' && !empty($kProf['ip_address'])) {
                    $escpos = PrinterProfileService::generateKotEscpos($kotBatchData, $kProf);
                    $res = PrinterProfileService::printRawEscpos($kProf['ip_address'], (int) ($kProf['port'] ?? 9100), $escpos);
                    $directPrintStatus[] = [
                        'printer' => $kProf['name'],
                        'success' => $res['success'],
                        'message' => $res['message'],
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "KOT #{$bill->kot_count} generated with " . count($kotItemsToPrint) . " new item(s) for Table {$table->table_number}!",
                'bill_id' => $bill->id,
                'invoice_number' => $bill->invoice_number,
                'kot_number' => $bill->kot_count,
                'new_items_count' => count($kotItemsToPrint),
                'kot_print_url' => route('pos.kot.print', ['bill' => $bill->id, 'kot' => $bill->kot_count]),
                'kitchen_printers' => $kitchenProfiles,
                'direct_prints' => $directPrintStatus,
            ]);
        });
    }

    /**
     * Render KOT thermal print view.
     */
    public function printKot(Bill $bill, Request $request): View
    {
        $kotNum = $request->input('kot', $bill->kot_count);
        $isReprint = $request->boolean('reprint');
        $cacheKey = "kot_batch_{$bill->id}_{$kotNum}";
        $kotData = cache()->get($cacheKey);

        if (!$kotData || $isReprint) {
            $bill->loadMissing(['table', 'waiter', 'items.addons']);
            $kotData = [
                'kot_number' => $kotNum ?: ($bill->kot_count ?: 1),
                'bill_id' => $bill->id,
                'invoice_number' => $bill->invoice_number,
                'table_number' => $bill->table?->table_number ?? 'Table',
                'table_name' => $bill->table?->name,
                'waiter' => $bill->waiter_name ?: ($bill->waiter?->name ?? 'Staff'),
                'order_notes' => $bill->notes,
                'date' => $bill->created_at->format('d/m/Y'),
                'time' => now()->format('h:i A'),
                'printer_type' => Setting::get('printer_type', '80mm'),
                'items' => $bill->items->map(fn($i) => [
                    'name' => $i->item_name,
                    'quantity' => (float) $i->quantity,
                    'notes' => $i->notes,
                    'addons' => $i->addons->map(fn($a) => ['name' => $a->name])->toArray(),
                ])->toArray(),
                'total_quantity' => (float) $bill->items->sum('quantity'),
                'is_reprint' => true,
            ];
        }

        return view('print.kot', compact('kotData'));
    }
}
