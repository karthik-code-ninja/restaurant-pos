@extends('layouts.app')

@section('title', 'Cancelled Bills Report')
@section('page_title', '8. Cancelled & Voided Bills Audit Report')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('reports.cancelled') }}" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoice #..." class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs w-44">
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
                        <th class="py-3 px-4">Invoice #</th>
                        <th class="py-3 px-4">Created Date</th>
                        <th class="py-3 px-4">Cancelled Date</th>
                        <th class="py-3 px-4">Table / Counter</th>
                        <th class="py-3 px-4 text-right">Bill Amount</th>
                        <th class="py-3 px-4">Cancelled By</th>
                        <th class="py-3 px-4">Reason Recorded</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($bills as $bill)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-mono font-bold text-rose-700">{{ $bill->invoice_number }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $bill->created_at->format('d/m/Y h:i A') }}</td>
                        <td class="py-3 px-4 text-rose-600 font-semibold">{{ $bill->cancelled_at ? $bill->cancelled_at->format('d/m/Y h:i A') : '-' }}</td>
                        <td class="py-3 px-4 text-slate-700">{{ $bill->table ? 'Table ' . $bill->table->table_number : 'Counter' }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">{{ $currency }}{{ number_format($bill->grand_total, 2) }}</td>
                        <td class="py-3 px-4 text-slate-600 font-medium">{{ $bill->cancelledByUser?->name ?? 'Admin' }}</td>
                        <td class="py-3 px-4 text-slate-600 max-w-xs">{{ $bill->cancellation_reason ?: 'No reason stated' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No cancelled bills recorded in this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $bills->links() }}
        </div>
    </div>
</div>
@endsection
