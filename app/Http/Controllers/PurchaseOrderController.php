<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status');
        $search = $request->input('search');

        $query = PurchaseOrder::with(['supplier', 'items']);

        if (! empty($status)) {
            $query->where('status', $status);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        $purchaseOrders = $query->orderByDesc('po_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $currency = Setting::get('currency_symbol', '₹');
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $foods = Food::where('is_active', true)->orderBy('name')->get();
        $inventoryItems = InventoryItem::orderBy('name')->get();
        $nextPoNumber = PurchaseOrder::generatePoNumber();

        return view('purchase_orders.index', compact(
            'purchaseOrders',
            'currency',
            'status',
            'search',
            'suppliers',
            'foods',
            'inventoryItems',
            'nextPoNumber'
        ));
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'po_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'required_without:supplier_id|nullable|string|max:150',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:food,inventory',
            'items.*.item_id' => 'nullable|integer',
            'items.*.item_name' => 'required|string|max:150',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:20',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $supplierName = $validated['supplier_name'] ?? '';
            $supplier = null;
            if (! empty($validated['supplier_id'])) {
                $supplier = Supplier::find($validated['supplier_id']);
                $supplierName = $supplier ? $supplier->name : $supplierName;
            }

            $total = 0;
            $itemsData = [];
            foreach ($validated['items'] as $item) {
                $lineTotal = (float) $item['quantity'] * (float) $item['unit_price'];
                $total += $lineTotal;

                $itemsData[] = [
                    'item_type' => $item['item_type'],
                    'item_id' => $item['item_id'] ?? null,
                    'item_name' => $item['item_name'],
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $lineTotal,
                ];
            }

            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'po_date' => $validated['po_date'],
                'expected_date' => $validated['expected_date'] ?? null,
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplierName,
                'total_amount' => $total,
                'status' => 'sent',
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsData as $iData) {
                $po->items()->create($iData);
            }

            DB::commit();

            return redirect()->route('purchase-orders.index')->with('success', "Purchase Order {$po->po_number} created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Error creating PO: '.$e->getMessage());
        }
    }

    /**
     * Show the specified purchase order.
     */
    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'items', 'creator']);
        $currency = Setting::get('currency_symbol', '₹');
        $restaurantName = Setting::get('restaurant_name', 'RestroPOS');

        return view('purchase_orders.show', compact('purchaseOrder', 'currency', 'restaurantName'));
    }

    /**
     * Convert Purchase Order into a live Purchase Bill.
     */
    public function convertToPurchase(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $purchaseNo = Purchase::generatePurchaseNo();

            $purchase = Purchase::create([
                'purchase_no' => $purchaseNo,
                'invoice_no' => "FROM-{$purchaseOrder->po_number}",
                'purchase_date' => now()->toDateString(),
                'supplier_id' => $purchaseOrder->supplier_id,
                'supplier_name' => $purchaseOrder->supplier_name,
                'subtotal' => $purchaseOrder->total_amount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'grand_total' => $purchaseOrder->total_amount,
                'paid_amount' => 0,
                'due_amount' => $purchaseOrder->total_amount,
                'payment_status' => 'unpaid',
                'payment_method' => 'credit',
                'notes' => "Converted from Purchase Order {$purchaseOrder->po_number}",
                'created_by' => auth()->id(),
            ]);

            foreach ($purchaseOrder->items as $item) {
                $purchase->items()->create([
                    'item_type' => $item->item_type,
                    'item_id' => $item->item_id,
                    'item_name' => $item->item_name,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'tax_percent' => 0,
                    'tax_amount' => 0,
                    'total_amount' => $item->total_amount,
                ]);

                if ($item->item_type === 'food' && $item->item_id) {
                    $food = Food::find($item->item_id);
                    $food?->increment('stock', $item->quantity);
                } elseif ($item->item_type === 'inventory' && $item->item_id) {
                    $inv = InventoryItem::find($item->item_id);
                    $inv?->increment('current_stock', $item->quantity);
                }
            }

            if ($purchaseOrder->supplier) {
                $purchaseOrder->supplier->increment('current_balance', $purchase->grand_total);
            }

            $purchaseOrder->update(['status' => 'received']);
            DB::commit();

            return redirect()->route('purchases.show', $purchase)->with('success', "PO converted to Purchase Bill {$purchaseNo} successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->with('error', 'Error converting PO: '.$e->getMessage());
        }
    }
}
