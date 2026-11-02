@extends('layouts.app')

@section('title', 'Accounts Payable')
@section('page_title', 'Payables / Vendor Outstanding (Shortcut: f)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: f</span>
                <h1 class="text-xl font-bold text-slate-800">Accounts Payable (Sundry Creditors)</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Track balances owed to suppliers and disburse vendor payments</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <a href="{{ route('accounting.payment') }}" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-money-bill-transfer"></i>
                <span>Disburse Payment (d)</span>
            </a>
        </div>
    </div>

    <!-- Summary Banner -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-r from-amber-600 to-orange-600 text-white p-5 rounded-xl shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-amber-100">Total Vendor Payables Due</div>
                <div class="text-3xl font-black mt-1">{{ $currency }} {{ number_format($totalPayable, 2) }}</div>
            </div>
            <i class="fa-solid fa-truck-field text-4xl opacity-20"></i>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Vendors with Pending Balances</div>
                <div class="text-3xl font-black text-slate-800 mt-1">{{ $totalSuppliersWithDue }} Suppliers</div>
            </div>
            <i class="fa-solid fa-building text-4xl text-slate-200"></i>
        </div>
    </div>

    <!-- Payables Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex justify-between items-center">
            <form method="GET" action="{{ route('accounting.payables') }}" class="flex items-center gap-2 max-w-sm w-full">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search vendor by name, phone, GSTIN..." class="w-full text-xs border border-slate-300 rounded px-3 py-1.5 focus:outline-none focus:border-red-500">
                <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded">Search</button>
            </form>
            <button onclick="window.print()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded flex items-center gap-1">
                <i class="fa-solid fa-print"></i> Print Payable Aging
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Supplier Name</th>
                        <th class="py-3 px-4">GSTIN</th>
                        <th class="py-3 px-4">Phone / Contact</th>
                        <th class="py-3 px-4 text-right">Opening Balance</th>
                        <th class="py-3 px-4 text-right">Current Payable Due</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($suppliers as $s)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-bold text-slate-800">
                            <a href="{{ route('accounting.ledger-statement', ['type' => 'supplier', 'id' => $s->id]) }}" class="hover:underline text-blue-700">{{ $s->name }}</a>
                            @if($s->company_name)
                            <div class="text-[10px] text-slate-400 font-normal">{{ $s->company_name }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-600 font-mono">{{ $s->gstin ?: '-' }}</td>
                        <td class="py-3 px-4 text-slate-600 font-mono">{{ $s->phone ?: '-' }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600">{{ $currency }} {{ number_format($s->opening_balance, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-red-600 text-sm">
                            {{ $currency }} {{ number_format($s->current_balance, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($s->current_balance > 0)
                                <span class="px-2 py-0.5 bg-red-100 text-red-800 font-bold rounded-full text-[10px]">PAYABLE</span>
                            @else
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded-full text-[10px]">SETTLED</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('accounting.ledger-statement', ['type' => 'supplier', 'id' => $s->id]) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-bold">
                                    Statement
                                </a>
                                @if($s->current_balance > 0)
                                <a href="{{ route('accounting.payment') }}" class="px-2 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-[11px] font-bold">
                                    Pay Now
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-truck-field text-3xl mb-2"></i>
                            <p class="font-medium">No suppliers found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $suppliers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

