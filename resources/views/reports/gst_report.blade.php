@extends('layouts.app')

@section('title', 'GST Tax Report')
@section('page_title', '7. GST Tax Filing Audit Report')

@section('content')
<div class="space-y-6">
    <!-- Top Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('reports.gst') }}" class="flex items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <span class="text-slate-400 text-xs">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">Filter</button>
        </form>

        <div class="flex items-center gap-2">
            @if($gstin)
            <span class="px-3 py-1 bg-slate-100 rounded-lg text-xs font-mono font-bold text-slate-700">GSTIN: {{ $gstin }}</span>
            @endif
            <button onclick="window.print()" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <!-- GST Summary Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="p-3 bg-white border border-slate-200 rounded-xl">
            <p class="text-[11px] font-semibold text-slate-500 uppercase">Taxable Subtotal</p>
            <h4 class="text-base font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($totalSubtotal, 2) }}</h4>
        </div>
        <div class="p-3 bg-white border border-slate-200 rounded-xl">
            <p class="text-[11px] font-semibold text-slate-500 uppercase">Central GST (CGST)</p>
            <h4 class="text-base font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($totalCgst, 2) }}</h4>
        </div>
        <div class="p-3 bg-white border border-slate-200 rounded-xl">
            <p class="text-[11px] font-semibold text-slate-500 uppercase">State GST (SGST)</p>
            <h4 class="text-base font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($totalSgst, 2) }}</h4>
        </div>
        <div class="p-3 bg-orange-50 border border-orange-200 rounded-xl">
            <p class="text-[11px] font-semibold text-orange-700 uppercase">Total GST Output</p>
            <h4 class="text-base font-black text-orange-900 mt-1">{{ $currency }}{{ number_format($totalTax, 2) }}</h4>
        </div>
        <div class="p-3 bg-slate-900 text-white rounded-xl col-span-2 sm:col-span-1 shadow-inner">
            <p class="text-[11px] font-bold text-orange-400 uppercase">Gross Sales</p>
            <h4 class="text-base font-black text-white mt-1">{{ $currency }}{{ number_format($grandTotal, 2) }}</h4>
        </div>
    </div>

    <!-- Detailed Invoice GST Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Invoice #</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4 text-right">Taxable Subtotal</th>
                        <th class="py-3 px-4 text-right">CGST</th>
                        <th class="py-3 px-4 text-right">SGST</th>
                        <th class="py-3 px-4 text-right">Total GST</th>
                        <th class="py-3 px-4 text-right">Invoice Grand Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($bills as $bill)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $bill->invoice_number }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $bill->created_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-right text-slate-700 font-semibold">{{ $currency }}{{ number_format($bill->subtotal, 2) }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($bill->cgst_total, 2) }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($bill->sgst_total, 2) }}</td>
                        <td class="py-3 px-4 text-right font-bold text-orange-700">{{ $currency }}{{ number_format($bill->tax_total, 2) }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900">{{ $currency }}{{ number_format($bill->grand_total, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No invoices generated in this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $bills->links() }}
        </div>
    </div>
</div>
@endsection
