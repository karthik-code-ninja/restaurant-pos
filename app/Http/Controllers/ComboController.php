<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Food;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComboController extends Controller
{
    public function index(Request $request): View
    {
        $query = Combo::with('foods');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', (bool) $request->status);
        }

        $combos = $query->orderBy('name')->paginate(15)->withQueryString();
        $foods = Food::active()->orderBy('name')->get();
        $currency = Setting::get('currency_symbol', '₹');

        return view('combos.index', compact('combos', 'foods', 'currency'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:combos,code'],
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.food_id' => ['required', 'exists:foods,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $combo = Combo::create($validated);

        foreach ($request->items as $item) {
            ComboItem::create([
                'combo_id' => $combo->id,
                'food_id' => $item['food_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        AuditLog::log(
            action: 'combo_created',
            module: 'food',
            referenceId: (string) $combo->id,
            description: "Combo '{$combo->name}' ({$combo->code}) created at ₹{$combo->price}"
        );

        return redirect()->route('combos.index')->with('success', 'Combo created successfully!');
    }

    public function update(Request $request, Combo $combo): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('combos', 'code')->ignore($combo->id)],
            'name' => ['required', 'string', 'max:150'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.food_id' => ['required', 'exists:foods,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:1'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $oldValues = $combo->only(['code', 'name', 'price', 'tax_rate', 'is_active']);
        $combo->update($validated);

        $combo->items()->delete();
        foreach ($request->items as $item) {
            ComboItem::create([
                'combo_id' => $combo->id,
                'food_id' => $item['food_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        AuditLog::log(
            action: 'combo_updated',
            module: 'food',
            referenceId: (string) $combo->id,
            oldValues: $oldValues,
            newValues: $validated,
            description: "Combo '{$combo->name}' ({$combo->code}) updated"
        );

        return redirect()->route('combos.index')->with('success', 'Combo updated successfully!');
    }

    public function destroy(Combo $combo): RedirectResponse
    {
        $name = $combo->name;
        $combo->delete();

        AuditLog::log(
            action: 'combo_deleted',
            module: 'food',
            referenceId: (string) $combo->id,
            description: "Combo '{$name}' deleted"
        );

        return redirect()->route('combos.index')->with('success', 'Combo deleted successfully!');
    }

    public function toggleStatus(Combo $combo): RedirectResponse
    {
        $combo->update(['is_active' => !$combo->is_active]);

        $statusStr = $combo->is_active ? 'activated' : 'deactivated';

        AuditLog::log(
            action: 'combo_status_changed',
            module: 'food',
            referenceId: (string) $combo->id,
            description: "Combo '{$combo->name}' was {$statusStr}"
        );

        return back()->with('success', "Combo {$statusStr} successfully!");
    }
}
