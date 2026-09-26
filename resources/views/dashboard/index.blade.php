@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Restaurant Overview & Analytics')

@section('content')
<div class="space-y-6">
    <!-- Date Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-slate-800">Sales Summary</h3>
            <p class="text-xs text-slate-500">Live transaction figures for selected date range</p>
        </div>
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            <span class="text-slate-400 text-xs">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold transition">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
            <a href="{{ route('dashboard') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-medium transition">Today</a>
        </form>
    </div>

    <!-- 1. Key Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Sales -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Sales</p>
                <h4 class="text-2xl font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($totalSales, 2) }}</h4>
                <p class="text-xs text-emerald-600 mt-1 font-medium"><i class="fa-solid fa-receipt mr-1"></i> {{ $totalBills }} Completed Bills</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
        </div>

        <!-- Cash Sales -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cash Sales</p>
                <h4 class="text-2xl font-black text-emerald-600 mt-1">{{ $currency }}{{ number_format($cashSales, 2) }}</h4>
                <p class="text-xs text-slate-400 mt-1">Cash drawer receipts</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- UPI / QR Sales -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">UPI / QR Sales</p>
                <h4 class="text-2xl font-black text-indigo-600 mt-1">{{ $currency }}{{ number_format($upiSales, 2) }}</h4>
                <p class="text-xs text-slate-400 mt-1">Digital payments</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-qrcode"></i>
            </div>
        </div>

        <!-- Card Sales -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Card Sales</p>
                <h4 class="text-2xl font-black text-blue-600 mt-1">{{ $currency }}{{ number_format($cardSales, 2) }}</h4>
                <p class="text-xs text-slate-400 mt-1">POS swipe/tap</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-credit-card"></i>
            </div>
        </div>
    </div>

    <!-- 2. Status Indicators -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-amber-200/80 bg-amber-50/20 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div>
                    <h5 class="text-lg font-bold text-slate-900">{{ $pendingBillsCount }}</h5>
                    <p class="text-xs text-slate-500 font-medium">Pending / Open Bills</p>
                </div>
            </div>
            <a href="{{ route('pos.index') }}" class="text-xs font-semibold text-orange-600 hover:underline">View in POS &rarr;</a>
        </div>

        <div class="bg-white p-4 rounded-xl border border-blue-200/80 bg-blue-50/20 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-pause"></i>
                </div>
                <div>
                    <h5 class="text-lg font-bold text-slate-900">{{ $heldBillsCount }}</h5>
                    <p class="text-xs text-slate-500 font-medium">Held Bills</p>
                </div>
            </div>
            <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800 font-medium">Active Cart</span>
        </div>

        <div class="bg-white p-4 rounded-xl border border-rose-200/80 bg-rose-50/20 shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <div>
                    <h5 class="text-lg font-bold text-slate-900">{{ $cancelledBillsCount }}</h5>
                    <p class="text-xs text-slate-500 font-medium">Cancelled Bills</p>
                </div>
            </div>
            <a href="{{ route('reports.cancelled') }}" class="text-xs font-semibold text-rose-600 hover:underline">Cancelled Report &rarr;</a>
        </div>
    </div>

    <!-- 3. Tables & Occupancy Status -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-800">Dining Table Status</h3>
                <p class="text-xs text-slate-500">Live overview of dining floor availability</p>
            </div>
            <a href="{{ route('tables.index') }}" class="text-xs font-semibold text-orange-600 hover:underline">Manage Tables &rarr;</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-center">
                <p class="text-xs text-slate-500 font-medium">Total Tables</p>
                <p class="text-xl font-black text-slate-800 mt-1">{{ $totalTables }}</p>
            </div>
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-center">
                <p class="text-xs text-emerald-700 font-medium">Available</p>
                <p class="text-xl font-black text-emerald-800 mt-1">{{ $availableTables }}</p>
            </div>
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-center">
                <p class="text-xs text-amber-700 font-medium">Occupied</p>
                <p class="text-xl font-black text-amber-800 mt-1">{{ $occupiedTables }}</p>
            </div>
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl text-center">
                <p class="text-xs text-blue-700 font-medium">Reserved</p>
                <p class="text-xl font-black text-blue-800 mt-1">{{ $reservedTables }}</p>
            </div>
        </div>
    </div>

    <!-- 4. Two Columns: Food-wise Sales Summary & Daily Trend -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Selling Foods -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Top Food Sales</h3>
                    <p class="text-xs text-slate-500">Fastest moving items in selected period</p>
                </div>
                <a href="{{ route('reports.food') }}" class="text-xs font-semibold text-orange-600 hover:underline">Full Report &rarr;</a>
            </div>

            @if($foodSalesSummary->isEmpty())
            <div class="py-8 text-center text-slate-400 text-xs">
                <i class="fa-solid fa-bowl-food text-2xl mb-2 text-slate-300 block"></i>
                No food sales recorded in this date range.
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
                            <th class="py-2">Item Code</th>
                            <th class="py-2">Food Name</th>
                            <th class="py-2 text-right">Qty</th>
                            <th class="py-2 text-right">Sales</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($foodSalesSummary as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2.5 font-mono text-slate-500 font-bold">{{ $item->food_code }}</td>
                            <td class="py-2.5 font-semibold text-slate-800">{{ $item->item_name }}</td>
                            <td class="py-2.5 text-right font-bold text-slate-700">{{ (float) $item->total_qty }}</td>
                            <td class="py-2.5 text-right font-black text-slate-900">{{ $currency }}{{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <!-- 7-Day Sales Trend -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Last 7 Days Sales Trend</h3>
                        <p class="text-xs text-slate-500">Daily revenue progression</p>
                    </div>
                    <span class="text-xs font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg">
                        Month: {{ $currency }}{{ number_format($monthlyTotalSales, 2) }}
                    </span>
                </div>

                <div class="space-y-3 pt-2">
                    @php
                        $maxSales = max(1, max(array_column($last7Days, 'sales')));
                    @endphp
                    @foreach($last7Days as $day)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-medium text-slate-600">{{ $day['date'] }}</span>
                            <span class="font-bold text-slate-900">{{ $currency }}{{ number_format($day['sales'], 2) }}</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-orange-500 to-amber-500 h-2 rounded-full transition-all duration-500" style="width: {{ ($day['sales'] / $maxSales) * 100 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Updated in real-time from settled bills</span>
                <a href="{{ route('reports.daily') }}" class="font-semibold text-orange-600 hover:underline">Daily Reports &rarr;</a>
            </div>
        </div>
    </div>

    <!-- 5. Recent Bills Activity -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-800">Recent Transactions</h3>
                <p class="text-xs text-slate-500">Latest 10 bills generated in restaurant</p>
            </div>
            <a href="{{ route('reports.daily') }}" class="text-xs font-semibold text-orange-600 hover:underline">View All &rarr;</a>
        </div>

        @if($recentBills->isEmpty())
        <div class="py-8 text-center text-slate-400 text-xs">
            <i class="fa-solid fa-receipt text-2xl mb-2 text-slate-300 block"></i>
            No bills generated yet. Go to POS to create the first bill!
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-400 uppercase font-semibold">
                        <th class="py-2.5">Invoice #</th>
                        <th class="py-2.5">Type</th>
                        <th class="py-2.5">Table</th>
                        <th class="py-2.5">Cashier</th>
                        <th class="py-2.5">Date & Time</th>
                        <th class="py-2.5 text-right">Grand Total</th>
                        <th class="py-2.5 text-center">Status</th>
                        <th class="py-2.5 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentBills as $bill)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 font-mono font-bold text-slate-900">{{ $bill->invoice_number }}</td>
                        <td class="py-3">
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $bill->order_type === 'table' ? 'bg-indigo-50 text-indigo-700' : 'bg-emerald-50 text-emerald-700' }}">
                                {{ ucfirst($bill->order_type) }}
                            </span>
                        </td>
                        <td class="py-3 font-medium text-slate-700">{{ $bill->table ? $bill->table->table_number : 'Counter' }}</td>
                        <td class="py-3 text-slate-600">{{ $bill->cashier?->name ?? 'Staff' }}</td>
                        <td class="py-3 text-slate-500">{{ $bill->created_at->format('d/m/Y h:i A') }}</td>
                        <td class="py-3 text-right font-black text-slate-900">{{ $currency }}{{ number_format($bill->grand_total, 2) }}</td>
                        <td class="py-3 text-center">
                            @if($bill->status === 'completed')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">Completed</span>
                            @elseif($bill->status === 'pending')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-800">Pending</span>
                            @elseif($bill->status === 'held')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-100 text-blue-800">Held</span>
                            @elseif($bill->status === 'draft')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-200 text-slate-700">Draft</span>
                            @elseif($bill->status === 'cancelled')
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-800">Cancelled</span>
                            @endif
                        </td>
                        <td class="py-3 text-center space-x-1">
                            <a href="{{ route('print.bill', ['bill' => $bill->id]) }}" target="_blank" title="Print Receipt" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded text-slate-700 text-xs font-semibold inline-block">
                                <i class="fa-solid fa-print"></i>
                            </a>
                            @if(in_array($bill->status, ['pending', 'held', 'draft']))
                            <a href="{{ route('pos.index', ['bill_id' => $bill->id]) }}" title="Resume in POS" class="px-2 py-1 bg-orange-100 hover:bg-orange-200 rounded text-orange-700 text-xs font-semibold inline-block">
                                <i class="fa-solid fa-play"></i>
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection
