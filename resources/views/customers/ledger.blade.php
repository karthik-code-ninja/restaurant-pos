@extends('layouts.app')

@section('title', 'Ledger - ' . $customer->name)
@section('page_title', 'Customer Ledger Statement')

@section('content')
<div class="space-y-6">
    <!-- Back Navigation & Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('customers.index') }}" class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-slate-900 flex items-center justify-center transition shadow-sm" title="Back to Customer List">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <h3 class="text-xl font-black text-slate-900 flex items-center gap-2">
                    <span>{{ $customer->name }}</span>
                    @if($customer->phone)
                    <span class="text-xs font-mono font-normal text-slate-500">({{ $customer->phone }})</span>
                    @endif
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Account Ledger & Transaction History</p>
            </div>
        </div>

        <div class="flex items-center flex-wrap gap-2 self-end sm:self-auto">
            @if(auth()->user()->hasPermission('customer.manage'))
            <!-- Receive Payment (Credit) -->
            <button type="button" onclick="openReceivePaymentModal()" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition active:scale-95">
                <i class="fa-solid fa-hand-holding-dollar"></i> Receive Payment
            </button>

            <!-- Add Debit Charge -->
            <button type="button" onclick="openAddDebitModal()" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition active:scale-95">
                <i class="fa-solid fa-plus-circle"></i> Add Debit Charge
            </button>
            @endif

            <!-- Print Statement -->
            <a href="{{ route('customers.print-statement', [$customer, 'start_date' => $startDate, 'end_date' => $endDate]) }}" target="_blank" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition active:scale-95">
                <i class="fa-solid fa-print"></i> Print Statement
            </a>
        </div>
    </div>

    <!-- Customer Overview Card & Period Financial Summary -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Customer Info Card -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Customer Profile</span>
                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $customer->status ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                    {{ $customer->status ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">Phone:</span>
                    <span class="font-bold text-slate-800">{{ $customer->phone ?: 'Not provided' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Email:</span>
                    <span class="font-medium text-slate-800">{{ $customer->email ?: 'Not provided' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">GSTIN:</span>
                    <span class="font-mono text-slate-800">{{ $customer->gstin ?: 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Credit Limit:</span>
                    <span class="font-bold text-slate-800 font-mono">{{ $customer->credit_limit > 0 ? $currency . number_format($customer->credit_limit, 2) : 'No Limit' }}</span>
                </div>
                @if($customer->address)
                <div class="pt-1.5 border-t border-slate-100">
                    <span class="text-slate-400 block text-[11px]">Address:</span>
                    <span class="text-slate-700 text-xs">{{ $customer->address }}</span>
                </div>
                @endif
            </div>

            <!-- Current Real-time Balance Box -->
            <div class="pt-3 border-t border-slate-100">
                <div class="p-3.5 rounded-xl flex items-center justify-between {{ $customer->balance > 0.001 ? 'bg-rose-50 text-rose-800 border border-rose-200' : ($customer->balance < -0.001 ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-700') }}">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider block">Live Balance</span>
                        <span class="text-xs font-medium">
                            @if($customer->balance > 0.001) Customer Owes You (Due) @elseif($customer->balance < -0.001) Customer Has Advance (Credit) @else Settled Account @endif
                        </span>
                    </div>
                    <span class="text-xl font-black font-mono">
                        {{ $currency }}{{ number_format(abs($customer->balance), 2) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Period Summary Cards (2 cols) -->
        <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Opening Balance for Period -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Opening Balance</span>
                    <h4 class="text-2xl font-black mt-2 {{ $openingBalance > 0 ? 'text-rose-600' : ($openingBalance < 0 ? 'text-emerald-600' : 'text-slate-700') }}">
                        {{ $currency }}{{ number_format(abs($openingBalance), 2) }}
                    </h4>
                </div>
                <p class="text-xs text-slate-400 mt-2">
                    {{ $openingBalance > 0 ? 'Due at period start' : ($openingBalance < 0 ? 'Advance at period start' : 'Zero at period start') }}
                </p>
            </div>

            <!-- Closing Balance for Period -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Closing Balance</span>
                    <h4 class="text-2xl font-black mt-2 {{ $closingBalance > 0 ? 'text-rose-600' : ($closingBalance < 0 ? 'text-emerald-600' : 'text-slate-700') }}">
                        {{ $currency }}{{ number_format(abs($closingBalance), 2) }}
                    </h4>
                </div>
                <p class="text-xs text-slate-400 mt-2">
                    {{ $closingBalance > 0 ? 'Outstanding Due' : ($closingBalance < 0 ? 'Advance Deposit' : 'Settled') }}
                </p>
            </div>

            <!-- Total Debits in Period (Charges & Credit Bills) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-rose-600 uppercase tracking-wider block">Total Debits (+)</span>
                    <h4 class="text-2xl font-black text-rose-600 mt-2">
                        {{ $currency }}{{ number_format($periodDebits, 2) }}
                    </h4>
                </div>
                <p class="text-xs text-slate-400 mt-2">Credit bills & charges added to customer</p>
            </div>

            <!-- Total Credits in Period (Payments & Reductions) -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block">Total Credits (-)</span>
                    <h4 class="text-2xl font-black text-emerald-600 mt-2">
                        {{ $currency }}{{ number_format($periodCredits, 2) }}
                    </h4>
                </div>
                <p class="text-xs text-slate-400 mt-2">Payments received & settlements</p>
            </div>
        </div>
    </div>

    <!-- Date Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <form method="GET" action="{{ route('customers.ledger', $customer) }}" class="flex flex-wrap items-center gap-2.5 text-xs w-full sm:w-auto">
            <span class="font-bold text-slate-600 uppercase text-[11px]">Filter Date:</span>
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <span class="text-slate-400">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">

            <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold transition">
                Apply
            </button>

            @if($startDate || $endDate)
            <a href="{{ route('customers.ledger', $customer) }}" class="px-3 py-1.5 text-slate-600 hover:text-slate-900 font-semibold">
                Clear
            </a>
            @endif
        </form>

        <span class="text-xs text-slate-400 font-medium">
            Showing {{ $ledgers->count() }} transactions
        </span>
    </div>

    <!-- Ledger Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h4 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                <i class="fa-solid fa-list-check text-orange-500"></i> Statement Transactions
            </h4>
            <span class="text-xs text-slate-500 font-mono">Running Balance</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-100/75 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-4 py-3">Date & Time</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Reference / Bill</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3 text-right">Debit (+)</th>
                        <th class="px-4 py-3 text-right">Credit (-)</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3 text-slate-500">Staff</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($ledgers as $entry)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                            <span class="font-bold text-slate-800 block">{{ $entry->entry_date->format('d M Y') }}</span>
                            <span class="text-[11px] text-slate-400">{{ $entry->entry_date->format('h:i A') }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($entry->type === 'debit')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200 uppercase">
                                    Debit
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                                    Credit
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($entry->bill)
                                <a href="{{ route('print.bill', $entry->bill_id) }}" target="_blank" class="font-bold text-indigo-600 hover:text-indigo-800 transition inline-flex items-center gap-1">
                                    <i class="fa-solid fa-receipt text-[10px]"></i> #{{ $entry->bill->invoice_number }}
                                </a>
                            @elseif($entry->reference_number)
                                <span class="font-mono text-slate-800">{{ $entry->reference_number }}</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif

                            @if($entry->payment_mode)
                                <span class="text-[10px] text-slate-400 font-sans uppercase block">
                                    {{ str_replace('_', ' ', $entry->payment_mode) }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-800 max-w-xs truncate">
                            {{ $entry->description ?: '-' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-rose-600">
                            {{ $entry->type === 'debit' ? $currency . number_format($entry->amount, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-bold text-emerald-600">
                            {{ $entry->type === 'credit' ? $currency . number_format($entry->amount, 2) : '-' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono font-black">
                            @if($entry->balance_after > 0.001)
                                <span class="text-rose-700">₹{{ number_format($entry->balance_after, 2) }} Dr</span>
                            @elseif($entry->balance_after < -0.001)
                                <span class="text-emerald-700">₹{{ number_format(abs($entry->balance_after), 2) }} Cr</span>
                            @else
                                <span class="text-slate-600">₹0.00</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap text-[11px]">
                            {{ $entry->createdByUser?->name ?? 'System' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                            <i class="fa-solid fa-book-open text-3xl mb-2 text-slate-300 block"></i>
                            <p class="font-bold text-sm text-slate-600">No Ledger Transactions Yet</p>
                            <p class="text-xs text-slate-400 mt-1">Transactions like credit bills, customer payments, or adjustments will appear here.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: Receive Payment (Credit) -->
<div id="receivePaymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <i class="fa-solid fa-hand-holding-dollar text-emerald-400"></i> Receive Customer Payment
                </h3>
                <p class="text-xs text-orange-400 font-medium mt-0.5">{{ $customer->name }}</p>
            </div>
            <button type="button" onclick="closeReceivePaymentModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('customers.payment', $customer) }}" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            <!-- Current Due Banner -->
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                <span class="text-slate-500 font-bold uppercase text-[10px]">Current Balance</span>
                <span class="font-black text-sm {{ $customer->balance > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                    {{ $customer->formatted_balance }}
                </span>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Amount Received (₹) *</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 font-bold text-sm">₹</span>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ $customer->balance > 0 ? number_format($customer->balance, 2, '.', '') : '' }}" required placeholder="0.00" class="w-full pl-7 pr-3 py-2 rounded-xl border border-slate-300 font-black text-base focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Payment Mode *</label>
                <select name="payment_mode" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-semibold">
                    <option value="cash">Cash</option>
                    <option value="upi">UPI / QR Code</option>
                    <option value="card">Card (Debit/Credit)</option>
                    <option value="bank_transfer">Bank Transfer / NEFT / IMPS</option>
                    <option value="cheque">Cheque</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Reference / UTR / Cheque No.</label>
                <input type="text" name="reference_number" placeholder="Optional transaction ID or cheque number" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Notes / Description</label>
                <input type="text" name="description" placeholder="e.g. Cleared bill outstanding dues" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                <button type="button" onclick="closeReceivePaymentModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold transition shadow-sm">Record Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Add Debit Charge -->
<div id="addDebitModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-rose-400"></i> Add Debit Charge
                </h3>
                <p class="text-xs text-orange-400 font-medium mt-0.5">{{ $customer->name }}</p>
            </div>
            <button type="button" onclick="closeAddDebitModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('customers.debit', $customer) }}" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Debit Amount (₹) *</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 font-bold text-sm">₹</span>
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="w-full pl-7 pr-3 py-2 rounded-xl border border-slate-300 font-black text-base focus:ring-2 focus:ring-rose-500">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Reason / Description *</label>
                <input type="text" name="description" required placeholder="e.g. Additional catering charge / Late surcharge" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Reference No. (Optional)</label>
                <input type="text" name="reference_number" placeholder="Optional reference" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                <button type="button" onclick="closeAddDebitModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg font-bold transition shadow-sm">Save Debit Entry</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openReceivePaymentModal() {
        document.getElementById('receivePaymentModal').classList.remove('hidden');
    }
    function closeReceivePaymentModal() {
        document.getElementById('receivePaymentModal').classList.add('hidden');
    }

    function openAddDebitModal() {
        document.getElementById('addDebitModal').classList.remove('hidden');
    }
    function closeAddDebitModal() {
        document.getElementById('addDebitModal').classList.add('hidden');
    }
</script>
@endpush
@endsection

