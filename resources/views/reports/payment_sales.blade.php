@extends('layouts.app')

@section('title', 'Payment-wise Sales Report')
@section('page_title', '6. Payment-wise Sales Report')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('reports.payment') }}" class="flex items-center gap-2">
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
                        <th class="py-3 px-4">Payment Method</th>
                        <th class="py-3 px-4 text-center">Transactions Count</th>
                        <th class="py-3 px-4 text-right">Total Amount Received</th>
                        <th class="py-3 px-4 text-right">Payment Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $grandTotalPayments = max(1, $report->sum('total_amount'));
                    @endphp
                    @forelse($report as $row)
                    @php
                        $share = round(($row->total_amount / $grandTotalPayments) * 100, 1);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-bold uppercase text-slate-900 text-sm">
                            <span class="px-2.5 py-1 rounded-lg font-bold
                                {{ $row->payment_method === 'cash' ? 'bg-emerald-100 text-emerald-800' : ($row->payment_method === 'upi' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ $row->payment_method }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-slate-700">{{ $row->count }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">{{ $currency }}{{ number_format($row->total_amount, 2) }}</td>
                        <td class="py-3 px-4 text-right font-bold text-slate-600">
                            <span class="inline-block px-2 py-0.5 rounded bg-slate-100">{{ $share }}%</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="py-8 text-center text-slate-400">No payment records found for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
