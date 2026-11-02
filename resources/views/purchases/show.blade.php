@extends('layouts.app')

@section('title', 'Purchase Invoice ' . $purchase->purchase_no)
@section('page_title', 'Purchase Invoice #' . $purchase->purchase_no)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Action buttons -->
    <div class="flex items-center justify-between no-print bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Back to Register
            </a>
            <span class="text-xs text-slate-400">|</span>
            <span class="text-xs font-mono font-bold text-slate-700">{{ $purchase->purchase_no }}</span>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print Invoice</span>
            </button>
            <a href="{{ route('purchases.create') }}" class="px-4 py-1.5 bg-red-700 hover:bg-red-800 text-white text-xs font-bold rounded shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>New Purchase</span>
            </a>
        </div>
    </div>

    <!-- Printable Tax Invoice Document -->
    <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm space-y-6 font-sans">
        <!-- Invoice Header -->
        <div class="flex justify-between items-start border-b border-slate-200 pb-6">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-red-700 bg-red-50 border border-red-200 px-2 py-0.5 rounded">Tax Invoice / Goods Inward</span>
                <h1 class="text-2xl font-black text-slate-900 mt-2">{{ $restaurantName }}</h1>
                <p class="text-xs text-slate-500 mt-1">Official Purchase Voucher</p>
            </div>
            <div class="text-right">
                <div class="text-sm font-bold text-slate-800">Voucher No: <span class="font-mono text-red-700">{{ $purchase->purchase_no }}</span></div>
                <div class="text-xs text-slate-500 mt-1">Date: <span class="font-medium text-slate-700">{{ $purchase->purchase_date->format('d F Y') }}</span></div>
                @if($purchase->invoice_no)
                <div class="text-xs text-slate-500 mt-0.5">Supplier Bill #: <span class="font-mono font-bold text-slate-700">{{ $purchase->invoice_no }}</span></div>
                @endif
                <div class="mt-2">
                    @if($purchase->payment_status === 'paid')
                        <span class="px-3 py-1 bg-emerald-100 text-emerald-800 font-extrabold rounded-full text-xs">FULLY PAID</span>
                    @elseif($purchase->payment_status === 'partial')
                        <span class="px-3 py-1 bg-amber-100 text-amber-800 font-extrabold rounded-full text-xs">PARTIALLY PAID</span>
                    @else
                        <span class="px-3 py-1 bg-red-100 text-red-800 font-extrabold rounded-full text-xs">UNPAID / CREDIT</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Supplier & Payment Details -->
        <div class="grid grid-cols-2 gap-8 text-xs">
            <div>
                <h3 class="font-bold text-slate-400 uppercase tracking-wider mb-1">Supplier / Vendor:</h3>
                <div class="font-bold text-sm text-slate-900">{{ $purchase->supplier_name }}</div>
                @if($purchase->supplier)
                    @if($purchase->supplier->company_name)
                    <div class="text-slate-600">{{ $purchase->supplier->company_name }}</div>
                    @endif
                    @if($purchase->supplier->gstin)
                    <div class="text-slate-600 font-mono">GSTIN: <b>{{ $purchase->supplier->gstin }}</b></div>
                    @endif
                    @if($purchase->supplier->phone)
                    <div class="text-slate-600">Ph: {{ $purchase->supplier->phone }}</div>
                    @endif
                    @if($purchase->supplier->address)
                    <div class="text-slate-500 mt-1">{{ $purchase->supplier->address }}</div>
                    @endif
                @endif
            </div>
            <div class="text-right">
                <h3 class="font-bold text-slate-400 uppercase tracking-wider mb-1">Payment Breakdown:</h3>
                <div class="space-y-1">
                    <div>Payment Method: <span class="font-bold uppercase text-slate-800">{{ $purchase->payment_method }}</span></div>
                    <div>Recorded By: <span class="font-medium text-slate-700">{{ $purchase->creator?->name ?? 'Admin' }}</span></div>
                    <div>Created At: <span class="font-mono text-slate-500">{{ $purchase->created_at->format('d/m/Y h:i A') }}</span></div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase">
                    <tr>
                        <th class="py-2.5 px-3">#</th>
                        <th class="py-2.5 px-3">Item Description</th>
                        <th class="py-2.5 px-3">Type</th>
                        <th class="py-2.5 px-3 text-right">Qty</th>
                        <th class="py-2.5 px-3 text-right">Rate</th>
                        <th class="py-2.5 px-3 text-right">GST %</th>
                        <th class="py-2.5 px-3 text-right">Tax (₹)</th>
                        <th class="py-2.5 px-3 text-right">Total (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($purchase->items as $idx => $item)
                    <tr>
                        <td class="py-2.5 px-3 text-slate-400">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-3 font-bold text-slate-800">{{ $item->item_name }}</td>
                        <td class="py-2.5 px-3 text-slate-500 uppercase text-[10px]">{{ $item->item_type }}</td>
                        <td class="py-2.5 px-3 text-right font-mono">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</td>
                        <td class="py-2.5 px-3 text-right font-mono">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-2.5 px-3 text-right font-mono">{{ number_format($item->tax_percent, 1) }}%</td>
                        <td class="py-2.5 px-3 text-right font-mono text-blue-600">{{ number_format($item->tax_amount, 2) }}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">{{ number_format($item->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary Totals -->
        <div class="flex justify-end">
            <div class="w-72 space-y-2 text-xs border border-slate-200 p-4 rounded-lg bg-slate-50">
                <div class="flex justify-between text-slate-600">
                    <span>Taxable Subtotal:</span>
                    <span class="font-mono font-bold">{{ $currency }} {{ number_format($purchase->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-blue-600">
                    <span>Total GST Tax:</span>
                    <span class="font-mono font-bold">{{ $currency }} {{ number_format($purchase->tax_amount, 2) }}</span>
                </div>
                @if($purchase->discount_amount > 0)
                <div class="flex justify-between text-slate-600">
                    <span>Discount:</span>
                    <span class="font-mono font-bold">- {{ $currency }} {{ number_format($purchase->discount_amount, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-slate-900 font-extrabold text-sm border-t border-slate-200 pt-2">
                    <span>Grand Total:</span>
                    <span class="font-mono text-red-700 text-base">{{ $currency }} {{ number_format($purchase->grand_total, 2) }}</span>
                </div>
                <div class="flex justify-between text-emerald-700 font-bold border-t border-slate-200 pt-1">
                    <span>Amount Paid:</span>
                    <span class="font-mono">{{ $currency }} {{ number_format($purchase->paid_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-red-700 font-bold border-t border-slate-200 pt-1">
                    <span>Balance Due:</span>
                    <span class="font-mono font-black">{{ $currency }} {{ number_format($purchase->due_amount, 2) }}</span>
                </div>
            </div>
        </div>

        @if($purchase->notes)
        <div class="text-xs text-slate-500 border-t pt-3">
            <b>Remarks:</b> {{ $purchase->notes }}
        </div>
        @endif
    </div>
</div>
@endsection

