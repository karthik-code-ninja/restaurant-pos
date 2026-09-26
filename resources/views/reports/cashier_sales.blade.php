@extends('layouts.app')

@section('title', 'Cashier-wise Sales Report')
@section('page_title', '11. Cashier-wise Sales Report')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('reports.cashier') }}" class="flex items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <span class="text-slate-400 text-xs">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">Filter</button>
        </form>

        <button onclick="window.print()" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5 transition">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Active Cashiers</p>
            <p class="text-xl font-black text-slate-900 mt-1">{{ $report->count() }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Total Bills Billed</p>
            <p class="text-xl font-black text-slate-900 mt-1">{{ $report->sum('total_bills') }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Total Discounts Given</p>
            <p class="text-xl font-black text-rose-600 mt-1">{{ $currency }}{{ number_format($report->sum('total_discount'), 2) }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Total Revenue Collected</p>
            <p class="text-xl font-black text-emerald-600 mt-1">{{ $currency }}{{ number_format($report->sum('total_sales'), 2) }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Cashier Name</th>
                        <th class="py-3 px-4">Email</th>
                        <th class="py-3 px-4 text-center">Bills Count</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4 text-right">Discounts</th>
                        <th class="py-3 px-4 text-right">Tax Collected</th>
                        <th class="py-3 px-4 text-right">Grand Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-bold text-slate-900">
                            {{ $row->cashier?->name ?? 'Unknown / Deleted User' }}
                        </td>
                        <td class="py-3 px-4 text-slate-500">{{ $row->cashier?->email ?? '-' }}</td>
                        <td class="py-3 px-4 text-center font-bold text-slate-700">{{ $row->total_bills }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($row->total_subtotal, 2) }}</td>
                        <td class="py-3 px-4 text-right text-rose-600 font-medium">-{{ $currency }}{{ number_format($row->total_discount, 2) }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($row->total_tax, 2) }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">{{ $currency }}{{ number_format($row->total_sales, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No cashier billing records found for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
