<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $query = Expense::with(['category', 'user'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $expenses = $query->latest('date')->latest('id')->paginate(15)->withQueryString();
        $categories = ExpenseCategory::orderBy('name')->get();

        // Totals
        $totalExpense = (float) Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $cashExpense = (float) Expense::whereBetween('date', [$startDate, $endDate])->where('payment_method', 'cash')->sum('amount');
        $currency = Setting::get('currency_symbol', '₹');

        return view('expenses.index', compact(
            'expenses',
            'categories',
            'startDate',
            'endDate',
            'totalExpense',
            'cashExpense',
            'currency'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,upi,card,bank_transfer'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['user_id'] = Auth::id();

        $expense = Expense::create($validated);

        AuditLog::log(
            action: 'expense_created',
            module: 'expense',
            referenceId: (string) $expense->id,
            description: "Expense ₹{$expense->amount} added under {$expense->category?->name} ({$expense->payment_method})"
        );

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully!');
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,upi,card,bank_transfer'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $old = $expense->only(['expense_category_id', 'amount', 'date', 'payment_method', 'description']);
        $expense->update($validated);

        AuditLog::log(
            action: 'expense_updated',
            module: 'expense',
            referenceId: (string) $expense->id,
            oldValues: $old,
            newValues: $validated,
            description: "Expense #{$expense->id} updated"
        );

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully!');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $amt = $expense->amount;
        $cat = $expense->category?->name;
        $expense->delete();

        AuditLog::log(
            action: 'expense_deleted',
            module: 'expense',
            referenceId: (string) $expense->id,
            description: "Expense of ₹{$amt} under '{$cat}' deleted"
        );

        return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully!');
    }

    // Category Management
    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $category = ExpenseCategory::create($validated);

        AuditLog::log(
            action: 'expense_category_created',
            module: 'expense',
            referenceId: (string) $category->id,
            description: "Expense Category '{$category->name}' created"
        );

        return back()->with('success', 'Expense category created successfully!');
    }

    public function destroyCategory(ExpenseCategory $category): RedirectResponse
    {
        if ($category->expenses()->count() > 0) {
            return back()->with('error', 'Cannot delete expense category with recorded expenses.');
        }

        $name = $category->name;
        $category->delete();

        AuditLog::log(
            action: 'expense_category_deleted',
            module: 'expense',
            referenceId: (string) $category->id,
            description: "Expense Category '{$name}' deleted"
        );

        return back()->with('success', 'Expense category deleted!');
    }
}
