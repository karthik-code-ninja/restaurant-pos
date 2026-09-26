<?php

namespace App\Http\Controllers;

use App\Models\AddOn;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AddOnController extends Controller
{
    public function index(Request $request): View
    {
        $query = AddOn::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', (bool) $request->status);
        }

        $addons = $query->orderBy('name')->paginate(15)->withQueryString();
        $currency = Setting::get('currency_symbol', '₹');

        return view('addons.index', compact('addons', 'currency'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $addon = AddOn::create($validated);

        AuditLog::log(
            action: 'addon_created',
            module: 'food',
            referenceId: (string) $addon->id,
            description: "Add-on '{$addon->name}' created at ₹{$addon->price}"
        );

        return redirect()->route('addons.index')->with('success', 'Add-on created successfully!');
    }

    public function update(Request $request, AddOn $addon): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $oldValues = $addon->only(['name', 'price', 'tax_rate', 'is_active']);
        $addon->update($validated);

        AuditLog::log(
            action: 'addon_updated',
            module: 'food',
            referenceId: (string) $addon->id,
            oldValues: $oldValues,
            newValues: $validated,
            description: "Add-on '{$addon->name}' updated"
        );

        return redirect()->route('addons.index')->with('success', 'Add-on updated successfully!');
    }

    public function destroy(AddOn $addon): RedirectResponse
    {
        $name = $addon->name;
        $addon->delete();

        AuditLog::log(
            action: 'addon_deleted',
            module: 'food',
            referenceId: (string) $addon->id,
            description: "Add-on '{$name}' deleted"
        );

        return redirect()->route('addons.index')->with('success', 'Add-on deleted successfully!');
    }

    public function toggleStatus(AddOn $addon): RedirectResponse
    {
        $addon->update(['is_active' => !$addon->is_active]);

        $statusStr = $addon->is_active ? 'activated' : 'deactivated';

        AuditLog::log(
            action: 'addon_status_changed',
            module: 'food',
            referenceId: (string) $addon->id,
            description: "Add-on '{$addon->name}' was {$statusStr}"
        );

        return back()->with('success', "Add-on {$statusStr} successfully!");
    }
}
