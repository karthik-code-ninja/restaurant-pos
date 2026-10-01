@extends('layouts.app')

@section('title', 'Detailed Payment & Multi-Mode Sales Report')
@section('page_title', '6. Payment-wise & Multi-Mode Detailed Sales Report')

@section('content')
<div class="space-y-6">
    <!-- Clean Print-Only Report Header -->
    <div class="print-only mb-6 pb-4 border-b-2 border-slate-900">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900">{{ \App\Models\Setting::get('restaurant_name', 'RestroPOS') }}</h1>
                <p class="text-xs text-slate-700">{{ \App\Models\Setting::get('restaurant_address', '') }} | Contact: {{ \App\Models\Setting::get('restaurant_contact', '') }}</p>
                @if(\App\Models\Setting::get('restaurant_gstin'))
                    <p class="text-xs text-slate-700 font-semibold">GSTIN: {{ \App\Models\Setting::get('restaurant_gstin') }}</p>
                @endif
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-bold uppercase rounded">Payment & Multi-Mode Audit</span>
                <p class="text-xs font-medium text-slate-700 mt-1">Period: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                <p class="text-[11px] text-slate-500">Mode: {{ strtoupper($selectedMode) }} | Generated: {{ date('d/m/Y h:i A') }}</p>
            </div>
        </div>
    </div>

    <!-- Filter & Action Controls -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3 no-print">
        <form method="GET" action="{{ route('reports.payment') }}" class="flex flex-wrap items-center gap-3">
            <!-- Date Filters -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500"><i class="fa-solid fa-calendar mr-1"></i>From:</span>
                <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-800 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                <span class="text-slate-400 text-xs">to</span>
                <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-800 focus:ring-2 focus:ring-slate-900 focus:outline-none">
            </div>

            <!-- Mode Filter -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-500"><i class="fa-solid fa-wallet mr-1"></i>Payment Mode:</span>
                <select name="mode" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-800 bg-white font-medium focus:ring-2 focus:ring-slate-900 focus:outline-none">
                    <option value="all" {{ $selectedMode === 'all' ? 'selected' : '' }}>All Modes (Single & Multi-Mode)</option>
                    <option value="multimode" {{ $selectedMode === 'multimode' ? 'selected' : '' }}>Multi-Mode Only (Split: Cash + UPI/Card)</option>
                    <option value="cash" {{ $selectedMode === 'cash' ? 'selected' : '' }}>Cash (Includes Cash Split)</option>
                    <option value="upi" {{ $selectedMode === 'upi' ? 'selected' : '' }}>UPI (Includes UPI Split)</option>
                    <option value="card" {{ $selectedMode === 'card' ? 'selected' : '' }}>Card (Includes Card Split)</option>
                </select>
            </div>

            <!-- Search Filter -->
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search Invoice #, Phone, Ref..." class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-800 focus:ring-2 focus:ring-slate-900 focus:outline-none">
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                @if($selectedMode !== 'all' || !empty($search) || $startDate !== Carbon\Carbon::today()->toDateString() || $endDate !== Carbon\Carbon::today()->toDateString())
                    <a href="{{ route('reports.payment') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition" title="Clear Filters">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                @endif
                <button type="button" onclick="window.print()" class="px-3.5 py-1.5 border border-slate-200 hover:border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <!-- Card 1: Total Revenue -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Net Revenue</span>
            <div class="mt-2 flex items-baseline justify-between">
                <span class="text-xl font-black text-slate-900">{{ $currency }}{{ number_format($totalPaymentsAmount, 2) }}</span>
            </div>
            <span class="text-[11px] text-slate-500 mt-1 font-medium">{{ $totalCompletedBills }} Total Completed Bills</span>
        </div>

        <!-- Card 2: Cash Total -->
        <div class="bg-white p-4 rounded-xl border border-emerald-100 bg-emerald-50/20 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-800">Cash Received</span>
                <span class="w-6 h-6 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </span>
            </div>
            <div class="mt-2">
                <span class="text-xl font-black text-emerald-900">{{ $currency }}{{ number_format($totalCash, 2) }}</span>
            </div>
            <span class="text-[11px] text-emerald-700 mt-1 font-semibold">
                {{ $totalPaymentsAmount > 0 ? round(($totalCash / $totalPaymentsAmount) * 100, 1) : 0 }}% of Total Sales
            </span>
        </div>

        <!-- Card 3: UPI Total -->
        <div class="bg-white p-4 rounded-xl border border-indigo-100 bg-indigo-50/20 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-800">UPI Received</span>
                <span class="w-6 h-6 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-qrcode"></i>
                </span>
            </div>
            <div class="mt-2">
                <span class="text-xl font-black text-indigo-900">{{ $currency }}{{ number_format($totalUpi, 2) }}</span>
            </div>
            <span class="text-[11px] text-indigo-700 mt-1 font-semibold">
                {{ $totalPaymentsAmount > 0 ? round(($totalUpi / $totalPaymentsAmount) * 100, 1) : 0 }}% of Total Sales
            </span>
        </div>

        <!-- Card 4: Card Total -->
        <div class="bg-white p-4 rounded-xl border border-blue-100 bg-blue-50/20 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-blue-800">Card Received</span>
                <span class="w-6 h-6 rounded-md bg-blue-100 text-blue-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-credit-card"></i>
                </span>
            </div>
            <div class="mt-2">
                <span class="text-xl font-black text-blue-900">{{ $currency }}{{ number_format($totalCard, 2) }}</span>
            </div>
            <span class="text-[11px] text-blue-700 mt-1 font-semibold">
                {{ $totalPaymentsAmount > 0 ? round(($totalCard / $totalPaymentsAmount) * 100, 1) : 0 }}% of Total Sales
            </span>
        </div>

        <!-- Card 5: Multi-Mode Split Total -->
        <div class="bg-white p-4 rounded-xl border border-amber-200 bg-amber-50/30 shadow-sm flex flex-col justify-between col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold uppercase tracking-wider text-amber-800">Multi-Mode (Split)</span>
                <span class="w-6 h-6 rounded-md bg-amber-100 text-amber-800 flex items-center justify-center text-xs font-bold">
                    <i class="fa-solid fa-shuffle"></i>
                </span>
            </div>
            <div class="mt-2">
                <span class="text-xl font-black text-amber-900">{{ $currency }}{{ number_format($multiModeAmount, 2) }}</span>
            </div>
            <span class="text-[11px] text-amber-800 mt-1 font-semibold">
                {{ $multiModeCount }} Split Bills ({{ $totalCompletedBills > 0 ? round(($multiModeCount / $totalCompletedBills) * 100, 1) : 0 }}%)
            </span>
        </div>
    </div>

    <!-- Payment Modes Summary & Split Combinations -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Aggregated Mode Summary Table -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Payment Mode Breakdown Summary</h3>
                <span class="text-[11px] text-slate-500 font-medium">All modes reconciled</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-slate-100/60 border-b border-slate-200 text-slate-600 uppercase font-semibold">
                            <th class="py-2.5 px-4">Payment Method</th>
                            <th class="py-2.5 px-4 text-center">Transactions Count</th>
                            <th class="py-2.5 px-4 text-right">Total Amount Received</th>
                            <th class="py-2.5 px-4 text-right">Volume Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($paymentSummary as $row)
                        @php
                            $share = $totalPaymentsAmount > 0 ? round(($row->total_amount / $totalPaymentsAmount) * 100, 1) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="py-2.5 px-4 font-bold uppercase text-slate-900">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold
                                    {{ $row->payment_method === 'cash' ? 'bg-emerald-100 text-emerald-800' : ($row->payment_method === 'upi' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800') }}">
                                    <i class="fa-solid {{ $row->payment_method === 'cash' ? 'fa-money-bill-wave' : ($row->payment_method === 'upi' ? 'fa-qrcode' : 'fa-credit-card') }} mr-1"></i>
                                    {{ $row->payment_method }}
                                </span>
                            </td>
                            <td class="py-2.5 px-4 text-center font-bold text-slate-700">{{ $row->count }}</td>
                            <td class="py-2.5 px-4 text-right font-black text-slate-900">{{ $currency }}{{ number_format($row->total_amount, 2) }}</td>
                            <td class="py-2.5 px-4 text-right font-bold text-slate-600">
                                <span class="inline-block px-2 py-0.5 rounded bg-slate-100">{{ $share }}%</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-400">No payment records found for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                        <tr>
                            <td class="py-2.5 px-4 text-slate-800 uppercase">Total Reconciled</td>
                            <td class="py-2.5 px-4 text-center text-slate-800">{{ $paymentSummary->sum('count') }}</td>
                            <td class="py-2.5 px-4 text-right text-slate-900 text-sm font-black">{{ $currency }}{{ number_format($totalPaymentsAmount, 2) }}</td>
                            <td class="py-2.5 px-4 text-right text-slate-700">100.0%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Multi-Mode Split Patterns Card -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-diagram-project text-amber-500"></i> Multi-Mode Combinations
                    </h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                        {{ $multiModeCount }} Split Bills
                    </span>
                </div>
                <div class="mt-3 space-y-2.5">
                    @forelse($multiModeCombinations as $combo => $cData)
                    <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/60 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-800">{{ $combo }}</span>
                            <p class="text-[11px] text-slate-500">{{ $cData['count'] }} bills settled using this split</p>
                        </div>
                        <span class="text-xs font-black text-slate-900">{{ $currency }}{{ number_format($cData['amount'], 2) }}</span>
                    </div>
                    @empty
                    <div class="py-6 text-center text-slate-400 text-xs">
                        <i class="fa-solid fa-receipt text-2xl mb-1 text-slate-300"></i>
                        <p>No multi-mode split bills recorded in this period.</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 mt-3 flex justify-between text-xs font-semibold text-slate-600">
                <span>Single-Mode Bills: <strong class="text-slate-900">{{ $singleModeCount }}</strong></span>
                <span>Split-Mode Bills: <strong class="text-amber-800">{{ $multiModeCount }}</strong></span>
            </div>
        </div>
    </div>

    <!-- Detailed Bill-by-Bill Payment & Multi-Mode Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-slate-600"></i>
                    Detailed Bill-by-Bill Payment Transactions Breakdown
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Showing itemized cash, UPI, and card breakdown for each bill transaction.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold px-2.5 py-1 rounded-md bg-slate-200 text-slate-700">
                    {{ $detailedBills->total() }} Total Transactions Found
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 uppercase font-semibold">
                        <th class="py-3 px-3">Invoice #</th>
                        <th class="py-3 px-3">Date & Time</th>
                        <th class="py-3 px-3">Type / Table</th>
                        <th class="py-3 px-3">Cashier</th>
                        <th class="py-3 px-3 text-center">Settlement Mode</th>
                        <th class="py-3 px-3 text-right bg-emerald-50/40 text-emerald-900">Cash (₹)</th>
                        <th class="py-3 px-3 text-right bg-indigo-50/40 text-indigo-900">UPI (₹) & Ref</th>
                        <th class="py-3 px-3 text-right bg-blue-50/40 text-blue-900">Card (₹) & Ref</th>
                        <th class="py-3 px-3 text-right font-black">Grand Total</th>
                        <th class="py-3 px-3 text-center no-print">Receipt</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($detailedBills as $bill)
                    @php
                        $paymentCount = $bill->payments->count();
                        $isMultiMode = $paymentCount > 1;
                        $methodsList = $bill->payments->pluck('payment_method')->unique()->values()->map(fn($m) => strtoupper($m))->implode(' + ');
                        
                        $cashSum = $bill->payments->where('payment_method', 'cash')->sum('amount');
                        $upiPayments = $bill->payments->where('payment_method', 'upi');
                        $upiSum = $upiPayments->sum('amount');
                        $upiRefs = $upiPayments->pluck('reference_number')->filter()->implode(', ');

                        $cardPayments = $bill->payments->where('payment_method', 'card');
                        $cardSum = $cardPayments->sum('amount');
                        $cardRefs = $cardPayments->pluck('reference_number')->filter()->implode(', ');
                    @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <!-- Invoice # -->
                        <td class="py-3 px-3 font-bold text-slate-900 whitespace-nowrap">
                            <a href="{{ route('print.bill', $bill->id) }}" target="_blank" class="hover:underline text-slate-900 flex items-center gap-1">
                                <i class="fa-solid fa-file-invoice text-slate-400 text-[10px]"></i>
                                {{ $bill->invoice_number }}
                            </a>
                            @if($bill->customer_name)
                                <div class="text-[10px] text-slate-500 font-normal truncate max-w-[120px]">
                                    {{ $bill->customer_name }}
                                </div>
                            @endif
                        </td>

                        <!-- Date & Time -->
                        <td class="py-3 px-3 text-slate-600 whitespace-nowrap">
                            <span class="font-medium text-slate-800">{{ $bill->created_at->format('d/m/Y') }}</span>
                            <div class="text-[10px] text-slate-400">{{ $bill->created_at->format('h:i A') }}</div>
                        </td>

                        <!-- Order Type / Table -->
                        <td class="py-3 px-3 text-slate-700 whitespace-nowrap">
                            @if($bill->order_type === 'table')
                                <span class="inline-flex items-center gap-1 font-bold text-slate-800">
                                    <i class="fa-solid fa-chair text-amber-500 text-[10px]"></i>
                                    {{ $bill->table?->table_number ?? 'Table' }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 font-semibold text-slate-600">
                                    <i class="fa-solid fa-bag-shopping text-blue-500 text-[10px]"></i>
                                    Counter
                                </span>
                            @endif
                        </td>

                        <!-- Cashier -->
                        <td class="py-3 px-3 text-slate-600 text-[11px] whitespace-nowrap">
                            {{ $bill->cashier?->name ?? 'Staff' }}
                        </td>

                        <!-- Settlement Mode Badge -->
                        <td class="py-3 px-3 text-center whitespace-nowrap">
                            @if($isMultiMode)
                                <span class="inline-block px-2.5 py-1 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    <i class="fa-solid fa-shuffle mr-1"></i> Multi: {{ $methodsList }}
                                </span>
                            @else
                                @php
                                    $singleMode = $bill->payments->first()?->payment_method ?? 'cash';
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ $singleMode === 'cash' ? 'bg-emerald-100 text-emerald-800' : ($singleMode === 'upi' ? 'bg-indigo-100 text-indigo-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ $singleMode }}
                                </span>
                            @endif
                        </td>

                        <!-- Cash Amount -->
                        <td class="py-3 px-3 text-right whitespace-nowrap bg-emerald-50/20 font-bold {{ $cashSum > 0 ? 'text-emerald-800' : 'text-slate-300' }}">
                            {{ $cashSum > 0 ? $currency . number_format($cashSum, 2) : '-' }}
                        </td>

                        <!-- UPI Amount & Reference -->
                        <td class="py-3 px-3 text-right whitespace-nowrap bg-indigo-50/20">
                            @if($upiSum > 0)
                                <div class="font-bold text-indigo-800">{{ $currency }}{{ number_format($upiSum, 2) }}</div>
                                @if($upiRefs)
                                    <div class="text-[9px] text-indigo-600 font-mono" title="UPI Ref: {{ $upiRefs }}">
                                        Ref: {{ \Illuminate\Support\Str::limit($upiRefs, 14) }}
                                    </div>
                                @endif
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>

                        <!-- Card Amount & Reference -->
                        <td class="py-3 px-3 text-right whitespace-nowrap bg-blue-50/20">
                            @if($cardSum > 0)
                                <div class="font-bold text-blue-800">{{ $currency }}{{ number_format($cardSum, 2) }}</div>
                                @if($cardRefs)
                                    <div class="text-[9px] text-blue-600 font-mono" title="Card Ref: {{ $cardRefs }}">
                                        Ref: {{ \Illuminate\Support\Str::limit($cardRefs, 14) }}
                                    </div>
                                @endif
                            @else
                                <span class="text-slate-300">-</span>
                            @endif
                        </td>

                        <!-- Grand Total -->
                        <td class="py-3 px-3 text-right font-black text-slate-900 text-sm whitespace-nowrap">
                            {{ $currency }}{{ number_format($bill->grand_total, 2) }}
                        </td>

                        <!-- Actions -->
                        <td class="py-3 px-3 text-center no-print whitespace-nowrap">
                            <a href="{{ route('print.bill', $bill->id) }}" target="_blank" class="p-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition inline-block" title="Print Thermal Receipt">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-12 text-center text-slate-400">
                            <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
                            <p class="text-sm font-semibold">No bills matched the selected payment filter.</p>
                            <p class="text-xs text-slate-400 mt-1">Try expanding the date range or selecting "All Modes".</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($detailedBills->hasPages())
        <div class="p-4 border-t border-slate-100 no-print">
            {{ $detailedBills->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
