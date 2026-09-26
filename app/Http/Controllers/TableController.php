<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TableController extends Controller
{
    public function index(Request $request): View
    {
        $query = RestaurantTable::with('activeBill');

        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }

        if ($request->filled('section')) {
            $query->where('section', $request->section);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $tables = $query->orderBy('floor')->orderBy('table_number')->get();
        $floors = RestaurantTable::select('floor')->distinct()->pluck('floor');
        $sections = RestaurantTable::select('section')->distinct()->pluck('section');

        return view('tables.index', compact('tables', 'floors', 'sections'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:20', 'unique:restaurant_tables,table_number'],
            'name' => ['nullable', 'string', 'max:100'],
            'floor' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', 'in:available,occupied,reserved'],
        ]);

        $table = RestaurantTable::create($validated);

        AuditLog::log(
            action: 'table_created',
            module: 'table',
            referenceId: (string) $table->id,
            description: "Table '{$table->table_number}' ({$table->floor} - {$table->section}) created"
        );

        return redirect()->route('tables.index')->with('success', 'Table created successfully!');
    }

    public function update(Request $request, RestaurantTable $table): RedirectResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:20', Rule::unique('restaurant_tables', 'table_number')->ignore($table->id)],
            'name' => ['nullable', 'string', 'max:100'],
            'floor' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:50'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'status' => ['required', 'in:available,occupied,reserved'],
        ]);

        // If table has active pending/held bill, don't allow setting to available manually
        if ($validated['status'] === 'available' && $table->activeBill) {
            return back()->with('error', "Table {$table->table_number} has an active bill #{$table->activeBill->invoice_number}. Settle or cancel the bill first.");
        }

        $old = $table->only(['table_number', 'name', 'floor', 'section', 'capacity', 'status']);
        $table->update($validated);

        AuditLog::log(
            action: 'table_updated',
            module: 'table',
            referenceId: (string) $table->id,
            oldValues: $old,
            newValues: $validated,
            description: "Table '{$table->table_number}' updated"
        );

        return redirect()->route('tables.index')->with('success', 'Table updated successfully!');
    }

    public function destroy(RestaurantTable $table): RedirectResponse
    {
        if ($table->activeBill) {
            return back()->with('error', "Cannot delete table with active bill #{$table->activeBill->invoice_number}.");
        }

        $num = $table->table_number;
        $table->delete();

        AuditLog::log(
            action: 'table_deleted',
            module: 'table',
            referenceId: (string) $table->id,
            description: "Table '{$num}' deleted"
        );

        return redirect()->route('tables.index')->with('success', 'Table deleted successfully!');
    }

    /**
     * Change status directly (e.g. Reserve or Mark Available).
     */
    public function updateStatus(Request $request, RestaurantTable $table): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:available,occupied,reserved'],
        ]);

        if ($validated['status'] === 'available' && $table->activeBill) {
            $msg = "Cannot set to available while active bill exists.";
            return $request->expectsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
        }

        $prevStatus = $table->status;
        $table->update(['status' => $validated['status']]);

        AuditLog::log(
            action: 'table_status_changed',
            module: 'table',
            referenceId: (string) $table->id,
            description: "Table '{$table->table_number}' status changed from {$prevStatus} to {$validated['status']}"
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Status updated successfully!']);
        }

        return back()->with('success', 'Table status updated!');
    }

    /**
     * Transfer table (Move active bill to another table).
     */
    public function transfer(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'from_table_id' => ['required', 'exists:restaurant_tables,id'],
            'to_table_id' => ['required', 'exists:restaurant_tables,id', 'different:from_table_id'],
        ]);

        return DB::transaction(function () use ($request) {
            $fromTable = RestaurantTable::findOrFail($request->from_table_id);
            $toTable = RestaurantTable::findOrFail($request->to_table_id);

            $activeBill = Bill::where('table_id', $fromTable->id)
                ->whereIn('status', ['pending', 'held'])
                ->latest()
                ->first();

            if (!$activeBill) {
                $msg = "No active bill found on table {$fromTable->table_number} to transfer.";
                return $request->expectsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
            }

            if ($toTable->status === 'occupied' && $toTable->activeBill) {
                $msg = "Destination table {$toTable->table_number} is already occupied with an active bill.";
                return $request->expectsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
            }

            // Transfer bill
            $activeBill->update(['table_id' => $toTable->id]);

            // Update table statuses
            $fromTable->update(['status' => 'available']);
            $toTable->update(['status' => 'occupied']);

            AuditLog::log(
                action: 'table_transferred',
                module: 'table',
                referenceId: (string) $activeBill->id,
                description: "Bill #{$activeBill->invoice_number} moved from Table {$fromTable->table_number} to Table {$toTable->table_number}"
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Successfully transferred bill from Table {$fromTable->table_number} to Table {$toTable->table_number}."
                ]);
            }

            return back()->with('success', "Transferred bill to Table {$toTable->table_number} successfully!");
        });
    }

    /**
     * Merge active bills from two tables.
     */
    public function merge(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'source_table_id' => ['required', 'exists:restaurant_tables,id'],
            'target_table_id' => ['required', 'exists:restaurant_tables,id', 'different:source_table_id'],
        ]);

        return DB::transaction(function () use ($request) {
            $sourceTable = RestaurantTable::findOrFail($request->source_table_id);
            $targetTable = RestaurantTable::findOrFail($request->target_table_id);

            $sourceBill = Bill::where('table_id', $sourceTable->id)->whereIn('status', ['pending', 'held'])->latest()->first();
            $targetBill = Bill::where('table_id', $targetTable->id)->whereIn('status', ['pending', 'held'])->latest()->first();

            if (!$sourceBill || !$targetBill) {
                $msg = "Both tables must have an active bill to merge.";
                return $request->expectsJson() ? response()->json(['success' => false, 'message' => $msg], 422) : back()->with('error', $msg);
            }

            // Move items from source to target
            foreach ($sourceBill->items as $item) {
                $item->update(['bill_id' => $targetBill->id]);
            }

            // Recalculate target bill
            $calcService = app(\App\Services\BillCalculationService::class);
            $cartItems = [];
            foreach ($targetBill->fresh()->items as $item) {
                $addons = $item->addons->pluck('add_on_id')->toArray();
                $cartItems[] = [
                    'item_type' => $item->item_type,
                    'item_id' => $item->item_id,
                    'quantity' => $item->quantity,
                    'notes' => $item->notes,
                    'addons' => $addons,
                ];
            }

            $calc = $calcService->calculate($cartItems, $targetBill->discount_type, (float) $targetBill->discount_value);

            $targetBill->update([
                'subtotal' => $calc['subtotal'],
                'discount_amount' => $calc['discount_amount'],
                'tax_total' => $calc['tax_total'],
                'cgst_total' => $calc['cgst_total'],
                'sgst_total' => $calc['sgst_total'],
                'rounding_difference' => $calc['rounding_difference'],
                'grand_total' => $calc['grand_total'],
            ]);

            // Cancel source bill with merge note
            $sourceBill->update([
                'status' => 'cancelled',
                'cancellation_reason' => "Merged into Bill #{$targetBill->invoice_number} (Table {$targetTable->table_number})",
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
            ]);

            $sourceTable->update(['status' => 'available']);

            AuditLog::log(
                action: 'tables_merged',
                module: 'table',
                referenceId: (string) $targetBill->id,
                description: "Table {$sourceTable->table_number} (Bill #{$sourceBill->invoice_number}) merged into Table {$targetTable->table_number} (Bill #{$targetBill->invoice_number})"
            );

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => "Tables merged successfully into Table {$targetTable->table_number}."]);
            }

            return back()->with('success', "Tables merged successfully into Table {$targetTable->table_number}!");
        });
    }
}
