@extends('layouts.app')

@section('title', 'Stock Movement Summary')
@section('page_title', 'Stock Movement Summary (Shortcut: k)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: k</span>
                <h1 class="text-xl font-bold text-slate-800">Stock Movement Summary (Item Ledger)</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Comprehensive Opening Stock, Inward, Outward, and Closing Stock reconciliation per item</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print Summary</span>
            </button>
        </div>
    </div>

    <!-- Date Filter -->
    <form method="GET" action="{{ route('inventory-reports.movement-summary') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center gap-3 no-print">
        <span class="text-xs font-bold text-slate-600">Reconciliation Period:</span>
        <input type="date" name="start_date" value="{{ $startDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
        <span class="text-xs text-slate-400">to</span>
        <input type="date" name="end_date" value="{{ $endDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
        <button type="submit" class="px-4 py-1.5 bg-slate-800 text-white text-xs font-bold rounded">Apply Filter</button>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Code</th>
                        <th class="py-3 px-4">Item Name</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4 text-right">Opening Qty</th>
                        <th class="py-3 px-4 text-right text-emerald-700">Inward (+)</th>
                        <th class="py-3 px-4 text-right text-red-700">Outward (-)</th>
                        <th class="py-3 px-4 text-right font-black">Closing Stock</th>
                        <th class="py-3 px-4 text-right">Cost Rate</th>
                        <th class="py-3 px-4 text-right">Total Valuation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($summary as $row)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-2.5 px-4 font-mono font-bold text-slate-500">{{ $row['code'] }}</td>
                        <td class="py-2.5 px-4 font-bold text-slate-800">{{ $row['name'] }}</td>
                        <td class="py-2.5 px-4 text-slate-500">{{ $row['category'] }}</td>
                        <td class="py-2.5 px-4 text-right font-mono">{{ number_format($row['opening_stock'], 1) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-emerald-700 font-bold">{{ number_format($row['inward_qty'], 1) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-red-700 font-bold">{{ number_format($row['outward_qty'], 1) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono font-black text-slate-900">{{ number_format($row['closing_stock'], 1) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-slate-600">{{ number_format($row['rate'], 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-blue-700">{{ $currency }} {{ number_format($row['valuation'], 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-black text-xs border-t-2 border-slate-800 text-slate-900">
                        <td colspan="8" class="py-3 px-4 text-right uppercase tracking-wider">Total Stock Valuation:</td>
                        <td class="py-3 px-4 text-right font-mono text-blue-900 text-sm">
                            {{ $currency }} {{ number_format($totalValuation, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

