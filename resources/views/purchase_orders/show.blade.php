@extends('layouts.app')

@section('title', 'PO ' . $purchaseOrder->po_number)
@section('page_title', 'Purchase Order #' . $purchaseOrder->po_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between no-print bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <a href="{{ route('purchase-orders.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Back to Orders
        </a>
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded shadow transition">
                <i class="fa-solid fa-print mr-1"></i> Print PO
            </button>
            @if($purchaseOrder->status !== 'received')
            <form action="{{ route('purchase-orders.convert', $purchaseOrder) }}" method="POST" onsubmit="return confirm('Convert this PO into a Purchase Bill?');" class="inline">
                @csrf
                <button type="submit" class="px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded shadow transition">
                    <i class="fa-solid fa-file-invoice mr-1"></i> Convert to Purchase Bill
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Printable PO Document -->
    <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-sm space-y-6 font-sans">
        <div class="flex justify-between items-start border-b border-slate-200 pb-6">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700 bg-blue-50 border border-blue-200 px-2 py-0.5 rounded">Purchase Order</span>
                <h1 class="text-2xl font-black text-slate-900 mt-2">{{ $restaurantName }}</h1>
                <p class="text-xs text-slate-500 mt-1">Official Purchase Requisition</p>
            </div>
            <div class="text-right">
                <div class="text-sm font-bold text-slate-800">PO Number: <span class="font-mono text-blue-700">{{ $purchaseOrder->po_number }}</span></div>
                <div class="text-xs text-slate-500 mt-1">Date: <span class="font-medium text-slate-700">{{ $purchaseOrder->po_date->format('d F Y') }}</span></div>
                @if($purchaseOrder->expected_date)
                <div class="text-xs text-slate-500 mt-0.5">Expected Delivery: <span class="font-medium text-slate-700">{{ $purchaseOrder->expected_date->format('d F Y') }}</span></div>
                @endif
                <div class="mt-2">
                    <span class="px-3 py-1 bg-blue-100 text-blue-800 font-extrabold rounded-full text-xs uppercase">{{ $purchaseOrder->status }}</span>
                </div>
            </div>
        </div>

        <div class="text-xs">
            <h3 class="font-bold text-slate-400 uppercase tracking-wider mb-1">To Vendor:</h3>
            <div class="font-bold text-sm text-slate-900">{{ $purchaseOrder->supplier_name }}</div>
            @if($purchaseOrder->supplier)
                @if($purchaseOrder->supplier->company_name)
                <div class="text-slate-600">{{ $purchaseOrder->supplier->company_name }}</div>
                @endif
                @if($purchaseOrder->supplier->phone)
                <div class="text-slate-600">Ph: {{ $purchaseOrder->supplier->phone }}</div>
                @endif
            @endif
        </div>

        <div class="border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase">
                    <tr>
                        <th class="py-2.5 px-3">#</th>
                        <th class="py-2.5 px-3">Item Description</th>
                        <th class="py-2.5 px-3 text-right">Quantity</th>
                        <th class="py-2.5 px-3 text-right">Est. Rate</th>
                        <th class="py-2.5 px-3 text-right">Total Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($purchaseOrder->items as $idx => $item)
                    <tr>
                        <td class="py-2.5 px-3 text-slate-400">{{ $idx + 1 }}</td>
                        <td class="py-2.5 px-3 font-bold text-slate-800">{{ $item->item_name }}</td>
                        <td class="py-2.5 px-3 text-right font-mono">{{ number_format($item->quantity, 2) }} {{ $item->unit }}</td>
                        <td class="py-2.5 px-3 text-right font-mono">{{ $currency }} {{ number_format($item->unit_price, 2) }}</td>
                        <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">{{ $currency }} {{ number_format($item->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            <div class="w-64 p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-1">
                <div class="flex justify-between font-bold text-sm text-slate-900">
                    <span>Estimated Total:</span>
                    <span class="font-mono text-blue-700">{{ $currency }} {{ number_format($purchaseOrder->total_amount, 2) }}</span>
                </div>
            </div>
        </div>

        @if($purchaseOrder->notes)
        <div class="text-xs text-slate-500 border-t pt-3">
            <b>Terms & Instructions:</b> {{ $purchaseOrder->notes }}
        </div>
        @endif
    </div>
</div>
@endsection

