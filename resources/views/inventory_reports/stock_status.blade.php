@extends('layouts.app')

@section('title', 'Stock Status')
@section('page_title', 'Stock Status Report (Shortcut: 6)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded">Shortcut: 6</span>
                <h1 class="text-xl font-bold text-slate-800">Inventory Stock Status & Valuation</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Real-time valuation, on-hand physical stock, and reorder warnings</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print Stock Sheet</span>
            </button>
        </div>
    </div>

    <!-- Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Total Inventory Value</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $currency }} {{ number_format($totalStockValue, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Based on latest purchase cost</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Total Stock Items</div>
            <div class="text-2xl font-black text-blue-700 mt-1">{{ $foods->count() + $inventoryItems->count() }} SKUs</div>
            <div class="text-[11px] text-slate-400 mt-1">Tracked food & raw materials</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Low Stock Alerts</div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ $lowStockCount }} Items</div>
            <div class="text-[11px] text-amber-600 mt-1 font-bold">Needs restock attention</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('inventory-reports.stock-status') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-end gap-3 no-print">
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Category</label>
            <select name="category_id" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Stock Level</label>
            <select name="filter" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
                <option value="all">All Items</option>
                <option value="low_stock" {{ $filter === 'low_stock' ? 'selected' : '' }}>Low Stock Alerts (&le; 10)</option>
                <option value="out_of_stock" {{ $filter === 'out_of_stock' ? 'selected' : '' }}>Out of Stock (0)</option>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-600 mb-1">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by item name, barcode, SKU..." class="w-full text-xs border border-slate-300 rounded px-2.5 py-1.5">
        </div>
        <button type="submit" class="px-4 py-1.5 bg-slate-800 text-white text-xs font-bold rounded shadow">Filter</button>
        <a href="{{ route('inventory-reports.stock-status') }}" class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-bold rounded">Reset</a>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Code / SKU</th>
                        <th class="py-3 px-4">Item Name</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4 text-right">Cost Price (₹)</th>
                        <th class="py-3 px-4 text-right">Selling Price (₹)</th>
                        <th class="py-3 px-4 text-right">Current Stock</th>
                        <th class="py-3 px-4 text-right">Stock Valuation</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    <!-- Food items -->
                    @foreach($foods as $f)
                    @php
                        $cost = $f->cost_price ?: ($f->price * 0.5);
                        $valuation = $cost * ($f->current_stock ?? 0);
                    @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-2.5 px-4 font-mono font-bold text-slate-600">{{ $f->code }}</td>
                        <td class="py-2.5 px-4 font-bold text-slate-800">
                            {{ $f->name }}
                            @if($f->barcode)
                            <span class="block text-[10px] text-slate-400 font-mono">Barcode: {{ $f->barcode }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-slate-500">{{ $f->category?->name ?? 'Menu' }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-slate-700">{{ number_format($cost, 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-slate-900 font-bold">{{ number_format($f->price, 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">
                            {{ number_format($f->current_stock ?? 0, 1) }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-blue-700">
                            {{ $currency }} {{ number_format($valuation, 2) }}
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            @if(($f->current_stock ?? 0) <= 0)
                                <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded-full font-bold text-[10px]">OUT OF STOCK</span>
                            @elseif(($f->current_stock ?? 0) <= 10)
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full font-bold text-[10px]">LOW STOCK</span>
                            @else
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[10px]">IN STOCK</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    <!-- Inventory Items -->
                    @foreach($inventoryItems as $inv)
                    @php
                        $cost = $inv->cost_price ?: 100;
                        $valuation = $cost * ($inv->current_stock ?? 0);
                    @endphp
                    <tr class="hover:bg-slate-50 transition bg-amber-50/20">
                        <td class="py-2.5 px-4 font-mono font-bold text-amber-800">INV-{{ $inv->id }}</td>
                        <td class="py-2.5 px-4 font-bold text-slate-800">
                            {{ $inv->name }}
                            <span class="block text-[10px] text-amber-600 font-semibold">[Raw Material]</span>
                        </td>
                        <td class="py-2.5 px-4 text-slate-500">Inventory Material</td>
                        <td class="py-2.5 px-4 text-right font-mono text-slate-700">{{ number_format($cost, 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-slate-400">-</td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">
                            {{ number_format($inv->current_stock ?? 0, 1) }} {{ $inv->unit }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-blue-700">
                            {{ $currency }} {{ number_format($valuation, 2) }}
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            @if(($inv->current_stock ?? 0) <= 0)
                                <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded-full font-bold text-[10px]">OUT OF STOCK</span>
                            @elseif(($inv->current_stock ?? 0) <= ($inv->min_stock_alert ?? 5))
                                <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded-full font-bold text-[10px]">LOW STOCK</span>
                            @else
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[10px]">IN STOCK</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

