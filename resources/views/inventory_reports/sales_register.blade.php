@extends('layouts.app')

@section('title', 'Sales Register')
@section('page_title', 'Detailed Sales Register (Shortcut: 8)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded">Shortcut: 8</span>
                <h1 class="text-xl font-bold text-slate-800">Sales Register & Bill Listing</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Chronological breakdown of every sales invoice, payment modes, GST taxes, and discounts</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print Register</span>
            </button>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Total Sales Invoiced</div>
            <div class="text-2xl font-black text-slate-900 mt-1">{{ $currency }} {{ number_format($totalSales, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Completed bills</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Total Output GST Collected</div>
            <div class="text-2xl font-black text-blue-600 mt-1">{{ $currency }} {{ number_format($totalTax, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Output tax liability</div>
        </div>
        <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="text-xs text-slate-500 font-semibold uppercase">Total Discounts Allowed</div>
            <div class="text-2xl font-black text-amber-600 mt-1">{{ $currency }} {{ number_format($totalDiscount, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Special offers & loyalty</div>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('accounting.sales-register') }}" class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-end gap-3 no-print">
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">From Date</label>
            <input type="date" name="start_date" value="{{ $startDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">To Date</label>
            <input type="date" name="end_date" value="{{ $endDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 mb-1">Payment Method</label>
            <select name="payment_method" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
                <option value="">All Payment Modes</option>
                <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="upi" {{ $paymentMethod === 'upi' ? 'selected' : '' }}>UPI / QR</option>
                <option value="card" {{ $paymentMethod === 'card' ? 'selected' : '' }}>Credit / Debit Card</option>
                <option value="credit" {{ $paymentMethod === 'credit' ? 'selected' : '' }}>Credit (Due)</option>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs font-bold text-slate-600 mb-1">Search</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by Bill #, Customer Name, Phone..." class="w-full text-xs border border-slate-300 rounded px-2.5 py-1.5">
        </div>
        <button type="submit" class="px-4 py-1.5 bg-slate-800 text-white text-xs font-bold rounded shadow">Filter</button>
        <a href="{{ route('accounting.sales-register') }}" class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-bold rounded">Reset</a>
    </form>

    <!-- Sales Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Bill #</th>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Customer Name</th>
                        <th class="py-3 px-4">Table / Order</th>
                        <th class="py-3 px-4 text-right">Taxable</th>
                        <th class="py-3 px-4 text-right">Tax (GST)</th>
                        <th class="py-3 px-4 text-right">Discount</th>
                        <th class="py-3 px-4 text-right">Grand Total</th>
                        <th class="py-3 px-4 text-center">Payment Mode</th>
                        <th class="py-3 px-4 text-center">Print</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($bills as $b)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-2.5 px-4 font-mono font-bold text-blue-800">{{ $b->invoice_number }}</td>
                        <td class="py-2.5 px-4 text-slate-600">{{ $b->created_at->format('d/m/Y h:i A') }}</td>
                        <td class="py-2.5 px-4 font-bold text-slate-800">{{ $b->customer_name ?: 'Walk-in Guest' }}</td>
                        <td class="py-2.5 px-4 text-slate-600 font-semibold">{{ $b->table ? $b->table->table_number : 'Takeaway / Parcel' }}</td>
                        <td class="py-2.5 px-4 text-right font-mono">{{ number_format($b->subtotal, 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-blue-600">{{ number_format($b->tax_total, 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-amber-600">{{ number_format($b->discount_amount, 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-slate-900">{{ number_format($b->grand_total, 2) }}</td>
                        <td class="py-2.5 px-4 text-center">
                            @foreach($b->payments as $pay)
                                <span class="inline-block px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded text-[10px] uppercase font-mono mr-1">
                                    {{ $pay->payment_method }} ({{ number_format($pay->amount, 0) }})
                                </span>
                            @endforeach
                            @if($b->due_amount > 0)
                                <span class="inline-block px-1.5 py-0.5 bg-red-100 text-red-800 font-bold rounded text-[10px]">
                                    Due: {{ number_format($b->due_amount, 0) }}
                                </span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-center">
                            <a href="{{ route('print.bill', $b) }}" target="_blank" class="p-1 text-slate-500 hover:text-blue-600 transition" title="Print Bill Receipt">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-receipt text-3xl mb-2"></i>
                            <p class="font-medium">No sales bills found for this filter criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($bills->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $bills->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

