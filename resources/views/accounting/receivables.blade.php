@extends('layouts.app')

@section('title', 'Accounts Receivable')
@section('page_title', 'Receivables / Customer Outstanding (Shortcut: e)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: e</span>
                <h1 class="text-xl font-bold text-slate-800">Accounts Receivable (Sundry Debtors)</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Track outstanding credit balances due from customers and collect payments</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <a href="{{ route('accounting.receipt') }}" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-hand-holding-dollar"></i>
                <span>Collect Receipt (b)</span>
            </a>
        </div>
    </div>

    <!-- Summary Banner -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-r from-red-500 to-rose-600 text-white p-5 rounded-xl shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-red-100">Total Customer Receivables Due</div>
                <div class="text-3xl font-black mt-1">{{ $currency }} {{ number_format($totalReceivable, 2) }}</div>
            </div>
            <i class="fa-solid fa-users text-4xl opacity-20"></i>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Customers with Pending Due</div>
                <div class="text-3xl font-black text-slate-800 mt-1">{{ $totalCustomersWithDue }} Accounts</div>
            </div>
            <i class="fa-solid fa-file-invoice text-4xl text-slate-200"></i>
        </div>
    </div>

    <!-- Receivables Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex justify-between items-center">
            <form method="GET" action="{{ route('accounting.receivables') }}" class="flex items-center gap-2 max-w-sm w-full">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search customer by name or phone..." class="w-full text-xs border border-slate-300 rounded px-3 py-1.5 focus:outline-none focus:border-red-500">
                <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded">Search</button>
            </form>
            <button onclick="window.print()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded flex items-center gap-1">
                <i class="fa-solid fa-print"></i> Print Aging Report
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Customer Name</th>
                        <th class="py-3 px-4">Contact Phone</th>
                        <th class="py-3 px-4">Credit Limit</th>
                        <th class="py-3 px-4 text-right">Opening Balance</th>
                        <th class="py-3 px-4 text-right">Current Outstanding (Due)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($customers as $c)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-bold text-slate-800">
                            <a href="{{ route('accounting.ledger-statement', ['type' => 'customer', 'id' => $c->id]) }}" class="hover:underline text-blue-700">{{ $c->name }}</a>
                        </td>
                        <td class="py-3 px-4 text-slate-600 font-mono">{{ $c->phone ?: '-' }}</td>
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $currency }} {{ number_format($c->credit_limit ?? 10000, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-slate-600">{{ $currency }} {{ number_format($c->opening_balance, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-red-600 text-sm">
                            {{ $currency }} {{ number_format($c->balance, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($c->balance > 0)
                                <span class="px-2 py-0.5 bg-red-100 text-red-800 font-bold rounded-full text-[10px]">DUE</span>
                            @else
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded-full text-[10px]">CLEAR</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('accounting.ledger-statement', ['type' => 'customer', 'id' => $c->id]) }}" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-bold" title="View Statement">
                                    Statement
                                </a>
                                @if($c->balance > 0)
                                <a href="{{ route('accounting.receipt') }}" class="px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[11px] font-bold" title="Collect Payment">
                                    Receive
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-users text-3xl mb-2"></i>
                            <p class="font-medium">No customers found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $customers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

