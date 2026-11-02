@extends('layouts.app')

@section('title', 'Ledgers Master')
@section('page_title', 'Chart of Accounts & Ledgers (Shortcut: g)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: g</span>
                <h1 class="text-xl font-bold text-slate-800">Chart of Accounts & Master Ledgers</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Manage accounting ledger accounts, account groups, and balances</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="document.getElementById('newLedgerModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>Add New Ledger</span>
            </button>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('accounting.ledgers') }}" class="flex flex-wrap items-center gap-3 flex-1">
            <select name="group" onchange="this.form.submit()" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
                <option value="">All Account Groups</option>
                <option value="Current Assets" {{ $group === 'Current Assets' ? 'selected' : '' }}>Current Assets</option>
                <option value="Sundry Debtors" {{ $group === 'Sundry Debtors' ? 'selected' : '' }}>Sundry Debtors (Customers)</option>
                <option value="Sundry Creditors" {{ $group === 'Sundry Creditors' ? 'selected' : '' }}>Sundry Creditors (Suppliers)</option>
                <option value="Bank Accounts" {{ $group === 'Bank Accounts' ? 'selected' : '' }}>Bank Accounts</option>
                <option value="Sales" {{ $group === 'Sales' ? 'selected' : '' }}>Sales Accounts</option>
                <option value="Purchase" {{ $group === 'Purchase' ? 'selected' : '' }}>Purchase Accounts</option>
            </select>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search ledger name..." class="text-xs border border-slate-300 rounded px-3 py-1.5 w-60">
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs font-bold rounded">Filter</button>
            <a href="{{ route('accounting.ledgers') }}" class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-bold rounded">Reset</a>
        </form>
    </div>

    <!-- Ledgers Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Ledger Account Name</th>
                        <th class="py-3 px-4">Account Group</th>
                        <th class="py-3 px-4 text-right">Opening Balance</th>
                        <th class="py-3 px-4 text-right">Debit (₹)</th>
                        <th class="py-3 px-4 text-right">Credit (₹)</th>
                        <th class="py-3 px-4 text-right">Closing Balance</th>
                        <th class="py-3 px-4 text-center">Dr/Cr</th>
                        <th class="py-3 px-4 text-center">Statement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($ledgers as $l)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-bold text-slate-800">
                            <a href="{{ $l['route'] }}" class="text-blue-700 hover:underline">{{ $l['name'] }}</a>
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $l['group'] }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-500">{{ $currency }} {{ number_format($l['opening_balance'], 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-blue-600">{{ $currency }} {{ number_format($l['debit'], 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-600">{{ $currency }} {{ number_format($l['credit'], 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">{{ $currency }} {{ number_format($l['balance'], 2) }}</td>
                        <td class="py-3 px-4 text-center font-bold {{ $l['balance_type'] === 'Dr' ? 'text-blue-700' : 'text-emerald-700' }}">
                            {{ $l['balance_type'] }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ $l['route'] }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-bold">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Ledger -->
<div id="newLedgerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
        <div class="bg-blue-800 text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-book-bookmark mr-2"></i> Create Master Ledger</h3>
            <button onclick="document.getElementById('newLedgerModal').classList.add('hidden')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('accounting.ledgers.store') }}" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Ledger Name *</label>
                <input type="text" name="name" required placeholder="e.g. Ramesh Traders or State Bank of India" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Account Group *</label>
                <select name="group" required class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                    <option value="Sundry Debtors (Customers)">Sundry Debtors (Customers)</option>
                    <option value="Sundry Creditors (Suppliers)">Sundry Creditors (Suppliers)</option>
                    <option value="Bank Accounts">Bank Accounts</option>
                    <option value="Direct Expenses">Direct Expenses</option>
                    <option value="Indirect Expenses">Indirect Expenses</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Phone / Mobile</label>
                    <input type="text" name="phone" placeholder="Contact number" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">GSTIN</label>
                    <input type="text" name="gstin" placeholder="GST Number" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Opening Balance (₹)</label>
                <input type="number" step="0.01" name="opening_balance" value="0.00" class="w-full border border-slate-300 rounded px-2.5 py-1.5 text-right font-mono">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="document.getElementById('newLedgerModal').classList.add('hidden')" class="px-4 py-1.5 text-slate-600 hover:bg-slate-100 rounded font-bold">Cancel</button>
                <button type="submit" class="px-5 py-1.5 bg-blue-700 hover:bg-blue-800 text-white rounded font-bold shadow">Save Ledger</button>
            </div>
        </form>
    </div>
</div>
@endsection

