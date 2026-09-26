@extends('layouts.app')

@section('title', 'Stock Report')
@section('page_title', '10. Inventory Stock Report')

@section('content')
<div class="space-y-6">
    <!-- Header with actions -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h2 class="text-sm font-bold text-slate-800">Current Stock Levels & Material Valuation</h2>
            <p class="text-xs text-slate-500">Real-time raw material balances and minimum reorder thresholds</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('inventory.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold text-slate-700 flex items-center gap-1.5 transition">
                <i class="fa-solid fa-boxes-stacked"></i> Manage Inventory
            </a>
            <button onclick="window.print()" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5 transition">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Total Items</p>
            <p class="text-xl font-black text-slate-900 mt-1">{{ $items->count() }}</p>
        </div>
        <div class="bg-white p-4 rounded-xl border {{ $lowStockItems->count() > 0 ? 'border-amber-300 bg-amber-50/30' : 'border-slate-200' }} shadow-sm">
            <p class="text-xs font-medium {{ $lowStockItems->count() > 0 ? 'text-amber-700 font-bold' : 'text-slate-400' }}">Low Stock Alerts</p>
            <p class="text-xl font-black {{ $lowStockItems->count() > 0 ? 'text-amber-600' : 'text-slate-900' }} mt-1">{{ $lowStockItems->count() }} Items</p>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Total Stock Valuation</p>
            <p class="text-xl font-black text-emerald-600 mt-1">₹{{ number_format($items->sum(fn($i) => $i->current_stock * $i->unit_cost), 2) }}</p>
        </div>
    </div>

    @if($lowStockItems->count() > 0)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-800 flex items-start gap-3">
        <i class="fa-solid fa-triangle-exclamation text-amber-500 text-sm mt-0.5"></i>
        <div>
            <p class="font-bold">Attention: {{ $lowStockItems->count() }} raw material(s) have reached or fallen below minimum alert levels!</p>
            <p class="text-amber-700 mt-0.5">Consider replenishing stock for: {{ $lowStockItems->pluck('name')->implode(', ') }}</p>
        </div>
    </div>
    @endif

    <!-- Stock Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Item Name</th>
                        <th class="py-3 px-4">Unit</th>
                        <th class="py-3 px-4 text-right">Current Stock</th>
                        <th class="py-3 px-4 text-right">Min Alert Level</th>
                        <th class="py-3 px-4 text-right">Unit Cost</th>
                        <th class="py-3 px-4 text-right">Total Valuation</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $item)
                    <tr class="hover:bg-slate-50 {{ $item->isLowStock() ? 'bg-amber-50/40' : '' }}">
                        <td class="py-3 px-4 font-bold text-slate-900">
                            {{ $item->name }}
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $item->unit }}</td>
                        <td class="py-3 px-4 text-right font-black {{ $item->isLowStock() ? 'text-amber-600' : 'text-slate-900' }} text-sm">
                            {{ number_format($item->current_stock, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right text-slate-500 font-medium">
                            {{ number_format($item->min_stock_alert, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right text-slate-600">
                            ₹{{ number_format($item->unit_cost, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-slate-800">
                            ₹{{ number_format($item->current_stock * $item->unit_cost, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($item->current_stock <= 0)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">Out of Stock</span>
                            @elseif($item->isLowStock())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">Low Stock</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Adequate</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No inventory items found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
