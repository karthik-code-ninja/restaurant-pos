@extends('layouts.app')

@section('title', 'Tax & GST Management')
@section('page_title', 'Tax & GST Configuration')

@section('content')
<div class="space-y-6">
    <!-- Top Row: Configuration Card & GST Summary Card -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Configuration Card -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-1">GST & Tax Rules</h3>
            <p class="text-xs text-slate-500 mb-4">Configure system-wide GST split and pricing behavior</p>

            <form action="{{ route('tax.settings') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Restaurant GSTIN</label>
                    <input type="text" name="restaurant_gstin" value="{{ $gstin }}" placeholder="e.g. 33AAAAA0000A1Z5"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 uppercase font-mono font-bold">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Pricing Model</label>
                        <select name="tax_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                            <option value="exclusive" {{ $taxType === 'exclusive' ? 'selected' : '' }}>Exclusive (Tax Added to Price)</option>
                            <option value="inclusive" {{ $taxType === 'inclusive' ? 'selected' : '' }}>Inclusive (Tax Included in Price)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Default Total GST (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="default_gst_rate" value="{{ $defaultGst }}" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">CGST (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="cgst_rate" value="{{ $cgst }}" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">SGST (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="sgst_rate" value="{{ $sgst }}" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">IGST (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="igst_rate" value="{{ $igst }}" required
                            class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold shadow-md shadow-orange-600/20">
                        Update Tax Configuration
                    </button>
                </div>
            </form>
        </div>

        <!-- GST Live Period Summary -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">GST Collection Summary</h3>
                        <p class="text-xs text-slate-500">Tax collected from completed orders</p>
                    </div>
                    <form method="GET" action="{{ route('tax.index') }}" class="flex items-center gap-1.5">
                        <input type="date" name="start_date" value="{{ $startDate }}" class="px-2 py-1 rounded border border-slate-300 text-xs">
                        <input type="date" name="end_date" value="{{ $endDate }}" class="px-2 py-1 rounded border border-slate-300 text-xs">
                        <button type="submit" class="px-2.5 py-1 bg-slate-900 text-white rounded text-xs font-bold">Go</button>
                    </form>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase">Taxable Sales</p>
                        <h4 class="text-xl font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($taxableAmount, 2) }}</h4>
                    </div>
                    <div class="p-3 bg-orange-50 border border-orange-100 rounded-xl">
                        <p class="text-[11px] font-semibold text-orange-700 uppercase">Total GST Collected</p>
                        <h4 class="text-xl font-black text-orange-900 mt-1">{{ $currency }}{{ number_format($totalTaxCollected, 2) }}</h4>
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="p-2.5 rounded-lg bg-slate-50 flex items-center justify-between">
                        <span class="font-medium text-slate-600">Central GST (CGST):</span>
                        <span class="font-bold text-slate-800">{{ $currency }}{{ number_format($cgstTotal, 2) }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 flex items-center justify-between">
                        <span class="font-medium text-slate-600">State GST (SGST):</span>
                        <span class="font-bold text-slate-800">{{ $currency }}{{ number_format($sgstTotal, 2) }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-50 flex items-center justify-between">
                        <span class="font-medium text-slate-600">Integrated GST (IGST):</span>
                        <span class="font-bold text-slate-800">{{ $currency }}{{ number_format($igstTotal, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">Gross Period Turnover: <strong class="text-slate-800">{{ $currency }}{{ number_format($totalGrossSales, 2) }}</strong></span>
                <a href="{{ route('reports.gst') }}" class="text-xs font-bold text-orange-600 hover:underline">Detailed GST Report &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Food-Wise Tax Rates Overview -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800">Food-Wise Tax Configuration</h3>
            <p class="text-xs text-slate-500">Item-level tax rates applied during billing calculation</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Item Code</th>
                        <th class="py-3 px-4">Food Name</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4 text-right">Base Price</th>
                        <th class="py-3 px-4 text-center">Tax Rate</th>
                        <th class="py-3 px-4 text-right">CGST</th>
                        <th class="py-3 px-4 text-right">SGST</th>
                        <th class="py-3 px-4 text-right">Price with Tax</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($foods as $f)
                    @php
                        $rate = (float) $f->tax_rate;
                        $taxAmt = $taxType === 'inclusive' 
                            ? round($f->price - ($f->price / (1 + ($rate / 100))), 2) 
                            : round($f->price * ($rate / 100), 2);
                        $cgstAmt = round($taxAmt / 2, 2);
                        $sgstAmt = round($taxAmt - $cgstAmt, 2);
                        $grossPrice = $taxType === 'inclusive' ? $f->price : ($f->price + $taxAmt);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-2.5 px-4 font-mono font-bold text-slate-700">{{ $f->code }}</td>
                        <td class="py-2.5 px-4 font-bold text-slate-900">{{ $f->name }}</td>
                        <td class="py-2.5 px-4 text-slate-500">{{ $f->category?->name }}</td>
                        <td class="py-2.5 px-4 text-right font-semibold text-slate-800">{{ $currency }}{{ number_format($f->price, 2) }}</td>
                        <td class="py-2.5 px-4 text-center font-bold text-slate-700">{{ number_format($f->tax_rate, 1) }}%</td>
                        <td class="py-2.5 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($cgstAmt, 2) }}</td>
                        <td class="py-2.5 px-4 text-right text-slate-600">{{ $currency }}{{ number_format($sgstAmt, 2) }}</td>
                        <td class="py-2.5 px-4 text-right font-black text-slate-900">{{ $currency }}{{ number_format($grossPrice, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $foods->links() }}
        </div>
    </div>
</div>
@endsection
