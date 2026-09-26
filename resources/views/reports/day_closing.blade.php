@extends('layouts.app')

@section('title', 'Day Closing Report')
@section('page_title', '12. Register & Day Closing Report')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('reports.day-closing') }}" class="flex items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <span class="text-slate-400 text-xs">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">Filter</button>
        </form>

        <div class="flex items-center gap-2">
            <a href="{{ route('dayclosing.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold text-slate-700 flex items-center gap-1.5 transition">
                <i class="fa-solid fa-cash-register"></i> Day Closing Terminal
            </a>
            <button onclick="window.print()" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5 transition">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Session Date / Period</th>
                        <th class="py-3 px-4">Opened By</th>
                        <th class="py-3 px-4">Closed By</th>
                        <th class="py-3 px-4 text-right">Opening Float</th>
                        <th class="py-3 px-4 text-right">Cash Sales</th>
                        <th class="py-3 px-4 text-right">Digital (UPI/Card)</th>
                        <th class="py-3 px-4 text-right">Total Net Sales</th>
                        <th class="py-3 px-4 text-right">Expected Drawer</th>
                        <th class="py-3 px-4 text-right">Actual Counted</th>
                        <th class="py-3 px-4 text-right">Variance</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($closings as $closing)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <p class="font-bold text-slate-900">{{ $closing->opened_at->format('d M Y') }}</p>
                            <p class="text-[10px] text-slate-400">
                                {{ $closing->opened_at->format('h:i A') }} - {{ $closing->closed_at ? $closing->closed_at->format('h:i A') : 'Active' }}
                            </p>
                        </td>
                        <td class="py-3 px-4 text-slate-700 font-medium">{{ $closing->openedByUser?->name ?? 'System' }}</td>
                        <td class="py-3 px-4 text-slate-700 font-medium">{{ $closing->closedByUser?->name ?? '-' }}</td>
                        <td class="py-3 px-4 text-right text-slate-600 font-mono">{{ $currency }}{{ number_format($closing->opening_balance, 2) }}</td>
                        <td class="py-3 px-4 text-right text-emerald-600 font-semibold font-mono">{{ $currency }}{{ number_format($closing->total_sales_cash, 2) }}</td>
                        <td class="py-3 px-4 text-right text-sky-600 font-medium font-mono">{{ $currency }}{{ number_format($closing->total_sales_upi + $closing->total_sales_card, 2) }}</td>
                        <td class="py-3 px-4 text-right font-bold text-slate-900 font-mono">{{ $currency }}{{ number_format($closing->total_sales_amount, 2) }}</td>
                        <td class="py-3 px-4 text-right font-medium text-slate-700 font-mono">{{ $currency }}{{ number_format($closing->expected_cash_in_drawer, 2) }}</td>
                        <td class="py-3 px-4 text-right font-bold text-slate-900 font-mono">
                            {{ $closing->actual_cash_in_drawer !== null ? $currency . number_format($closing->actual_cash_in_drawer, 2) : '-' }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold font-mono">
                            @if($closing->cash_difference === null)
                                <span class="text-slate-400">-</span>
                            @elseif($closing->cash_difference == 0)
                                <span class="text-emerald-600">₹0.00</span>
                            @elseif($closing->cash_difference < 0)
                                <span class="text-rose-600 font-black">-{{ $currency }}{{ number_format(abs($closing->cash_difference), 2) }}</span>
                            @else
                                <span class="text-amber-600 font-black">+{{ $currency }}{{ number_format($closing->cash_difference, 2) }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($closing->status === 'closed')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">Closed</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 animate-pulse">Active Session</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="py-8 text-center text-slate-400">No register closing sessions found for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $closings->links() }}
        </div>
    </div>
</div>
@endsection
