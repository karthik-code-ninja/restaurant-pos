@extends('layouts.app')

@section('title', 'Food-wise Sales Report')
@section('page_title', '3. Food-wise Sales Report')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('reports.food') }}" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search food name or code..." class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs w-48">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <span class="text-slate-400 text-xs">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">Filter</button>
        </form>

        <button onclick="window.print()" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5">
            <i class="fa-solid fa-print"></i> Print
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Item Code</th>
                        <th class="py-3 px-4">Food / Item Name</th>
                        <th class="py-3 px-4 text-right">Avg Unit Rate</th>
                        <th class="py-3 px-4 text-right">Total Qty Sold</th>
                        <th class="py-3 px-4 text-right">Tax Collected</th>
                        <th class="py-3 px-4 text-right">Total Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ $row->food_code }}</td>
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $row->item_name }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($row->avg_price, 2) }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-800">{{ (float) $row->total_qty }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($row->total_tax, 2) }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">{{ $currency }}{{ number_format($row->total_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No food item sales found in this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $report->links() }}
        </div>
    </div>
</div>
@endsection
