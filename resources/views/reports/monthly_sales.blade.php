@extends('layouts.app')

@section('title', 'Monthly Sales Report')
@section('page_title', '2. Monthly Sales Report')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
        <form method="GET" action="{{ route('reports.monthly') }}" class="flex items-center gap-2">
            <label class="text-xs font-semibold text-slate-600">Select Year:</label>
            <select name="year" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs font-bold" onchange="this.form.submit()">
                @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
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
                        <th class="py-3 px-4">Month</th>
                        <th class="py-3 px-4 text-center">Total Bills</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4 text-right">Discounts</th>
                        <th class="py-3 px-4 text-right">GST Total</th>
                        <th class="py-3 px-4 text-right">Grand Total Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report as $row)
                    @php
                        $monthName = DateTime::createFromFormat('!m', $row->sale_month)->format('F');
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $monthName }} {{ $year }}</td>
                        <td class="py-3 px-4 text-center font-bold text-slate-700">{{ $row->total_bills }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($row->total_subtotal, 2) }}</td>
                        <td class="py-3 px-4 text-right text-rose-600 font-medium">-{{ $currency }}{{ number_format($row->total_discount, 2) }}</td>
                        <td class="py-3 px-4 text-right text-slate-700 font-semibold">{{ $currency }}{{ number_format($row->total_tax, 2) }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">{{ $currency }}{{ number_format($row->total_sales, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No sales recorded for {{ $year }}.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
