@extends('layouts.app')

@section('title', 'Purchase Register')
@section('page_title', 'Purchase Register (Shortcut: j)')

@section('content')
<div class="space-y-6">
    <!-- Top Action & Filter Bar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: j</span>
                <h1 class="text-xl font-bold text-slate-800">Purchase Invoices & Register</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Track vendor purchases, inward inventory stocks, and GST tax credit</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition flex items-center gap-1.5">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Quick Access Hub</span>
            </a>
            <a href="{{ route('purchases.create') }}" class="px-4 py-2 bg-gradient-to-r from-red-700 to-amber-700 hover:from-red-600 hover:to-amber-600 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>New Purchase (c)</span>
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Total Purchases</div>
            <div class="text-2xl font-black text-slate-800 mt-1">{{ $currency }} {{ number_format($totalPurchasesAmount, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">In selected period</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Input Tax Credit (ITC)</div>
            <div class="text-2xl font-black text-blue-600 mt-1">{{ $currency }} {{ number_format($totalTaxAmount, 2) }}</div>
            <div class="text-[11px] text-blue-400 mt-1">Claimable GST</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Total Paid</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $currency }} {{ number_format($totalPaidAmount, 2) }}</div>
            <div class="text-[11px] text-emerald-500 mt-1">Settled vendor bills</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Outstanding Due</div>
            <div class="text-2xl font-black text-red-600 mt-1">{{ $currency }} {{ number_format($totalDueAmount, 2) }}</div>
            <div class="text-[11px] text-red-500 mt-1">Accounts payable</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('purchases.index') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">From Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5 focus:outline-none focus:border-red-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">To Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5 focus:outline-none focus:border-red-500">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Supplier</label>
            <select name="supplier_id" class="text-xs border border-slate-300 rounded px-2.5 py-1.5 focus:outline-none focus:border-red-500">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" {{ $supplierId == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-600 mb-1">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by Purchase #, Invoice #, Supplier..." class="w-full text-xs border border-slate-300 rounded px-2.5 py-1.5 focus:outline-none focus:border-red-500">
        </div>
        <button type="submit" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded shadow transition">
            Filter
        </button>
        <a href="{{ route('purchases.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded transition">
            Reset
        </a>
    </form>

    <!-- Purchases Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Purchase #</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Supplier Name</th>
                        <th class="py-3 px-4">Vendor Bill #</th>
                        <th class="py-3 px-4 text-right">Taxable</th>
                        <th class="py-3 px-4 text-right">Tax (GST)</th>
                        <th class="py-3 px-4 text-right">Grand Total</th>
                        <th class="py-3 px-4 text-right">Paid</th>
                        <th class="py-3 px-4 text-right">Due</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($purchases as $p)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 font-mono font-bold text-red-800">
                            <a href="{{ route('purchases.show', $p) }}" class="hover:underline">{{ $p->purchase_no }}</a>
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $p->purchase_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 font-bold text-slate-800">{{ $p->supplier_name }}</td>
                        <td class="py-3 px-4 text-slate-500 font-mono">{{ $p->invoice_no ?: '-' }}</td>
                        <td class="py-3 px-4 text-right font-mono">{{ number_format($p->subtotal, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-blue-600">{{ number_format($p->tax_amount, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">{{ number_format($p->grand_total, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-600">{{ number_format($p->paid_amount, 2) }}</td>
                        <td class="py-3 px-4 text-right font-mono text-red-600 font-bold">{{ number_format($p->due_amount, 2) }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($p->payment_status === 'paid')
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded-full text-[10px]">PAID</span>
                            @elseif($p->payment_status === 'partial')
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-800 font-bold rounded-full text-[10px]">PARTIAL</span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-800 font-bold rounded-full text-[10px]">DUE</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <a href="{{ route('purchases.show', $p) }}" title="View Invoice" class="p-1 text-slate-600 hover:text-blue-600 transition">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <form action="{{ route('purchases.destroy', $p) }}" method="POST" onsubmit="return confirm('Cancel this purchase and revert stock?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Delete Purchase" class="p-1 text-slate-400 hover:text-red-600 transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-inbox text-3xl mb-2"></i>
                            <p class="font-medium">No purchase invoices found for this range.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $purchases->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

