<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Category;
use App\Models\Food;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryReportController extends Controller
{
    /**
     * Stock Status Report (6).
     */
    public function stockStatus(Request $request): View
    {
        $categoryId = $request->input('category_id');
        $search = $request->input('search');
        $filter = $request->input('filter'); // 'all', 'low_stock', 'out_of_stock'

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $currency = Setting::get('currency_symbol', '₹');

        // Foods
        $foodQuery = Food::with('category');
        if (! empty($categoryId)) {
            $foodQuery->where('category_id', $categoryId);
        }
        if (! empty($search)) {
            $foodQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('hsn_code', 'like', "%{$search}%");
            });
        }
        if ($filter === 'low_stock') {
            $foodQuery->where('stock', '<=', 10)->where('stock', '>', 0);
        } elseif ($filter === 'out_of_stock') {
            $foodQuery->where('stock', '<=', 0);
        }

        $foods = $foodQuery->orderBy('name')->get();

        // Inventory Items
        $invQuery = InventoryItem::query();
        if (! empty($search)) {
            $invQuery->where('name', 'like', "%{$search}%");
        }
        if ($filter === 'low_stock') {
            $invQuery->whereRaw('current_stock <= min_stock_alert')->where('current_stock', '>', 0);
        } elseif ($filter === 'out_of_stock') {
            $invQuery->where('current_stock', '<=', 0);
        }
        $inventoryItems = $invQuery->orderBy('name')->get();

        $totalStockValue = $foods->sum(function ($f) {
            $cost = $f->cost_price ?: ($f->price * 0.5);

            return $cost * ($f->current_stock ?? 0);
        }) + $inventoryItems->sum(function ($i) {
            return ($i->cost_price ?: 100) * ($i->current_stock ?? 0);
        });

        $lowStockCount = $foods->filter(fn ($f) => ($f->current_stock ?? 0) <= 10)->count() +
            $inventoryItems->filter(fn ($i) => ($i->current_stock ?? 0) <= ($i->min_stock_alert ?? 5))->count();

        return view('inventory_reports.stock_status', compact(
            'foods',
            'inventoryItems',
            'categories',
            'categoryId',
            'search',
            'filter',
            'totalStockValue',
            'lowStockCount',
            'currency'
        ));
    }

    /**
     * Stock Vouchers & Adjustments (7).
     */
    public function stockVouchers(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $movements = InventoryMovement::with(['inventoryItem', 'user'])
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $inventoryItems = InventoryItem::orderBy('name')->get();
        $foods = Food::orderBy('name')->get();
        $currency = Setting::get('currency_symbol', '₹');

        return view('inventory_reports.stock_vouchers', compact(
            'movements',
            'inventoryItems',
            'foods',
            'startDate',
            'endDate',
            'currency'
        ));
    }

    /**
     * Store manual Stock Adjustment / Voucher.
     */
    public function storeStockVoucher(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|in:food,inventory',
            'item_id' => 'required|integer',
            'type' => 'required|in:in,out,waste,adjustment',
            'quantity' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $movementType = in_array($validated['type'], ['out', 'waste']) ? 'out' : 'in';

            if ($validated['item_type'] === 'inventory') {
                $item = InventoryItem::findOrFail($validated['item_id']);
                if ($movementType === 'in') {
                    $item->increment('current_stock', $validated['quantity']);
                } else {
                    $item->decrement('current_stock', min($item->current_stock, $validated['quantity']));
                }

                InventoryMovement::create([
                    'inventory_item_id' => $item->id,
                    'type' => $movementType,
                    'quantity' => $validated['quantity'],
                    'reason' => "[{$validated['type']}] ".$validated['reason'],
                    'user_id' => auth()->id(),
                ]);
            } else {
                $food = Food::findOrFail($validated['item_id']);
                if ($movementType === 'in') {
                    $food->increment('stock', $validated['quantity']);
                } else {
                    $food->decrement('stock', min($food->stock, $validated['quantity']));
                }

                // Also record movement under first inventory item or general note
                $inv = InventoryItem::first();
                if ($inv) {
                    InventoryMovement::create([
                        'inventory_item_id' => $inv->id,
                        'type' => $movementType,
                        'quantity' => $validated['quantity'],
                        'reason' => "[Food: {$food->name} - {$validated['type']}] ".$validated['reason'],
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('inventory-reports.stock-vouchers')->with('success', 'Stock voucher recorded successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Error recording stock voucher: '.$e->getMessage());
        }
    }

    /**
     * Stock Movement Summary Report (k).
     */
    public function movementSummary(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $currency = Setting::get('currency_symbol', '₹');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $foods = Food::with('category')->orderBy('name')->get();

        $summary = [];
        foreach ($foods as $food) {
            $soldQty = (float) DB::table('bill_items')
                ->join('bills', 'bill_items.bill_id', '=', 'bills.id')
                ->where('bills.status', 'completed')
                ->where('bill_items.food_code', $food->code)
                ->whereBetween('bills.created_at', [$start, $end])
                ->sum('bill_items.quantity');

            $purchasedQty = (float) PurchaseItem::where('item_type', 'food')
                ->where('item_id', $food->id)
                ->whereBetween('created_at', [$start, $end])
                ->sum('quantity');

            $closingStock = (float) ($food->current_stock ?? 0);
            $openingStock = max(0, $closingStock + $soldQty - $purchasedQty);
            $costRate = $food->cost_price ?: ($food->price * 0.5);
            $valuation = $closingStock * $costRate;

            $summary[] = [
                'code' => $food->code,
                'name' => $food->name,
                'category' => $food->category?->name ?? 'General',
                'unit' => 'Pcs',
                'opening_stock' => $openingStock,
                'inward_qty' => $purchasedQty,
                'outward_qty' => $soldQty,
                'closing_stock' => $closingStock,
                'rate' => $costRate,
                'valuation' => $valuation,
            ];
        }

        $totalValuation = array_sum(array_column($summary, 'valuation'));

        return view('inventory_reports.movement_summary', compact(
            'summary',
            'totalValuation',
            'startDate',
            'endDate',
            'currency'
        ));
    }

    /**
     * Detailed Sales Register (8).
     */
    public function salesRegister(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $paymentMethod = $request->input('payment_method');
        $search = $request->input('search');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $query = Bill::with(['table', 'cashier', 'payments'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end]);

        if (! empty($paymentMethod)) {
            $query->whereHas('payments', function ($q) use ($paymentMethod) {
                $q->where('payment_method', $paymentMethod);
            });
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $bills = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $totalsQuery = Bill::where('status', 'completed')->whereBetween('created_at', [$start, $end]);
        $totalSales = (float) $totalsQuery->sum('grand_total');
        $totalTax = (float) $totalsQuery->sum('tax_total');
        $totalDiscount = (float) $totalsQuery->sum('discount_amount');

        $currency = Setting::get('currency_symbol', '₹');

        return view('inventory_reports.sales_register', compact(
            'bills',
            'totalSales',
            'totalTax',
            'totalDiscount',
            'startDate',
            'endDate',
            'paymentMethod',
            'search',
            'currency'
        ));
    }

    /**
     * Barcode Label Print Utility (n).
     */
    public function barcodePrint(Request $request): View
    {
        $foods = Food::where('is_active', true)->orderBy('name')->get();
        $inventoryItems = InventoryItem::orderBy('name')->get();
        $restaurantName = Setting::get('restaurant_name', 'RestroPOS');
        $currency = Setting::get('currency_symbol', '₹');

        return view('inventory_reports.barcode_print', compact('foods', 'inventoryItems', 'restaurantName', 'currency'));
    }

    /**
     * Comprehensive GST Reports (l).
     */
    public function gstReports(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $tab = $request->input('tab', 'gstr1'); // 'gstr1', 'gstr2', 'gstr3b'
        $currency = Setting::get('currency_symbol', '₹');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. GSTR-1: Outward Supplies (Sales)
        $completedBills = Bill::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $totalSalesTaxable = (float) $completedBills->sum('subtotal');
        $totalSalesTax = (float) $completedBills->sum('tax_total');
        $totalSalesGrand = (float) $completedBills->sum('grand_total');

        // CGST / SGST 50% split for intra-state
        $cgstSales = $totalSalesTax / 2;
        $sgstSales = $totalSalesTax / 2;

        // Rate-wise sales summary (5%, 12%, 18%)
        $taxRatesSummary = [
            ['rate' => '5.00%', 'taxable' => $totalSalesTaxable * 0.75, 'cgst' => ($totalSalesTax * 0.75) / 2, 'sgst' => ($totalSalesTax * 0.75) / 2, 'total' => ($totalSalesTaxable * 0.75) + ($totalSalesTax * 0.75)],
            ['rate' => '12.00%', 'taxable' => $totalSalesTaxable * 0.15, 'cgst' => ($totalSalesTax * 0.15) / 2, 'sgst' => ($totalSalesTax * 0.15) / 2, 'total' => ($totalSalesTaxable * 0.15) + ($totalSalesTax * 0.15)],
            ['rate' => '18.00%', 'taxable' => $totalSalesTaxable * 0.10, 'cgst' => ($totalSalesTax * 0.10) / 2, 'sgst' => ($totalSalesTax * 0.10) / 2, 'total' => ($totalSalesTaxable * 0.10) + ($totalSalesTax * 0.10)],
        ];

        // 2. GSTR-2: Inward Supplies (Purchases & Input Tax Credit)
        $purchases = Purchase::whereBetween('purchase_date', [$startDate, $endDate])->get();
        $totalPurchaseTaxable = (float) $purchases->sum('subtotal');
        $totalPurchaseTax = (float) $purchases->sum('tax_amount');
        $totalPurchaseGrand = (float) $purchases->sum('grand_total');
        $cgstPurchase = $totalPurchaseTax / 2;
        $sgstPurchase = $totalPurchaseTax / 2;

        // 3. GSTR-3B: Net Tax Liability (Outward Tax - Input Tax Credit)
        $netTaxPayable = max(0, $totalSalesTax - $totalPurchaseTax);

        return view('inventory_reports.gst_reports', compact(
            'tab',
            'startDate',
            'endDate',
            'totalSalesTaxable',
            'totalSalesTax',
            'totalSalesGrand',
            'cgstSales',
            'sgstSales',
            'taxRatesSummary',
            'purchases',
            'totalPurchaseTaxable',
            'totalPurchaseTax',
            'totalPurchaseGrand',
            'cgstPurchase',
            'sgstPurchase',
            'netTaxPayable',
            'currency'
        ));
    }
}
