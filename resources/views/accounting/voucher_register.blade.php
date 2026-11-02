@extends('layouts.app')

@section('title', 'Voucher Register')
@section('page_title', 'Day-wise Voucher Register (Shortcut: 3)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded">Shortcut: 3</span>
                <h1 class="text-xl font-bold text-slate-800">Accounting Voucher Register</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Chronological audit listing of all sales, purchases, receipts, payments, and contra vouchers</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print Register</span>
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('accounting.voucher-register') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-end gap-3 no-print">
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Voucher Type</label>
            <select name="type" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
                <option value="">All Voucher Types</option>
                <option value="receipt" {{ $voucherType === 'receipt' ? 'selected' : '' }}>Receipt</option>
                <option value="payment" {{ $voucherType === 'payment' ? 'selected' : '' }}>Payment</option>
                <option value="purchase" {{ $voucherType === 'purchase' ? 'selected' : '' }}>Purchase</option>
                <option value="purchase_return" {{ $voucherType === 'purchase_return' ? 'selected' : '' }}>Purchase Return</option>
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
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-600 mb-1">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search voucher #, party name, ref..." class="w-full text-xs border border-slate-300 rounded px-2.5 py-1.5">
        </div>
        <button type="submit" class="px-4 py-1.5 bg-slate-800 text-white text-xs font-bold rounded shadow">Filter</button>
        <a href="{{ route('accounting.voucher-register') }}" class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-bold rounded">Reset</a>
    </form>

    <!-- Vouchers Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Voucher #</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4">Party / Account</th>
                        <th class="py-3 px-4">Debit Ledger</th>
                        <th class="py-3 px-4">Credit Ledger</th>
                        <th class="py-3 px-4 text-right">Amount</th>
                        <th class="py-3 px-4">Narration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($vouchers as $v)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-mono font-bold text-blue-800">{{ $v->voucher_no }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $v->voucher_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $v->voucher_type === 'receipt' ? 'bg-emerald-100 text-emerald-800' : ($v->voucher_type === 'payment' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ $v->voucher_type }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-800">{{ $v->party_name ?: '-' }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $v->debit_ledger ?: '-' }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $v->credit_ledger ?: '-' }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">{{ $currency }} {{ number_format($v->amount, 2) }}</td>
                        <td class="py-3 px-4 text-slate-500 truncate max-w-xs">{{ $v->narration }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-book text-3xl mb-2"></i>
                            <p class="font-medium">No accounting vouchers found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vouchers->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $vouchers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

