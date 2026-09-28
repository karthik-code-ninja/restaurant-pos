@extends('layouts.app')

@section('title', 'Daily Sales Report')
@section('page_title', '1. Daily Sales Report')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
    <!-- Clean Print-Only Report Header -->
    <div class="print-only mb-6 pb-3 border-b-2 border-slate-900">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900">{{ \App\Models\Setting::get('restaurant_name', 'RestroPOS') }}</h1>
                <p class="text-xs text-slate-700">{{ \App\Models\Setting::get('restaurant_address', '') }} | Phone: {{ \App\Models\Setting::get('restaurant_contact', '') }}</p>
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-bold uppercase rounded">Daily Sales Report</span>
                <p class="text-xs font-medium text-slate-600 mt-1">Period: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                <p class="text-[11px] text-slate-500">Generated: {{ date('d/m/Y h:i A') }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 no-print">
        <form method="GET" action="{{ route('reports.daily') }}" class="flex items-center gap-2">
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
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4 text-center">Bills Count</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4 text-right">Discount</th>
                        <th class="py-3 px-4 text-right">GST Total</th>
                        <th class="py-3 px-4 text-right">Net Grand Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-bold text-slate-900">{{ date('d M Y (D)', strtotime($row->sale_date)) }}</td>
                        <td class="py-3 px-4 text-center font-bold text-slate-700">{{ $row->total_bills }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($row->total_subtotal, 2) }}</td>
                        <td class="py-3 px-4 text-right text-rose-600 font-medium">-{{ $currency }}{{ number_format($row->total_discount, 2) }}</td>
                        <td class="py-3 px-4 text-right text-slate-700 font-semibold">{{ $currency }}{{ number_format($row->total_tax, 2) }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">{{ $currency }}{{ number_format($row->total_sales, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No completed sales found in this period.</td>
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
