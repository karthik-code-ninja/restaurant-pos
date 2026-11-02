<?php

namespace App\Http\Controllers;

use App\Models\AccountingVoucher;
use App\Models\Food;
use App\Models\InventoryItem;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Setting;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseReturnController extends Controller
{
    /**
     * Display a listing of purchase returns.
     */
    public function index(Request $request): View
    {
        $search = $request->input('search');

        $query = PurchaseReturn::with(['supplier', 'purchase', 'items']);

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('return_no', 'like', "%{$search}%")
                    ->orWhere('supplier_name', 'like', "%{$search}%");
            });
        }

        $purchaseReturns = $query->orderByDesc('return_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $currency = Setting::get('currency_symbol', '₹');
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $purchases = Purchase::latest()->take(30)->get();
        $foods = Food::orderBy('name')->get();
        $nextReturnNo = PurchaseReturn::generateReturnNo();

        return view('purchase_returns.index', compact(
            'purchaseReturns',
            'currency',
            'search',
            'suppliers',
            'purchases',
            'foods',
            'nextReturnNo'
        ));
    }

    /**
     * Store a newly created purchase return in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'return_date' => 'required|date',
            'purchase_id' => 'nullable|exists:purchases,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'supplier_name' => 'required_without:supplier_id|nullable|string|max:150',
            'refund_status' => 'required|in:adjusted,refunded,pending',
            'reason' => 'nullable|string',
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

            $returnNo = PurchaseReturn::generateReturnNo();

            $pr = PurchaseReturn::create([
                'return_no' => $returnNo,
                'return_date' => $validated['return_date'],
                'purchase_id' => $validated['purchase_id'] ?? null,
                'supplier_id' => $supplier?->id,
                'supplier_name' => $supplierName,
                'total_amount' => $total,
                'refund_status' => $validated['refund_status'],
                'reason' => $validated['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($itemsData as $iData) {
                $pr->items()->create($iData);

                // Reduce stock
                if ($iData['item_type'] === 'food' && $iData['item_id']) {
                    $food = Food::find($iData['item_id']);
                    if ($food && $food->stock >= $iData['quantity']) {
                        $food->decrement('stock', $iData['quantity']);
                    }
                } elseif ($iData['item_type'] === 'inventory' && $iData['item_id']) {
                    $inv = InventoryItem::find($iData['item_id']);
                    if ($inv && $inv->current_stock >= $iData['quantity']) {
                        $inv->decrement('current_stock', $iData['quantity']);
                    }
                }
            }

            // Adjust supplier payable balance
            if ($supplier && $total > 0 && $validated['refund_status'] === 'adjusted') {
                $supplier->decrement('current_balance', min($supplier->current_balance, $total));
            }

            // Record accounting voucher
            AccountingVoucher::create([
                'voucher_no' => AccountingVoucher::generateVoucherNo('purchase_return'),
                'voucher_type' => 'purchase_return',
                'voucher_date' => $validated['return_date'],
                'party_type' => 'supplier',
                'party_id' => $supplier?->id,
                'party_name' => $supplierName,
                'debit_ledger' => 'Sundry Creditors',
                'credit_ledger' => 'Purchase Return Account',
                'amount' => $total,
                'payment_method' => 'credit_note',
                'reference_no' => $returnNo,
                'narration' => "Debit note for goods returned to {$supplierName}",
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('purchase-returns.show', $pr)->with('success', "Debit Note {$returnNo} created successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Error creating purchase return: '.$e->getMessage());
        }
    }

    /**
     * Show the specified purchase return / debit note.
     */
    public function show(PurchaseReturn $purchaseReturn): View
    {
        $purchaseReturn->load(['supplier', 'purchase', 'items', 'creator']);
        $currency = Setting::get('currency_symbol', '₹');
        $restaurantName = Setting::get('restaurant_name', 'RestroPOS');

        return view('purchase_returns.show', compact('purchaseReturn', 'currency', 'restaurantName'));
    }
}
