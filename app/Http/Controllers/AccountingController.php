<?php

namespace App\Http\Controllers;

use App\Models\AccountingVoucher;
use App\Models\Bill;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Expense;
use App\Models\Food;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Setting;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AccountingController extends Controller
{
    /**
     * Master Chart of Accounts / Ledgers (g).
     */
    public function ledgers(Request $request): View
    {
        $group = $request->input('group');
        $search = $request->input('search');

        $customers = Customer::where('status', true)->get();
        $suppliers = Supplier::where('status', true)->get();

        $currency = Setting::get('currency_symbol', '₹');

        // Build list of all accounting ledgers
        $ledgers = [];

        // Cash & Bank
        $cashBalance = (float) Payment::where('payment_method', 'cash')->whereHas('bill', function ($q) {
            $q->where('status', 'completed');
        })->sum('amount');
        $bankBalance = (float) Payment::whereIn('payment_method', ['upi', 'card'])->whereHas('bill', function ($q) {
            $q->where('status', 'completed');
        })->sum('amount');

        $ledgers[] = [
            'type' => 'cash',
            'name' => 'Cash in Hand Account',
            'group' => 'Current Assets / Cash-in-hand',
            'opening_balance' => 0.00,
            'debit' => $cashBalance,
            'credit' => (float) Expense::where('payment_method', 'cash')->sum('amount'),
            'balance' => max(0, $cashBalance - (float) Expense::where('payment_method', 'cash')->sum('amount')),
            'balance_type' => 'Dr',
            'route' => route('accounting.ledger-statement', ['type' => 'cash', 'id' => 1]),
        ];

        $ledgers[] = [
            'type' => 'bank',
            'name' => 'Primary Bank Account (HDFC / Current)',
            'group' => 'Bank Accounts',
            'opening_balance' => 10000.00,
            'debit' => $bankBalance + 10000.00,
            'credit' => (float) Purchase::where('payment_method', 'bank')->sum('paid_amount'),
            'balance' => max(0, ($bankBalance + 10000.00) - (float) Purchase::where('payment_method', 'bank')->sum('paid_amount')),
            'balance_type' => 'Dr',
            'route' => route('accounting.ledger-statement', ['type' => 'bank', 'id' => 1]),
        ];

        // Customers (Sundry Debtors)
        foreach ($customers as $cust) {
            $debit = (float) CustomerLedger::where('customer_id', $cust->id)->where('type', 'debit')->sum('amount');
            $credit = (float) CustomerLedger::where('customer_id', $cust->id)->where('type', 'credit')->sum('amount');

            $ledgers[] = [
                'type' => 'customer',
                'id' => $cust->id,
                'name' => $cust->name.($cust->phone ? " ({$cust->phone})" : ''),
                'group' => 'Sundry Debtors (Customers)',
                'opening_balance' => (float) ($cust->opening_balance ?? 0),
                'debit' => $debit,
                'credit' => $credit,
                'balance' => (float) $cust->balance,
                'balance_type' => $cust->balance >= 0 ? 'Dr' : 'Cr',
                'route' => route('accounting.ledger-statement', ['type' => 'customer', 'id' => $cust->id]),
            ];
        }

        // Suppliers (Sundry Creditors)
        foreach ($suppliers as $supp) {
            $totalPurchases = (float) Purchase::where('supplier_id', $supp->id)->sum('grand_total');
            $totalPaid = (float) Purchase::where('supplier_id', $supp->id)->sum('paid_amount');

            $ledgers[] = [
                'type' => 'supplier',
                'id' => $supp->id,
                'name' => $supp->name.($supp->gstin ? " [GST: {$supp->gstin}]" : ''),
                'group' => 'Sundry Creditors (Suppliers)',
                'opening_balance' => (float) $supp->opening_balance,
                'debit' => $totalPaid,
                'credit' => $totalPurchases + (float) $supp->opening_balance,
                'balance' => (float) $supp->current_balance,
                'balance_type' => 'Cr',
                'route' => route('accounting.ledger-statement', ['type' => 'supplier', 'id' => $supp->id]),
            ];
        }

        // Sales Account
        $totalSales = (float) Bill::where('status', 'completed')->sum('grand_total');
        $ledgers[] = [
            'type' => 'sales',
            'name' => 'Sales Revenue Account',
            'group' => 'Sales Accounts',
            'opening_balance' => 0.00,
            'debit' => 0.00,
            'credit' => $totalSales,
            'balance' => $totalSales,
            'balance_type' => 'Cr',
            'route' => route('accounting.sales-register'),
        ];

        // Purchase Account
        $totalPurchasesAll = (float) Purchase::sum('grand_total');
        $ledgers[] = [
            'type' => 'purchase',
            'name' => 'Purchase Account',
            'group' => 'Purchase Accounts',
            'opening_balance' => 0.00,
            'debit' => $totalPurchasesAll,
            'credit' => 0.00,
            'balance' => $totalPurchasesAll,
            'balance_type' => 'Dr',
            'route' => route('purchases.index'),
        ];

        // Filter if requested
        if (! empty($group)) {
            $ledgers = array_filter($ledgers, function ($l) use ($group) {
                return str_contains(strtolower($l['group']), strtolower($group));
            });
        }

        if (! empty($search)) {
            $ledgers = array_filter($ledgers, function ($l) use ($search) {
                return str_contains(strtolower($l['name']), strtolower($search)) ||
                       str_contains(strtolower($l['group']), strtolower($search));
            });
        }

        return view('accounting.ledgers', compact('ledgers', 'currency', 'group', 'search'));
    }

    /**
     * Store a new custom ledger / supplier.
     */
    public function storeLedger(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'group' => 'required|string|max:100',
            'phone' => 'nullable|string|max:20',
            'gstin' => 'nullable|string|max:30',
            'opening_balance' => 'nullable|numeric',
        ]);

        $opening = (float) ($validated['opening_balance'] ?? 0);

        if (str_contains(strtolower($validated['group']), 'creditor') || str_contains(strtolower($validated['group']), 'supplier')) {
            Supplier::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'gstin' => $validated['gstin'],
                'opening_balance' => $opening,
                'current_balance' => $opening,
            ]);
        } elseif (str_contains(strtolower($validated['group']), 'debtor') || str_contains(strtolower($validated['group']), 'customer')) {
            Customer::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? '0000000000',
                'balance' => $opening,
            ]);
        }

        return redirect()->route('accounting.ledgers')->with('success', "Ledger '{$validated['name']}' created successfully!");
    }

    /**
     * Receipt Voucher List & Entry (b).
     */
    public function receipt(Request $request): View
    {
        $query = AccountingVoucher::where('voucher_type', 'receipt');
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('voucher_no', 'like', "%{$s}%")
                    ->orWhere('party_name', 'like', "%{$s}%")
                    ->orWhere('reference_no', 'like', "%{$s}%");
            });
        }

        $receipts = $query->orderByDesc('voucher_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $customers = Customer::where('status', true)->orderBy('name')->get();
        $nextVoucherNo = AccountingVoucher::generateVoucherNo('receipt');
        $currency = Setting::get('currency_symbol', '₹');

        return view('accounting.receipt', compact('receipts', 'customers', 'nextVoucherNo', 'currency'));
    }

    /**
     * Store Customer Payment Receipt Voucher.
     */
    public function storeReceipt(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'voucher_date' => 'required|date',
            'customer_id' => 'nullable|exists:customers,id',
            'party_name' => 'required_without:customer_id|nullable|string|max:150',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'deposit_to' => 'required|string|max:100', // Cash Account or Bank Account
            'reference_no' => 'nullable|string|max:100',
            'narration' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $customer = null;
            $partyName = $validated['party_name'] ?? 'General Customer';

            if (! empty($validated['customer_id'])) {
                $customer = Customer::find($validated['customer_id']);
                $partyName = $customer->name;
            }

            $amount = (float) $validated['amount'];
            $voucherNo = AccountingVoucher::generateVoucherNo('receipt');

            // Record Accounting Voucher
            $voucher = AccountingVoucher::create([
                'voucher_no' => $voucherNo,
                'voucher_type' => 'receipt',
                'voucher_date' => $validated['voucher_date'],
                'party_type' => 'customer',
                'party_id' => $customer?->id,
                'party_name' => $partyName,
                'debit_ledger' => $validated['deposit_to'],
                'credit_ledger' => 'Sundry Debtors',
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'narration' => $validated['narration'] ?? "Received with thanks from {$partyName}",
                'created_by' => auth()->id(),
            ]);

            // If customer exists, update Customer Ledger & balance
            if ($customer) {
                $prevBalance = (float) $customer->balance;
                $newBalance = $prevBalance - $amount;

                CustomerLedger::create([
                    'customer_id' => $customer->id,
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_after' => $newBalance,
                    'payment_mode' => $validated['payment_method'],
                    'description' => "Receipt Voucher #{$voucherNo} - ".($validated['narration'] ?? 'Due settlement'),
                    'created_by' => auth()->id(),
                ]);

                $customer->update(['balance' => $newBalance]);
            }

            DB::commit();

            return redirect()->route('accounting.receipt')->with('success', "Receipt Voucher {$voucherNo} recorded successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Error recording receipt: '.$e->getMessage());
        }
    }

    /**
     * Payment Voucher List & Entry (d).
     */
    public function payment(Request $request): View
    {
        $query = AccountingVoucher::where('voucher_type', 'payment');
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('voucher_no', 'like', "%{$s}%")
                    ->orWhere('party_name', 'like', "%{$s}%")
                    ->orWhere('reference_no', 'like', "%{$s}%");
            });
        }

        $payments = $query->orderByDesc('voucher_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $suppliers = Supplier::where('status', true)->orderBy('name')->get();
        $nextVoucherNo = AccountingVoucher::generateVoucherNo('payment');
        $currency = Setting::get('currency_symbol', '₹');

        return view('accounting.payment', compact('payments', 'suppliers', 'nextVoucherNo', 'currency'));
    }

    /**
     * Store Outward Payment Voucher.
     */
    public function storePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'voucher_date' => 'required|date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'party_name' => 'required_without:supplier_id|nullable|string|max:150',
            'expense_head' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'paid_from' => 'required|string|max:100', // Cash Account or Bank Account
            'reference_no' => 'nullable|string|max:100',
            'narration' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $supplier = null;
            $partyName = $validated['party_name'] ?? ($validated['expense_head'] ?? 'General Payee');

            if (! empty($validated['supplier_id'])) {
                $supplier = Supplier::find($validated['supplier_id']);
                $partyName = $supplier->name;
            }

            $amount = (float) $validated['amount'];
            $voucherNo = AccountingVoucher::generateVoucherNo('payment');

            $voucher = AccountingVoucher::create([
                'voucher_no' => $voucherNo,
                'voucher_type' => 'payment',
                'voucher_date' => $validated['voucher_date'],
                'party_type' => $supplier ? 'supplier' : 'expense',
                'party_id' => $supplier?->id,
                'party_name' => $partyName,
                'debit_ledger' => $supplier ? 'Sundry Creditors' : ($validated['expense_head'] ?? 'Operating Expenses'),
                'credit_ledger' => $validated['paid_from'],
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_no' => $validated['reference_no'] ?? null,
                'narration' => $validated['narration'] ?? "Paid to {$partyName}",
                'created_by' => auth()->id(),
            ]);

            // If supplier, reduce supplier payable balance
            if ($supplier) {
                $supplier->decrement('current_balance', min($supplier->current_balance, $amount));
            }

            DB::commit();

            return redirect()->route('accounting.payment')->with('success', "Payment Voucher {$voucherNo} recorded successfully!");
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->with('error', 'Error recording payment: '.$e->getMessage());
        }
    }

    /**
     * Customer Outstanding Receivables (e).
     */
    public function receivables(Request $request): View
    {
        $search = $request->input('search');

        $query = Customer::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderByDesc('balance')->paginate(20)->withQueryString();

        $totalReceivable = (float) Customer::where('balance', '>', 0)->sum('balance');
        $totalCustomersWithDue = Customer::where('balance', '>', 0)->count();

        $currency = Setting::get('currency_symbol', '₹');

        return view('accounting.receivables', compact(
            'customers',
            'totalReceivable',
            'totalCustomersWithDue',
            'currency',
            'search'
        ));
    }

    /**
     * Vendor Outstanding Payables (f).
     */
    public function payables(Request $request): View
    {
        $search = $request->input('search');

        $query = Supplier::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderByDesc('current_balance')->paginate(20)->withQueryString();

        $totalPayable = (float) Supplier::where('current_balance', '>', 0)->sum('current_balance');
        $totalSuppliersWithDue = Supplier::where('current_balance', '>', 0)->count();

        $currency = Setting::get('currency_symbol', '₹');

        return view('accounting.payables', compact(
            'suppliers',
            'totalPayable',
            'totalSuppliersWithDue',
            'currency',
            'search'
        ));
    }

    /**
     * Double-Entry Trial Balance (1).
     */
    public function trialBalance(Request $request): View
    {
        $asOfDate = $request->input('as_of', Carbon::today()->toDateString());
        $currency = Setting::get('currency_symbol', '₹');

        // Assets (Debit balance)
        $cashBalance = (float) Payment::where('payment_method', 'cash')->whereHas('bill', function ($q) {
            $q->where('status', 'completed');
        })->sum('amount');
        $bankBalance = (float) Payment::whereIn('payment_method', ['upi', 'card'])->whereHas('bill', function ($q) {
            $q->where('status', 'completed');
        })->sum('amount');
        $debtorsBalance = (float) Customer::where('balance', '>', 0)->sum('balance');

        $stockValue = (float) Food::all()->sum(function ($item) {
            return ($item->cost_price ?: ($item->price * 0.5)) * ($item->current_stock ?? 25);
        });

        // Liabilities (Credit balance)
        $creditorsBalance = (float) Supplier::where('current_balance', '>', 0)->sum('current_balance');

        // Income (Credit balance)
        $salesRevenue = (float) Bill::where('status', 'completed')->sum('grand_total');

        // Expenses (Debit balance)
        $purchasesCost = (float) Purchase::sum('grand_total');
        $operatingExpenses = (float) Expense::sum('amount');

        $rows = [
            ['code' => '1001', 'name' => 'Cash in Hand', 'group' => 'Current Assets', 'debit' => $cashBalance, 'credit' => 0],
            ['code' => '1002', 'name' => 'Bank Accounts', 'group' => 'Current Assets', 'debit' => $bankBalance + 10000, 'credit' => 0],
            ['code' => '1003', 'name' => 'Sundry Debtors (Receivables)', 'group' => 'Current Assets', 'debit' => $debtorsBalance, 'credit' => 0],
            ['code' => '1004', 'name' => 'Closing Stock in Trade', 'group' => 'Current Assets', 'debit' => $stockValue, 'credit' => 0],
            ['code' => '2001', 'name' => 'Sundry Creditors (Payables)', 'group' => 'Current Liabilities', 'debit' => 0, 'credit' => $creditorsBalance],
            ['code' => '3001', 'name' => 'Sales Revenue Account', 'group' => 'Revenue Accounts', 'debit' => 0, 'credit' => $salesRevenue],
            ['code' => '4001', 'name' => 'Purchases Account', 'group' => 'Direct Expenses', 'debit' => $purchasesCost, 'credit' => 0],
            ['code' => '4002', 'name' => 'Operating / Direct Expenses', 'group' => 'Indirect Expenses', 'debit' => $operatingExpenses, 'credit' => 0],
        ];

        $totalDebits = array_sum(array_column($rows, 'debit'));
        $totalCredits = array_sum(array_column($rows, 'credit'));

        // Balancing capital/proprietor equity row
        $diff = $totalDebits - $totalCredits;
        if ($diff > 0) {
            $rows[] = ['code' => '5001', 'name' => 'Capital & Reserves (Balancing)', 'group' => 'Capital Account', 'debit' => 0, 'credit' => $diff];
            $totalCredits += $diff;
        } elseif ($diff < 0) {
            $rows[] = ['code' => '5001', 'name' => 'Owner Drawings (Balancing)', 'group' => 'Capital Account', 'debit' => abs($diff), 'credit' => 0];
            $totalDebits += abs($diff);
        }

        return view('accounting.trial_balance', compact('rows', 'totalDebits', 'totalCredits', 'asOfDate', 'currency'));
    }

    /**
     * Detailed Ledger Statement (2).
     */
    public function ledgerStatement(Request $request): View
    {
        $type = $request->input('type', 'customer');
        $id = $request->input('id');
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $currency = Setting::get('currency_symbol', '₹');

        $customers = Customer::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        $accountName = 'Select an Account';
        $openingBalance = 0.00;
        $entries = [];

        if ($type === 'customer' && ! empty($id)) {
            $customer = Customer::find($id);
            if ($customer) {
                $accountName = $customer->name." (Customer #{$customer->id})";
                $openingBalance = (float) $customer->opening_balance;

                $ledgers = CustomerLedger::where('customer_id', $customer->id)
                    ->whereBetween('entry_date', [
                        Carbon::parse($startDate)->startOfDay(),
                        Carbon::parse($endDate)->endOfDay(),
                    ])
                    ->orderBy('entry_date')
                    ->get();

                $running = $openingBalance;
                foreach ($ledgers as $l) {
                    $debit = $l->type === 'debit' ? (float) $l->amount : 0;
                    $credit = $l->type === 'credit' ? (float) $l->amount : 0;
                    $running = $running + $debit - $credit;

                    $entries[] = [
                        'date' => Carbon::parse($l->entry_date)->format('d/m/Y'),
                        'voucher_type' => $l->type === 'debit' ? 'Sales Bill' : 'Receipt',
                        'voucher_no' => $l->bill_id ? "BILL-{$l->bill_id}" : "RCP-{$l->id}",
                        'narration' => $l->description ?? 'Customer ledger transaction',
                        'debit' => $debit,
                        'credit' => $credit,
                        'balance' => $running,
                    ];
                }
            }
        } elseif ($type === 'supplier' && ! empty($id)) {
            $supplier = Supplier::find($id);
            if ($supplier) {
                $accountName = $supplier->name." (Supplier #{$supplier->id})";
                $openingBalance = (float) $supplier->opening_balance;

                $purchases = Purchase::where('supplier_id', $supplier->id)
                    ->whereBetween('purchase_date', [$startDate, $endDate])
                    ->orderBy('purchase_date')
                    ->get();

                $running = $openingBalance;
                foreach ($purchases as $p) {
                    $credit = (float) $p->grand_total;
                    $debit = (float) $p->paid_amount;
                    $running = $running + $credit - $debit;

                    $entries[] = [
                        'date' => $p->purchase_date->format('d/m/Y'),
                        'voucher_type' => 'Purchase Invoice',
                        'voucher_no' => $p->purchase_no,
                        'narration' => "Purchase Bill: {$p->invoice_no} ({$p->payment_status})",
                        'debit' => $debit,
                        'credit' => $credit,
                        'balance' => $running,
                    ];
                }
            }
        } else {
            // General Cash Book statement
            $accountName = 'Cash in Hand Account';
            $vouchers = AccountingVoucher::whereBetween('voucher_date', [$startDate, $endDate])
                ->where('payment_method', 'cash')
                ->orderBy('voucher_date')
                ->get();

            $running = 0.00;
            foreach ($vouchers as $v) {
                $debit = $v->voucher_type === 'receipt' ? (float) $v->amount : 0;
                $credit = $v->voucher_type === 'payment' ? (float) $v->amount : 0;
                $running = $running + $debit - $credit;

                $entries[] = [
                    'date' => $v->voucher_date->format('d/m/Y'),
                    'voucher_type' => ucfirst($v->voucher_type),
                    'voucher_no' => $v->voucher_no,
                    'narration' => $v->narration ?? $v->party_name,
                    'debit' => $debit,
                    'credit' => $credit,
                    'balance' => $running,
                ];
            }
        }

        return view('accounting.ledger_statement', compact(
            'accountName',
            'openingBalance',
            'entries',
            'type',
            'id',
            'startDate',
            'endDate',
            'customers',
            'suppliers',
            'currency'
        ));
    }

    /**
     * Chronological Voucher Register (3).
     */
    public function voucherRegister(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $voucherType = $request->input('type');
        $search = $request->input('search');

        $query = AccountingVoucher::whereBetween('voucher_date', [$startDate, $endDate]);

        if (! empty($voucherType)) {
            $query->where('voucher_type', $voucherType);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('voucher_no', 'like', "%{$search}%")
                    ->orWhere('party_name', 'like', "%{$search}%")
                    ->orWhere('reference_no', 'like', "%{$search}%")
                    ->orWhere('narration', 'like', "%{$search}%");
            });
        }

        $vouchers = $query->orderByDesc('voucher_date')->orderByDesc('id')->paginate(20)->withQueryString();
        $currency = Setting::get('currency_symbol', '₹');

        return view('accounting.voucher_register', compact(
            'vouchers',
            'startDate',
            'endDate',
            'voucherType',
            'search',
            'currency'
        ));
    }

    /**
     * Business View / Trading & Profit and Loss Account (4).
     */
    public function businessView(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $currency = Setting::get('currency_symbol', '₹');

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Trading Account
        $salesRevenue = (float) Bill::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('grand_total');

        $openingStock = 15000.00; // estimated opening stock for period
        $purchases = (float) Purchase::whereBetween('purchase_date', [$startDate, $endDate])
            ->sum('grand_total');

        $closingStock = (float) Food::all()->sum(function ($item) {
            return ($item->cost_price ?: ($item->price * 0.5)) * ($item->current_stock ?? 25);
        });

        $costOfGoodsSold = max(0, ($openingStock + $purchases) - $closingStock);
        if ($costOfGoodsSold <= 0 && $salesRevenue > 0) {
            $costOfGoodsSold = $salesRevenue * 0.42; // baseline food cost ratio
        }

        $grossProfit = $salesRevenue - $costOfGoodsSold;
        $grossMarginPercent = $salesRevenue > 0 ? round(($grossProfit / $salesRevenue) * 100, 1) : 0;

        // 2. Profit & Loss Account
        $operatingExpenses = (float) Expense::whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $netProfit = $grossProfit - $operatingExpenses;
        $netMarginPercent = $salesRevenue > 0 ? round(($netProfit / $salesRevenue) * 100, 1) : 0;

        // Breakdown of expenses
        $expensesByCategory = Expense::whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->select('expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('expense_category_id')
            ->get();

        return view('accounting.business_view', compact(
            'salesRevenue',
            'openingStock',
            'purchases',
            'closingStock',
            'costOfGoodsSold',
            'grossProfit',
            'grossMarginPercent',
            'operatingExpenses',
            'netProfit',
            'netMarginPercent',
            'expensesByCategory',
            'startDate',
            'endDate',
            'currency'
        ));
    }
}
