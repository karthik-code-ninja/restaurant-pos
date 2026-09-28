@extends('layouts.app')

@section('title', 'GST Tax Report')
@section('page_title', '7. GST Tax Filing Audit Report')
@section('page_title', 'GST Tax Filing Audit Report')

@section('content')
<div class="space-y-6">
    <!-- Clean Print-Only Report Header -->
    <div class="print-only mb-6 pb-3 border-b-2 border-slate-900">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-black uppercase tracking-tight text-slate-900">{{ \App\Models\Setting::get('restaurant_name', 'RestroPOS') }}</h1>
                <p class="text-xs text-slate-700">{{ \App\Models\Setting::get('restaurant_address', '') }} | Phone: {{ \App\Models\Setting::get('restaurant_contact', '') }}</p>
                @if($gstin)
                <p class="text-xs font-mono font-bold text-slate-900 mt-0.5">GSTIN / Tax ID: {{ $gstin }}</p>
                @endif
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 bg-slate-900 text-white text-xs font-bold uppercase rounded">GST Tax Filing Report</span>
                <p class="text-xs font-medium text-slate-600 mt-1">Period: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                <p class="text-[11px] text-slate-500">Generated: {{ date('d/m/Y h:i A') }}</p>
            </div>
        </div>
    </div>

    <!-- Top Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4 no-print">
        <form method="GET" action="{{ route('reports.gst') }}" class="flex items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <span class="text-slate-400 text-xs">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">Filter</button>
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">Filter</button>
        </form>

        <div class="flex items-center gap-2">
            @if($gstin)
            <span class="px-3 py-1 bg-slate-100 rounded-lg text-xs font-mono font-bold text-slate-700">GSTIN: {{ $gstin }}</span>
            @endif
            <button onclick="window.print()" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Print Report
            <button type="button" onclick="window.print()" class="px-3.5 py-1.5 bg-orange-600 text-white hover:bg-orange-700 rounded-lg text-xs font-bold flex items-center gap-1.5 shadow-sm transition">
                <i class="fa-solid fa-print"></i> Print Report Data
            </button>
        </div>
    </div>

    <!-- GST Summary Metrics -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="p-3 bg-white border border-slate-200 rounded-xl">
            <p class="text-[11px] font-semibold text-slate-500 uppercase">Taxable Subtotal</p>
            <h4 class="text-base font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($totalSubtotal, 2) }}</h4>
            <span class="text-[10px] text-slate-400 font-medium">Net Value Before Tax</span>
        </div>
        <div class="p-3 bg-white border border-slate-200 rounded-xl">
            <p class="text-[11px] font-semibold text-slate-500 uppercase">Central GST (CGST)</p>
            <p class="text-[11px] font-semibold text-slate-500 uppercase">CGST ({{ \App\Models\Setting::get('cgst_rate', '2.5') }}%)</p>
            <h4 class="text-base font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($totalCgst, 2) }}</h4>
            <span class="text-[10px] text-slate-400 font-medium">Central Tax Portion</span>
        </div>
        <div class="p-3 bg-white border border-slate-200 rounded-xl">
            <p class="text-[11px] font-semibold text-slate-500 uppercase">State GST (SGST)</p>
            <p class="text-[11px] font-semibold text-slate-500 uppercase">SGST ({{ \App\Models\Setting::get('sgst_rate', '2.5') }}%)</p>
            <h4 class="text-base font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($totalSgst, 2) }}</h4>
            <span class="text-[10px] text-slate-400 font-medium">State Tax Portion</span>
        </div>
        <div class="p-3 bg-orange-50 border border-orange-200 rounded-xl">
            <p class="text-[11px] font-semibold text-orange-700 uppercase">Total GST Output</p>
            <p class="text-[11px] font-semibold text-orange-700 uppercase">Total GST ({{ \App\Models\Setting::get('default_gst_rate', '5.0') }}%)</p>
            <h4 class="text-base font-black text-orange-900 mt-1">{{ $currency }}{{ number_format($totalTax, 2) }}</h4>
            <span class="text-[10px] text-orange-600 font-medium">Output Tax Liability</span>
        </div>
        <div class="p-3 bg-slate-900 text-white rounded-xl col-span-2 sm:col-span-1 shadow-inner">
            <p class="text-[11px] font-bold text-orange-400 uppercase">Gross Sales</p>
            <p class="text-[11px] font-bold text-orange-400 uppercase">Gross Revenue</p>
            <h4 class="text-base font-black text-white mt-1">{{ $currency }}{{ number_format($grandTotal, 2) }}</h4>
            <span class="text-[10px] text-slate-400 font-medium">Inclusive of GST</span>
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
                        <th class="py-3 px-3">Invoice #</th>
                        <th class="py-3 px-3">Date</th>
                        <th class="py-3 px-3 text-right">Taxable Subtotal</th>
                        <th class="py-3 px-3 text-center">GST Rate %</th>
                        <th class="py-3 px-3 text-right">CGST</th>
                        <th class="py-3 px-3 text-right">SGST</th>
                        <th class="py-3 px-3 text-right">Total GST</th>
                        <th class="py-3 px-3 text-right">Invoice Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($bills as $bill)
                    @php
                        $effectiveGst = ($bill->subtotal > 0 && $bill->tax_total > 0) 
                            ? round(($bill->tax_total / $bill->subtotal) * 100, 1) 
                            : (float) \App\Models\Setting::get('default_gst_rate', 5.0);
                        $cgstPct = round($effectiveGst / 2, 2);
                        $sgstPct = round($effectiveGst / 2, 2);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $bill->invoice_number }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $bill->created_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 text-right text-slate-700 font-semibold">{{ $currency }}{{ number_format($bill->subtotal, 2) }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($bill->cgst_total, 2) }}</td>
                        <td class="py-3 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($bill->sgst_total, 2) }}</td>
                        <td class="py-3 px-4 text-right font-bold text-orange-700">{{ $currency }}{{ number_format($bill->tax_total, 2) }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900">{{ $currency }}{{ number_format($bill->grand_total, 2) }}</td>
                        <td class="py-2.5 px-3 font-mono font-bold text-slate-900">{{ $bill->invoice_number }}</td>
                        <td class="py-2.5 px-3 text-slate-500 whitespace-nowrap">{{ $bill->created_at->format('d/m/Y') }}</td>
                        <td class="py-2.5 px-3 text-right text-slate-700 font-semibold">{{ $currency }}{{ number_format($bill->subtotal, 2) }}</td>
                        <td class="py-2.5 px-3 text-center">
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                {{ number_format($effectiveGst, 1) }}%
                            </span>
                        </td>
                        <td class="py-2.5 px-3 text-right text-slate-600">
                            <div>{{ $currency }}{{ number_format($bill->cgst_total, 2) }}</div>
                            <span class="text-[10px] text-slate-400 font-mono">({{ number_format($cgstPct, 1) }}%)</span>
                        </td>
                        <td class="py-2.5 px-3 text-right text-slate-600">
                            <div>{{ $currency }}{{ number_format($bill->sgst_total, 2) }}</div>
                            <span class="text-[10px] text-slate-400 font-mono">({{ number_format($sgstPct, 1) }}%)</span>
                        </td>
                        <td class="py-2.5 px-3 text-right font-bold text-orange-700">
                            <div>{{ $currency }}{{ number_format($bill->tax_total, 2) }}</div>
                            <span class="text-[10px] text-orange-500 font-mono">({{ number_format($effectiveGst, 1) }}%)</span>
                        </td>
                        <td class="py-2.5 px-3 text-right font-black text-slate-900">{{ $currency }}{{ number_format($bill->grand_total, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No invoices generated in this period.</td>
                        <td colspan="8" class="py-8 text-center text-slate-400">No invoices generated in this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
        <div class="p-4 border-t border-slate-100 no-print">
            {{ $bills->links() }}
        </div>
    </div>
</div>
@endsection
