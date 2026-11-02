@extends('layouts.app')

@section('title', 'Business View (P&L)')
@section('page_title', 'Business View - Trading & Profit/Loss (Shortcut: 4)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm no-print">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded">Shortcut: 4</span>
                <h1 class="text-xl font-bold text-slate-800">Executive Business View & Financial Health</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Comprehensive Trading Account, Cost of Goods Sold (COGS), and Net Profit/Loss statement</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print P&L Statement</span>
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <form method="GET" action="{{ route('accounting.business-view') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3 no-print">
        <span class="text-xs font-bold text-slate-600">Select Financial Period:</span>
        <input type="date" name="start_date" value="{{ $startDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
        <span class="text-xs text-slate-400">to</span>
        <input type="date" name="end_date" value="{{ $endDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
        <button type="submit" class="px-4 py-1.5 bg-slate-800 text-white text-xs font-bold rounded">Update View</button>
    </form>

    <!-- Top KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Total Sales Revenue</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $currency }} {{ number_format($salesRevenue, 2) }}</div>
            <div class="text-[11px] text-emerald-600 mt-1 font-bold">100% Topline</div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Cost of Goods Sold (COGS)</div>
            <div class="text-2xl font-black text-amber-700 mt-1">{{ $currency }} {{ number_format($costOfGoodsSold, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Food & Raw Material Cost</div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Gross Profit</div>
            <div class="text-2xl font-black text-sky-700 mt-1">{{ $currency }} {{ number_format($grossProfit, 2) }}</div>
            <div class="text-[11px] text-sky-600 mt-1 font-bold">Margin: {{ $grossMarginPercent }}%</div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Net Profit / Surplus</div>
            <div class="text-2xl font-black {{ $netProfit >= 0 ? 'text-emerald-700' : 'text-red-700' }} mt-1">{{ $currency }} {{ number_format($netProfit, 2) }}</div>
            <div class="text-[11px] {{ $netProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }} mt-1 font-bold">Net Margin: {{ $netMarginPercent }}%</div>
        </div>
    </div>

    <!-- Detailed Trading & P&L Statement -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- TRADING ACCOUNT -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b pb-2 flex items-center justify-between">
                <span>1. Trading Account (Direct Production)</span>
                <span class="text-sky-700 font-mono text-xs">Gross Margin {{ $grossMarginPercent }}%</span>
            </h2>
            <div class="space-y-2.5 text-xs">
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="font-semibold text-slate-700">Gross Sales Invoiced:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $currency }} {{ number_format($salesRevenue, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 text-slate-600 pl-3">
                    <span>(+) Opening Stock Value:</span>
                    <span class="font-mono">{{ $currency }} {{ number_format($openingStock, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 text-slate-600 pl-3">
                    <span>(+) Inward Purchases During Period:</span>
                    <span class="font-mono">{{ $currency }} {{ number_format($purchases, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 text-slate-600 pl-3">
                    <span>(-) Closing Stock Value on Hand:</span>
                    <span class="font-mono text-emerald-700">- {{ $currency }} {{ number_format($closingStock, 2) }}</span>
                </div>
                <div class="flex justify-between py-1.5 border-t border-slate-200 bg-amber-50 px-2 rounded">
                    <span class="font-bold text-amber-900">Total Cost of Goods Sold (COGS):</span>
                    <span class="font-mono font-bold text-amber-900">{{ $currency }} {{ number_format($costOfGoodsSold, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-t-2 border-sky-600 bg-sky-50 px-2 rounded font-black text-sm">
                    <span class="text-sky-950">(=) Gross Profit:</span>
                    <span class="font-mono text-sky-800">{{ $currency }} {{ number_format($grossProfit, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- PROFIT & LOSS ACCOUNT -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-4">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b pb-2 flex items-center justify-between">
                <span>2. Profit & Loss Account (Overheads)</span>
                <span class="text-emerald-700 font-mono text-xs">Net Margin {{ $netMarginPercent }}%</span>
            </h2>
            <div class="space-y-2.5 text-xs">
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="font-semibold text-slate-700">Gross Profit Brought Down:</span>
                    <span class="font-mono font-bold text-slate-900">{{ $currency }} {{ number_format($grossProfit, 2) }}</span>
                </div>
                <div class="py-1">
                    <span class="font-bold text-slate-500 uppercase text-[10px]">Operating Expenses Breakdown:</span>
                    <div class="space-y-1.5 pl-3 mt-1.5">
                        @forelse($expensesByCategory as $exp)
                        <div class="flex justify-between text-slate-600">
                            <span>{{ $exp->category?->name ?? 'General Expenses' }}:</span>
                            <span class="font-mono text-red-600">{{ $currency }} {{ number_format($exp->total, 2) }}</span>
                        </div>
                        @empty
                        <div class="flex justify-between text-slate-600">
                            <span>General Operating Expenses:</span>
                            <span class="font-mono text-red-600">{{ $currency }} {{ number_format($operatingExpenses, 2) }}</span>
                        </div>
                        @endforelse
                    </div>
                </div>
                <div class="flex justify-between py-1.5 border-t border-slate-200 bg-red-50 px-2 rounded">
                    <span class="font-bold text-red-900">Total Operating Expenses:</span>
                    <span class="font-mono font-bold text-red-900">{{ $currency }} {{ number_format($operatingExpenses, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-t-2 border-emerald-600 bg-emerald-50 px-2 rounded font-black text-sm">
                    <span class="text-emerald-950">(=) Net Profit / Surplus:</span>
                    <span class="font-mono text-emerald-800">{{ $currency }} {{ number_format($netProfit, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

