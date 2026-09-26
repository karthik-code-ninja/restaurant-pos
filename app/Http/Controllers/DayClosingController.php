<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DayClosing;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DayClosingController extends Controller
{
    public function index(): View
    {
        // Find current open session (if any)
        $currentSession = DayClosing::with(['openedByUser', 'closedByUser'])
            ->where('status', 'open')
            ->latest()
            ->first();

        $sessionStart = $currentSession ? $currentSession->opened_at : Carbon::today()->startOfDay();

        // Calculate live figures for current open session
        $cashSales = (float) Payment::whereHas('bill', function ($q) use ($sessionStart) {
            $q->where('status', 'completed')->where('created_at', '>=', $sessionStart);
        })->where('payment_method', 'cash')->sum('amount');

        $cashExpenses = (float) Expense::where('payment_method', 'cash')
            ->where('created_at', '>=', $sessionStart)
            ->sum('amount');

        $openingCash = $currentSession ? (float) $currentSession->opening_cash : 0.0;
        $cashWithdrawals = $currentSession ? (float) $currentSession->cash_withdrawals : 0.0;
        $expectedCash = $openingCash + $cashSales - $cashExpenses - $cashWithdrawals;

        // Past closed sessions
        $closedSessions = DayClosing::with(['openedByUser', 'closedByUser'])
            ->where('status', 'closed')
            ->latest('closed_at')
            ->paginate(15);

        $currency = Setting::get('currency_symbol', '₹');

        return view('dayclosing.index', compact(
            'currentSession',
            'openingCash',
            'cashSales',
            'cashExpenses',
            'cashWithdrawals',
            'expectedCash',
            'closedSessions',
            'currency'
        ));
    }

    /**
     * Start/Open a new Day / Shift.
     */
    public function open(Request $request): RedirectResponse
    {
        $existingOpen = DayClosing::where('status', 'open')->first();
        if ($existingOpen) {
            return back()->with('error', 'A day/shift session is already open. Close it first before opening a new one.');
        }

        $validated = $request->validate([
            'shift_type' => ['required', 'in:day,shift'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $session = DayClosing::create([
            'user_id' => Auth::id(),
            'shift_type' => $validated['shift_type'],
            'opened_at' => now(),
            'opening_cash' => $validated['opening_cash'],
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::log(
            action: 'day_opened',
            module: 'day_closing',
            referenceId: (string) $session->id,
            description: "Session opened with ₹{$session->opening_cash} opening cash ({$session->shift_type})"
        );

        return back()->with('success', 'Day / Shift opened successfully!');
    }

    /**
     * Record cash withdrawal during shift.
     */
    public function recordWithdrawal(Request $request, DayClosing $dayClosing): RedirectResponse
    {
        if ($dayClosing->status !== 'open') {
            return back()->with('error', 'Cannot record withdrawal on a closed session.');
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $dayClosing->increment('cash_withdrawals', $validated['amount']);

        AuditLog::log(
            action: 'cash_withdrawn',
            module: 'day_closing',
            referenceId: (string) $dayClosing->id,
            description: "Cash withdrawal of ₹{$validated['amount']} recorded. Notes: " . ($validated['notes'] ?? 'None')
        );

        return back()->with('success', 'Cash withdrawal recorded successfully!');
    }

    /**
     * Close the Day / Shift session.
     */
    public function close(Request $request, DayClosing $dayClosing): RedirectResponse
    {
        if ($dayClosing->status !== 'open') {
            return back()->with('error', 'This session is already closed.');
        }

        $validated = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($dayClosing, $validated) {
            $sessionStart = $dayClosing->opened_at;

            $cashSales = (float) Payment::whereHas('bill', function ($q) use ($sessionStart) {
                $q->where('status', 'completed')->where('created_at', '>=', $sessionStart);
            })->where('payment_method', 'cash')->sum('amount');

            $cashExpenses = (float) Expense::where('payment_method', 'cash')
                ->where('created_at', '>=', $sessionStart)
                ->sum('amount');

            $openingCash = (float) $dayClosing->opening_cash;
            $cashWithdrawals = (float) $dayClosing->cash_withdrawals;
            $expectedCash = round($openingCash + $cashSales - $cashExpenses - $cashWithdrawals, 2);
            $actualCash = round((float) $validated['actual_cash'], 2);
            $difference = round($actualCash - $expectedCash, 2);

            $dayClosing->update([
                'cash_sales' => $cashSales,
                'cash_expenses' => $cashExpenses,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => Auth::id(),
                'notes' => $validated['notes'] ?? $dayClosing->notes,
            ]);

            AuditLog::log(
                action: 'day_closed',
                module: 'day_closing',
                referenceId: (string) $dayClosing->id,
                description: "Day closed: Expected ₹{$expectedCash}, Actual ₹{$actualCash}, Difference ₹{$difference}"
            );

            return back()->with('success', "Day / Shift closed successfully! Difference: ₹{$difference}");
        });
    }
}
