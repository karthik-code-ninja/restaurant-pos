@extends('layouts.app')

@section('title', 'Ledger Statement')
@section('page_title', 'Ledger Statement of Account (Shortcut: 2)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm no-print">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded">Shortcut: 2</span>
                <h1 class="text-xl font-bold text-slate-800">Ledger Statement of Account</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Account-wise running debit, credit, and balance statement with date filtering</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print Statement</span>
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('accounting.ledger-statement') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-end gap-3 no-print">
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Account Type</label>
            <select name="type" id="accTypeSelect" onchange="toggleAccountDropdowns(this.value)" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
                <option value="customer" {{ $type === 'customer' ? 'selected' : '' }}>Customer (Debtor)</option>
                <option value="supplier" {{ $type === 'supplier' ? 'selected' : '' }}>Supplier (Creditor)</option>
                <option value="cash" {{ $type === 'cash' ? 'selected' : '' }}>Cash in Hand</option>
            </select>
        </div>

        <div id="custSelectDiv" class="{{ $type === 'customer' ? '' : 'hidden' }}">
            <label class="block text-xs font-bold text-slate-600 mb-1">Select Customer</label>
            <select name="id" id="custSelectId" class="text-xs border border-slate-300 rounded px-2.5 py-1.5 min-w-[200px]">
                <option value="">-- Choose Customer --</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ $id == $c->id ? 'selected' : '' }}>{{ $c->name }} (Due: ₹{{ number_format($c->balance, 2) }})</option>
                @endforeach
            </select>
        </div>

        <div id="suppSelectDiv" class="{{ $type === 'supplier' ? '' : 'hidden' }}">
            <label class="block text-xs font-bold text-slate-600 mb-1">Select Supplier</label>
            <select name="id" id="suppSelectId" class="text-xs border border-slate-300 rounded px-2.5 py-1.5 min-w-[200px]" {{ $type === 'supplier' ? '' : 'disabled' }}>
                <option value="">-- Choose Supplier --</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ $id == $s->id ? 'selected' : '' }}>{{ $s->name }} (Due: ₹{{ number_format($s->current_balance, 2) }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">From Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">To Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
        </div>

        <button type="submit" class="px-4 py-1.5 bg-slate-800 text-white text-xs font-bold rounded shadow">View Statement</button>
    </form>

    <!-- Printable Statement Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="flex justify-between items-start border-b border-slate-200 pb-4">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">Statement of Account</span>
                <h2 class="text-xl font-bold text-slate-900 mt-2">{{ $accountName }}</h2>
                <p class="text-xs text-slate-500">Period: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
            </div>
            <div class="text-right">
                <div class="text-xs text-slate-500">Opening Balance:</div>
                <div class="text-sm font-mono font-bold text-slate-800">{{ $currency }} {{ number_format($openingBalance, 2) }}</div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200 text-slate-700 font-bold uppercase">
                        <th class="py-2.5 px-3">Date</th>
                        <th class="py-2.5 px-3">Voucher Type</th>
                        <th class="py-2.5 px-3">Voucher #</th>
                        <th class="py-2.5 px-3">Particulars / Narration</th>
                        <th class="py-2.5 px-3 text-right">Debit (₹)</th>
                        <th class="py-2.5 px-3 text-right">Credit (₹)</th>
                        <th class="py-2.5 px-3 text-right">Running Balance (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <!-- Opening Row -->
                    <tr class="bg-slate-50/50 font-semibold text-slate-600">
                        <td class="py-2 px-3">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</td>
                        <td class="py-2 px-3">-</td>
                        <td class="py-2 px-3">-</td>
                        <td class="py-2 px-3 italic">Opening Balance B/F</td>
                        <td class="py-2 px-3 text-right">-</td>
                        <td class="py-2 px-3 text-right">-</td>
                        <td class="py-2 px-3 text-right font-mono font-bold">{{ number_format($openingBalance, 2) }}</td>
                    </tr>

                    @forelse($entries as $e)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-2.5 px-3 text-slate-600">{{ $e['date'] }}</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-700">{{ $e['voucher_type'] }}</td>
                        <td class="py-2.5 px-3 font-mono text-slate-600">{{ $e['voucher_no'] }}</td>
                        <td class="py-2.5 px-3 text-slate-700">{{ $e['narration'] }}</td>
                        <td class="py-2.5 px-3 text-right font-mono text-blue-700 font-bold">
                            {{ $e['debit'] > 0 ? number_format($e['debit'], 2) : '-' }}
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono text-emerald-700 font-bold">
                            {{ $e['credit'] > 0 ? number_format($e['credit'], 2) : '-' }}
                        </td>
                        <td class="py-2.5 px-3 text-right font-mono font-extrabold text-slate-900">
                            {{ number_format($e['balance'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-6 text-center text-slate-400">
                            No ledger transactions recorded in this period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleAccountDropdowns(type) {
        const custDiv = document.getElementById('custSelectDiv');
        const suppDiv = document.getElementById('suppSelectDiv');
        const custId = document.getElementById('custSelectId');
        const suppId = document.getElementById('suppSelectId');

        if (type === 'customer') {
            custDiv.classList.remove('hidden');
            suppDiv.classList.add('hidden');
            custId.disabled = false;
            suppId.disabled = true;
        } else if (type === 'supplier') {
            custDiv.classList.add('hidden');
            suppDiv.classList.remove('hidden');
            custId.disabled = true;
            suppId.disabled = false;
        } else {
            custDiv.classList.add('hidden');
            suppDiv.classList.add('hidden');
            custId.disabled = true;
            suppId.disabled = true;
        }
    }
</script>
@endsection

