<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Food;
use App\Models\InventoryItem;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FoodController extends Controller
{
    public function index(Request $request): View
    {
        $query = Food::with(['category', 'ingredients']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('is_veg') && $request->is_veg !== '') {
            $query->where('is_veg', (bool) $request->is_veg);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', (bool) $request->status);
        }

        $foods = $query->orderBy('name')->paginate(15)->withQueryString();
        $categories = Category::active()->orderBy('name')->get();
        $inventoryItems = InventoryItem::active()->orderBy('name')->get();
        $currency = Setting::get('currency_symbol', '₹');

        return view('foods.index', compact('foods', 'categories', 'inventoryItems', 'currency'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:50', 'unique:foods,code'],
            'hsn_code' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_veg' => ['required', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.id' => ['required_with:ingredients', 'exists:inventory_items,id'],
            'ingredients.*.quantity' => ['required_with:ingredients', 'numeric', 'min:0.001'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_veg'] = $request->boolean('is_veg', true);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('foods', 'public');
            $validated['image'] = $path;
        }

        $food = Food::create($validated);

        // Sync ingredients if provided
        if (!empty($request->ingredients)) {
            $syncData = [];
            foreach ($request->ingredients as $ing) {
                if (!empty($ing['id']) && !empty($ing['quantity'])) {
                    $syncData[$ing['id']] = ['quantity' => $ing['quantity']];
                }
            }
            $food->ingredients()->sync($syncData);
        }

        AuditLog::log(
            action: 'food_created',
            module: 'food',
            referenceId: (string) $food->id,
            description: "Food item '{$food->name}' ({$food->code}) created with price ₹{$food->price}"
        );

        return redirect()->route('foods.index')->with('success', 'Food item created successfully!');
    }

    public function update(Request $request, Food $food): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('foods', 'code')->ignore($food->id)],
            'hsn_code' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_veg' => ['required', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'ingredients' => ['nullable', 'array'],
            'ingredients.*.id' => ['required_with:ingredients', 'exists:inventory_items,id'],
            'ingredients.*.quantity' => ['required_with:ingredients', 'numeric', 'min:0.001'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_veg'] = $request->boolean('is_veg');

        if ($request->hasFile('image')) {
            if ($food->image && Storage::disk('public')->exists($food->image)) {
                Storage::disk('public')->delete($food->image);
            }
            $path = $request->file('image')->store('foods', 'public');
            $validated['image'] = $path;
        }

        $oldValues = $food->only(['code', 'name', 'price', 'tax_rate', 'is_veg', 'is_active']);
        $food->update($validated);

        // Sync ingredients
        $syncData = [];
        if (!empty($request->ingredients)) {
            foreach ($request->ingredients as $ing) {
                if (!empty($ing['id']) && !empty($ing['quantity'])) {
                    $syncData[$ing['id']] = ['quantity' => $ing['quantity']];
                }
            }
        }
        $food->ingredients()->sync($syncData);

        AuditLog::log(
            action: 'food_updated',
            module: 'food',
            referenceId: (string) $food->id,
            oldValues: $oldValues,
            newValues: $validated,
            description: "Food item '{$food->name}' ({$food->code}) updated"
        );

        return redirect()->route('foods.index')->with('success', 'Food item updated successfully!');
    }

    public function destroy(Food $food): RedirectResponse
    {
        $name = $food->name;
        $food->delete();

        AuditLog::log(
            action: 'food_deleted',
            module: 'food',
            referenceId: (string) $food->id,
            description: "Food item '{$name}' deleted"
        );

        return redirect()->route('foods.index')->with('success', 'Food item deleted successfully!');
    }

    public function toggleStatus(Food $food): RedirectResponse
    {
        $food->update(['is_active' => !$food->is_active]);

        $statusStr = $food->is_active ? 'activated' : 'deactivated';

        AuditLog::log(
            action: 'food_status_changed',
            module: 'food',
            referenceId: (string) $food->id,
            description: "Food item '{$food->name}' was {$statusStr}"
        );

        return back()->with('success', "Food item {$statusStr} successfully!");
    }
}
