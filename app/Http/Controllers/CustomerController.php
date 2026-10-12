<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Setting;
use App\Services\CustomerLedgerService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerLedgerService $ledgerService
    ) {}

    /**
     * Display listing of customers with balances and statistics.
     */
    public function index(Request $request): View
    {
        $query = Customer::query();

        // Search filter (name, phone, email, gstin)
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status') === 'active');
        }

        // Balance status filter
        $balanceStatus = $request->input('balance_status', 'all');
        if ($balanceStatus === 'due') {
            $query->where('balance', '>', 0.001);
        } elseif ($balanceStatus === 'advance') {
            $query->where('balance', '<', -0.001);
        } elseif ($balanceStatus === 'settled') {
            $query->whereBetween('balance', [-0.001, 0.001]);
        }

        // Sort
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');
        if (in_array($sort, ['name', 'balance', 'created_at'])) {
            $query->orderBy($sort, $direction === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        $customers = $query->paginate(20)->withQueryString();

        // High-level statistics
        $totalCustomers = Customer::count();
        $totalDue = (float) Customer::where('balance', '>', 0.001)->sum('balance');
        $totalAdvance = (float) abs(Customer::where('balance', '<', -0.001)->sum('balance'));
        $dueCustomersCount = Customer::where('balance', '>', 0.001)->count();
        $currency = Setting::get('currency_symbol', '₹');

        return view('customers.index', compact(
            'customers',
            'totalCustomers',
            'totalDue',
            'totalAdvance',
            'dueCustomersCount',
            'currency',
            'balanceStatus'
        ));
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_type' => ['nullable', 'in:due,advance'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($validated) {
            $openingBalance = (float) ($validated['opening_balance'] ?? 0);
            $type = $validated['opening_balance_type'] ?? 'due';

            $initialBalance = 0.00;
            if ($openingBalance > 0) {
                $initialBalance = $type === 'due' ? $openingBalance : -$openingBalance;
            }

            $customer = Customer::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'gstin' => $validated['gstin'] ?? null,
                'credit_limit' => (float) ($validated['credit_limit'] ?? 0),
                'balance' => $initialBalance,
                'status' => true,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($openingBalance > 0) {
                CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'entry_date' => now(),
                    'type' => $type === 'due' ? 'debit' : 'credit',
                    'amount' => $openingBalance,
                    'balance_after' => $initialBalance,
                    'payment_mode' => 'opening_balance',
                    'description' => 'Opening balance ('.($type === 'due' ? 'Due' : 'Advance deposit').')',
                    'created_by' => Auth::id(),
                ]);
            }

            AuditLog::log(
                action: 'customer_created',
                module: 'customers',
                referenceId: (string) $customer->id,
                newValues: $customer->toArray(),
                description: "Created customer '{$customer->name}' with opening balance ₹{$initialBalance}"
            );
        });

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    /**
     * Update customer details.
     */
    public function update(Customer $customer, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'gstin' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldValues = $customer->toArray();

        $customer->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'gstin' => $validated['gstin'] ?? null,
            'credit_limit' => (float) ($validated['credit_limit'] ?? 0),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::log(
            action: 'customer_updated',
            module: 'customers',
            referenceId: (string) $customer->id,
            oldValues: $oldValues,
            newValues: $customer->toArray(),
            description: "Updated customer '{$customer->name}' profile details"
        );

        return redirect()->back()->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove / Soft delete customer.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        if (abs((float) $customer->balance) > 0.01) {
            return redirect()->back()->with('error', "Cannot delete customer '{$customer->name}' with unsettled balance of ₹".number_format($customer->balance, 2));
        }

        $customer->delete();

        AuditLog::log(
            action: 'customer_deleted',
            module: 'customers',
            referenceId: (string) $customer->id,
            description: "Deleted customer '{$customer->name}'"
        );

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    /**
     * API for Fast Customer Search (used in POS and search inputs).
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->input('query', ''));

        $query = Customer::active();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        $customers = $query->orderBy('name')
            ->limit(20)
            ->get()
            ->map(function ($c) {
                $bal = (float) $c->balance;

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone ?: '',
                    'email' => $c->email ?: '',
                    'balance' => $bal,
                    'credit_limit' => (float) $c->credit_limit,
                    'is_due' => $bal > 0.001,
                    'is_advance' => $bal < -0.001,
                    'formatted_balance' => $c->formatted_balance,
                ];
            });

        return response()->json([
            'success' => true,
            'customers' => $customers,
        ]);
    }

    /**
     * Quick create customer from POS billing screen modal.
     */
    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'opening_balance_type' => ['nullable', 'in:due,advance'],
        ]);

        $openingBalance = (float) ($validated['opening_balance'] ?? 0);
        $type = $validated['opening_balance_type'] ?? 'due';

        $initialBalance = 0.00;
        if ($openingBalance > 0) {
            $initialBalance = $type === 'due' ? $openingBalance : -$openingBalance;
        }

        $customer = Customer::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'credit_limit' => (float) ($validated['credit_limit'] ?? 0),
            'balance' => $initialBalance,
            'status' => true,
        ]);

        if ($openingBalance > 0) {
            CustomerLedger::create([
                'customer_id' => $customer->id,
                'entry_date' => now(),
                'type' => $type === 'due' ? 'debit' : 'credit',
                'amount' => $openingBalance,
                'balance_after' => $initialBalance,
                'payment_mode' => 'opening_balance',
                'description' => 'Opening balance ('.($type === 'due' ? 'Due' : 'Advance deposit').')',
                'created_by' => Auth::id(),
            ]);
        }

        AuditLog::log(
            action: 'customer_quick_created',
            module: 'pos',
            referenceId: (string) $customer->id,
            description: "Quick added customer '{$customer->name}' from POS"
        );

        return response()->json([
            'success' => true,
            'message' => 'Customer created and selected successfully.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone ?: '',
                'email' => $customer->email ?: '',
                'balance' => (float) $customer->balance,
                'credit_limit' => (float) $customer->credit_limit,
                'is_due' => $customer->isDue(),
                'is_advance' => $customer->isAdvance(),
                'formatted_balance' => $customer->formatted_balance,
            ],
        ]);
    }

    /**
     * View Detailed Customer Ledger statement and transaction history.
     */
    public function ledger(Customer $customer, Request $request): View
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = CustomerLedger::with(['bill', 'createdByUser'])
            ->where('customer_id', $customer->id);

        if ($startDate) {
            $query->where('entry_date', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('entry_date', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $ledgers = $query->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate opening balance before startDate if date filter is active
        $openingBalance = 0.00;
        if ($startDate) {
            $priorDebits = (float) CustomerLedger::where('customer_id', $customer->id)
                ->where('entry_date', '<', Carbon::parse($startDate)->startOfDay())
                ->where('type', 'debit')
                ->sum('amount');

            $priorCredits = (float) CustomerLedger::where('customer_id', $customer->id)
                ->where('entry_date', '<', Carbon::parse($startDate)->startOfDay())
                ->where('type', 'credit')
                ->sum('amount');

            $openingBalance = round($priorDebits - $priorCredits, 2);
        }

        $periodDebits = $ledgers->where('type', 'debit')->sum('amount');
        $periodCredits = $ledgers->where('type', 'credit')->sum('amount');
        $closingBalance = $startDate
            ? round($openingBalance + $periodDebits - $periodCredits, 2)
            : (float) $customer->balance;

        $currency = Setting::get('currency_symbol', '₹');

        return view('customers.ledger', compact(
            'customer',
            'ledgers',
            'startDate',
            'endDate',
            'openingBalance',
            'periodDebits',
            'periodCredits',
            'closingBalance',
            'currency'
        ));
    }

    /**
     * Record a payment (credit) received from customer settling due balance.
     */
    public function recordLedgerPayment(Customer $customer, Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('customers.ledger', $customer);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode' => ['required', 'in:cash,upi,card,bank_transfer,cheque,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $ledger = $this->ledgerService->recordPayment(
            customer: $customer,
            amount: (float) $validated['amount'],
            paymentMode: $validated['payment_mode'],
            reference: $validated['reference_number'] ?? null,
            description: $validated['description'] ?? null,
            userId: Auth::id()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment of ₹'.number_format($ledger->amount, 2).' recorded successfully.',
                'new_balance' => (float) $customer->fresh()->balance,
            ]);
        }

        return redirect()->route('customers.ledger', $customer)->with('success', 'Payment of ₹'.number_format($ledger->amount, 2).' received and credited successfully.');
    }

    /**
     * Record a manual debit (charge/fee) to customer's account.
     */
    public function recordLedgerDebit(Customer $customer, Request $request)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('customers.ledger', $customer);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $ledger = $this->ledgerService->recordAdjustment(
            customer: $customer,
            type: 'debit',
            amount: (float) $validated['amount'],
            reference: $validated['reference_number'] ?? null,
            description: $validated['description'],
            userId: Auth::id()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Debit entry of ₹'.number_format($ledger->amount, 2).' recorded successfully.',
                'new_balance' => (float) $customer->fresh()->balance,
            ]);
        }

        return redirect()->route('customers.ledger', $customer)->with('success', 'Debit adjustment of ₹'.number_format($ledger->amount, 2).' added successfully.');
    }

    /**
     * Render printable customer statement.
     */
    public function printStatement(Customer $customer, Request $request): View
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = CustomerLedger::with(['bill', 'createdByUser'])
            ->where('customer_id', $customer->id);

        if ($startDate) {
            $query->where('entry_date', '>=', Carbon::parse($startDate)->startOfDay());
        }

        if ($endDate) {
            $query->where('entry_date', '<=', Carbon::parse($endDate)->endOfDay());
        }

        $ledgers = $query->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $openingBalance = 0.00;
        if ($startDate) {
            $priorDebits = (float) CustomerLedger::where('customer_id', $customer->id)
                ->where('entry_date', '<', Carbon::parse($startDate)->startOfDay())
                ->where('type', 'debit')
                ->sum('amount');

            $priorCredits = (float) CustomerLedger::where('customer_id', $customer->id)
                ->where('entry_date', '<', Carbon::parse($startDate)->startOfDay())
                ->where('type', 'credit')
                ->sum('amount');

            $openingBalance = round($priorDebits - $priorCredits, 2);
        }

        $periodDebits = $ledgers->where('type', 'debit')->sum('amount');
        $periodCredits = $ledgers->where('type', 'credit')->sum('amount');
        $closingBalance = $startDate
            ? round($openingBalance + $periodDebits - $periodCredits, 2)
            : (float) $customer->balance;

        $currency = Setting::get('currency_symbol', '₹');
        $restaurantName = Setting::get('restaurant_name', 'Restaurant Name');
        $restaurantAddress = Setting::get('restaurant_address', '');
        $restaurantContact = Setting::get('restaurant_contact', '');
        $restaurantGstin = Setting::get('restaurant_gstin', '');

        return view('customers.print_statement', compact(
            'customer',
            'ledgers',
            'startDate',
            'endDate',
            'openingBalance',
            'periodDebits',
            'periodCredits',
            'closingBalance',
            'currency',
            'restaurantName',
            'restaurantAddress',
            'restaurantContact',
            'restaurantGstin'
        ));
    }

    /**
     * Return recent ledger entries as JSON for quick POS preview modal.
     */
    public function ledgerJson(Customer $customer): JsonResponse
    {
        $entries = $customer->ledgers()
            ->latest('entry_date')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'entry_date' => $l->entry_date->format('d M Y, h:i A'),
                    'type' => $l->type,
                    'amount' => (float) $l->amount,
                    'balance_after' => (float) $l->balance_after,
                    'payment_mode' => $l->payment_mode,
                    'reference_number' => $l->reference_number,
                    'description' => $l->description,
                ];
            });

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone ?: '',
                'balance' => (float) $customer->balance,
                'formatted_balance' => $customer->formatted_balance,
                'is_due' => $customer->isDue(),
                'is_advance' => $customer->isAdvance(),
            ],
            'entries' => $entries,
        ]);
    }
}
