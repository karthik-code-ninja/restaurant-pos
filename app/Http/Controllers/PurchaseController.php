<?php

namespace App\Http\Controllers;

use App\Models\AccountingVoucher;
use App\Models\Food;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    /**
     * Display a listing of purchases (Purchase Register).
     */
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $supplierId = $request->input('supplier_id');
        $search = $request->input('search');

        $query = Purchase::with(['supplier', 'items'])
            ->whereBetween('purchase_date', [$startDate, $endDate]);

        if (! empty($supplierId)) {
            $query->where('supplier_id', $supplierId);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('purchase_no', 'like', "%{$search}%")
                    ->orWhere('invoice_no', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        $purchases = $query->orderByDesc('purchase_date')->orderByDesc('id')->paginate(15)->withQueryString();

        // Totals
        $totalsQuery = Purchase::whereBetween('purchase_date', [$startDate, $endDate]);
        if (! empty($supplierId)) {
            $totalsQuery->where('supplier_id', $supplierId);
        }
        $totalPurchasesAmount = (float) $totalsQuery->sum('grand_total');
        $totalTaxAmount = (float) $totalsQuery->sum('tax_amount');
        $totalPaidAmount = (float) $totalsQuery->sum('paid_amount');
        $totalDueAmount = (float) $totalsQuery->sum('due_amount');

        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $currency = Setting::get('currency_symbol', '₹');

        return view('purchases.index', compact(
            'purchases',
            'suppliers',
            'startDate',
            'endDate',
            'supplierId',
            'search',
            'totalPurchasesAmount',
            'totalTaxAmount',
            'totalPaidAmount',
            'totalDueAmount',
            'currency'
        ));
    }

    /**
     * Show the form for creating a new purchase.
     */
    public function create(): View
    {
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $foods = Food::where('is_active', true)->orderBy('name')->get();
        $inventoryItems = InventoryItem::orderBy('name')->get();
        $nextPurchaseNo = Purchase::generatePurchaseNo();
        $currency = Setting::get('currency_symbol', '₹');

        return view('purchases.create', compact('suppliers', 'foods', 'inventoryItems', 'nextPurchaseNo', 'currency'));
    }

    /**
     * Store a newly created purchase in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'purchase_date' => 'required|date',
            'invoice_no' => 'nullable|string|max:100',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'required_without:supplier_id|nullable|string|max:150',
            'payment_status' => 'required|in:paid,partial,unpaid',
            'payment_method' => 'required|string|max:50',
            'paid_amount' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:food,inventory',
            'items.*.item_id' => 'nullable|integer',
            'items.*.item_name' => 'required|string|max:150',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $supplierName = $validated['supplier_name'] ?? '';
            $supplier = null;
            if (! empty($validated['supplier_id'])) {
                $supplier = Supplier::find($validated['supplier_id']);
                $supplierName = $supplier ? $supplier->name : $supplierName;
            }

            // Calculate item totals
            $subtotal = 0;
            $totalTax = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $taxPct = (float) ($item['tax_percent'] ?? 0);

                $itemSubtotal = $qty * $price;
                $itemTax = ($itemSubtotal * $taxPct) / 100;
                $itemTotal = $itemSubtotal + $itemTax;

                $subtotal += $itemSubtotal;
                $totalTax += $itemTax;

                $itemsData[] = [
                    'item_type' => $item['item_type'],
                    'item_id' => $item['item_id'] ?? null,
                    'item_name' => $item['item_name'],
                    'quantity' => $qty,
                    'unit' => $item['unit'],
                    'unit_price' => $price,
                    'tax_percent' => $taxPct,
                    'tax_amount' => $itemTax,
                    'total_amount' => $itemTotal,
                ];
            }

            $discount = (float) ($validated['discount_amount'] ?? 0);
            $grandTotal = max(0, ($subtotal + $totalTax) - $discount);
            $paidAmount = min($grandTotal, (float) $validated['paid_amount']);
            $dueAmount = max(0, $grandTotal - $paidAmount);

            $purchaseNo = Purchase::generatePurchaseNo();

            $purchase = Purchase::create([
                'purchase_no' => $purchaseNo,
                'invoice_no' => $validated['invoice_no'] ?? null,
                'purchase_date' => $validated['purchase_date'],
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplierName,
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'discount_amount' => $discount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'payment_status' => $validated['payment_status'],
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsData as $iData) {
                $purchase->items()->create($iData);

                // Update stock
                if ($iData['item_type'] === 'food' && $iData['item_id']) {
                    $food = Food::find($iData['item_id']);
                    if ($food) {
                        $food->increment('stock', $iData['quantity']);
                        if ($iData['unit_price'] > 0) {
                            $food->update(['cost_price' => $iData['unit_price']]);
                        }
                    }
                } elseif ($iData['item_type'] === 'inventory' && $iData['item_id']) {
                    $inv = InventoryItem::find($iData['item_id']);
                    if ($inv) {
                        $inv->increment('current_stock', $iData['quantity']);
                        InventoryMovement::create([
                            'inventory_item_id' => $inv->id,
                            'type' => 'in',
                            'quantity' => $iData['quantity'],
                            'reason' => "Purchase #{$purchaseNo}",
                            'user_id' => auth()->id(),
                        ]);
                    }
                }
            }

            // Update Supplier Current Balance if credit/due
            if ($supplier && $dueAmount > 0) {
                $supplier->increment('current_balance', $dueAmount);
            }

            // Record in Accounting Voucher Register
            AccountingVoucher::create([
                'voucher_no' => AccountingVoucher::generateVoucherNo('purchase'),
                'voucher_type' => 'purchase',
                'voucher_date' => $validated['purchase_date'],
                'party_type' => 'supplier',
                'party_id' => $supplier?->id,
                'party_name' => $supplierName,
                'debit_ledger' => 'Purchase Account',
                'credit_ledger' => $paidAmount >= $grandTotal ? 'Cash/Bank Account' : 'Sundry Creditors',
                'amount' => $grandTotal,
                'payment_method' => $validated['payment_method'],
                'reference_no' => $purchaseNo,
                'narration' => "Purchase from {$supplierName} Inv #".($validated['invoice_no'] ?? $purchaseNo),
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('purchases.show', $purchase)->with('success', "Purchase invoice {$purchaseNo} created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Error recording purchase: '.$e->getMessage());
        }
    }

    /**
     * Display the specified purchase invoice.
     */
    public function show(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'items', 'creator']);
        $currency = Setting::get('currency_symbol', '₹');
        $restaurantName = Setting::get('restaurant_name', 'RestroPOS');

        return view('purchases.show', compact('purchase', 'currency', 'restaurantName'));
    }

    /**
     * Remove the specified purchase from storage.
     */
    public function destroy(Purchase $purchase): RedirectResponse
    {
        DB::beginTransaction();
        try {
            // Revert stock
            foreach ($purchase->items as $item) {
                if ($item->item_type === 'food' && $item->item_id) {
                    $food = Food::find($item->item_id);
                    if ($food && $food->stock >= $item->quantity) {
                        $food->decrement('stock', $item->quantity);
                    }
                } elseif ($item->item_type === 'inventory' && $item->item_id) {
                    $inv = InventoryItem::find($item->item_id);
                    if ($inv && $inv->current_stock >= $item->quantity) {
                        $inv->decrement('current_stock', $item->quantity);
                    }
                }
            }

            // Revert supplier balance
            if ($purchase->supplier && $purchase->due_amount > 0) {
                $purchase->supplier->decrement('current_balance', min($purchase->supplier->current_balance, $purchase->due_amount));
            }

            $purchase->delete();
            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Purchase invoice deleted and stock reverted.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Error cancelling purchase: '.$e->getMessage());
        }
    }
}
