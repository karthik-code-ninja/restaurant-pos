<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Setting;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function index(Request $request): View
    {
        $query = InventoryItem::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if ($request->has('low_stock') && $request->boolean('low_stock')) {
            $query->whereColumn('current_stock', '<=', 'min_stock_alert');
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();
        $lowStockCount = InventoryItem::whereColumn('current_stock', '<=', 'min_stock_alert')->count();
        $totalItems = InventoryItem::count();

        return view('inventory.index', compact('items', 'lowStockCount', 'totalItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:inventory_items,code'],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:50'],
            'opening_stock' => ['required', 'numeric', 'min:0'],
            'min_stock_alert' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['current_stock'] = $validated['opening_stock'];

        $item = InventoryItem::create($validated);

        // Record opening stock movement
        if ($item->opening_stock > 0) {
            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => 'opening',
                'quantity' => $item->opening_stock,
                'previous_stock' => 0,
                'current_stock' => $item->opening_stock,
                'notes' => 'Opening stock setup',
                'user_id' => auth()->id(),
            ]);
        }

        AuditLog::log(
            action: 'inventory_item_created',
            module: 'inventory',
            referenceId: (string) $item->id,
            description: "Raw material '{$item->name}' ({$item->code}) created with opening stock {$item->opening_stock} {$item->unit}"
        );

        return redirect()->route('inventory.index')->with('success', 'Stock item created successfully!');
    }

    public function update(Request $request, InventoryItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('inventory_items', 'code')->ignore($item->id)],
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['required', 'string', 'max:50'],
            'min_stock_alert' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $old = $item->only(['code', 'name', 'unit', 'min_stock_alert', 'is_active']);
        $item->update($validated);

        AuditLog::log(
            action: 'inventory_item_updated',
            module: 'inventory',
            referenceId: (string) $item->id,
            oldValues: $old,
            newValues: $validated,
            description: "Stock item '{$item->name}' updated"
        );

        return redirect()->route('inventory.index')->with('success', 'Stock item updated successfully!');
    }

    public function adjust(Request $request, InventoryItem $item): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:adjustment_add,adjustment_reduce'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $this->inventoryService->adjustStock($item->id, (float) $validated['quantity'], $validated['type'], $validated['notes'] ?? null);

        return back()->with('success', "Stock for {$item->name} adjusted successfully!");
    }

    public function history(Request $request, InventoryItem $item): View
    {
        $query = $item->movements()->with('user');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->latest()->paginate(20)->withQueryString();

        return view('inventory.history', compact('item', 'movements'));
    }

    public function destroy(InventoryItem $item): RedirectResponse
    {
        if ($item->foods()->count() > 0) {
            return back()->with('error', "Cannot delete raw material that is mapped to food recipes. Remove ingredient mappings first.");
        }

        $name = $item->name;
        $item->delete();

        AuditLog::log(
            action: 'inventory_item_deleted',
            module: 'inventory',
            referenceId: (string) $item->id,
            description: "Stock item '{$name}' deleted"
        );

        return redirect()->route('inventory.index')->with('success', 'Stock item deleted successfully!');
    }
}
