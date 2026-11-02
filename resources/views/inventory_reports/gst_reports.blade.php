@extends('layouts.app')

@section('title', 'GST Reports')
@section('page_title', 'GST Filing Reports (Shortcut: l)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm no-print">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: l</span>
                <h1 class="text-xl font-bold text-slate-800">GST Compliance & Tax Reports</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Official GSTR-1, GSTR-2 (ITC), and GSTR-3B monthly return computation</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print GST Sheet</span>
            </button>
        </div>
    </div>

    <!-- Date Range & Tab Switcher -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-wrap items-center justify-between gap-3 no-print">
        <div class="flex items-center space-x-2">
            <a href="{{ route('inventory-reports.gst-reports', ['tab' => 'gstr1', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $tab === 'gstr1' ? 'bg-red-700 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                GSTR-1 (Outward Sales)
            </a>
            <a href="{{ route('inventory-reports.gst-reports', ['tab' => 'gstr2', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $tab === 'gstr2' ? 'bg-red-700 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                GSTR-2 (Inward ITC Purchases)
            </a>
            <a href="{{ route('inventory-reports.gst-reports', ['tab' => 'gstr3b', 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="px-4 py-2 rounded-lg text-xs font-bold transition {{ $tab === 'gstr3b' ? 'bg-red-700 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                GSTR-3B (Net Tax Computation)
            </a>
        </div>

        <form method="GET" action="{{ route('inventory-reports.gst-reports') }}" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="date" name="start_date" value="{{ $startDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
            <span class="text-xs text-slate-400">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="text-xs border border-slate-300 rounded px-2.5 py-1.5">
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-xs font-bold rounded">Filter</button>
        </form>
    </div>

    @if($tab === 'gstr1')
    <!-- GSTR-1 SECTION -->
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs text-slate-500 font-semibold uppercase">Total Taxable Outward Value</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ $currency }} {{ number_format($totalSalesTaxable, 2) }}</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs text-slate-500 font-semibold uppercase">CGST (Central Tax)</div>
                <div class="text-2xl font-black text-blue-600 mt-1">{{ $currency }} {{ number_format($cgstSales, 2) }}</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs text-slate-500 font-semibold uppercase">SGST (State Tax)</div>
                <div class="text-2xl font-black text-blue-600 mt-1">{{ $currency }} {{ number_format($sgstSales, 2) }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b pb-2">Rate-wise Tax Summary (B2C & B2B)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase">
                            <th class="py-2.5 px-3">GST Tax Rate</th>
                            <th class="py-2.5 px-3 text-right">Taxable Turnover (₹)</th>
                            <th class="py-2.5 px-3 text-right">Central Tax (CGST)</th>
                            <th class="py-2.5 px-3 text-right">State Tax (SGST)</th>
                            <th class="py-2.5 px-3 text-right">Total Invoice Value (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($taxRatesSummary as $rateRow)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2.5 px-3 font-bold text-slate-800">{{ $rateRow['rate'] }}</td>
                            <td class="py-2.5 px-3 text-right font-mono">{{ number_format($rateRow['taxable'], 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-blue-700">{{ number_format($rateRow['cgst'], 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-blue-700">{{ number_format($rateRow['sgst'], 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">{{ number_format($rateRow['total'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-black border-t-2 border-slate-800">
                            <td class="py-2.5 px-3 uppercase">Total Outward Supplies:</td>
                            <td class="py-2.5 px-3 text-right font-mono">{{ number_format($totalSalesTaxable, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-blue-900">{{ number_format($cgstSales, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-blue-900">{{ number_format($sgstSales, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-slate-900">{{ number_format($totalSalesGrand, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @elseif($tab === 'gstr2')
    <!-- GSTR-2 SECTION -->
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs text-slate-500 font-semibold uppercase">Total Inward Taxable Value</div>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ $currency }} {{ number_format($totalPurchaseTaxable, 2) }}</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs text-slate-500 font-semibold uppercase">Eligible Input CGST</div>
                <div class="text-2xl font-black text-emerald-600 mt-1">{{ $currency }} {{ number_format($cgstPurchase, 2) }}</div>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs text-slate-500 font-semibold uppercase">Eligible Input SGST</div>
                <div class="text-2xl font-black text-emerald-600 mt-1">{{ $currency }} {{ number_format($sgstPurchase, 2) }}</div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b pb-2">Purchase Invoices with Input Tax Credit (ITC)</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase">
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Purchase #</th>
                            <th class="py-2.5 px-3">Supplier GSTIN</th>
                            <th class="py-2.5 px-3">Supplier Name</th>
                            <th class="py-2.5 px-3 text-right">Taxable Amount (₹)</th>
                            <th class="py-2.5 px-3 text-right">Input Tax (ITC)</th>
                            <th class="py-2.5 px-3 text-right">Total Invoice (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($purchases as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2.5 px-3 text-slate-600">{{ $p->purchase_date->format('d/m/Y') }}</td>
                            <td class="py-2.5 px-3 font-mono font-bold text-red-800">{{ $p->purchase_no }}</td>
                            <td class="py-2.5 px-3 font-mono text-slate-500">{{ $p->supplier?->gstin ?: 'UNREGISTERED' }}</td>
                            <td class="py-2.5 px-3 font-bold text-slate-800">{{ $p->supplier_name }}</td>
                            <td class="py-2.5 px-3 text-right font-mono">{{ number_format($p->subtotal, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono text-emerald-700 font-bold">{{ number_format($p->tax_amount, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">{{ number_format($p->grand_total, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400">No purchases found for ITC in this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @else
    <!-- GSTR-3B SECTION -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wider border-b pb-2">3.1 & 4.0 - Net GST Tax Liability Computation</h2>
            <div class="space-y-3 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <div>
                        <div class="font-bold text-slate-800">1. Output Tax Liability on Outward Supplies (Sales)</div>
                        <div class="text-[11px] text-slate-500">Collected from diners & customer bills</div>
                    </div>
                    <div class="font-mono font-bold text-blue-700 text-sm">
                        {{ $currency }} {{ number_format($totalSalesTax, 2) }}
                    </div>
                </div>

                <div class="flex justify-between py-2 border-b border-slate-100">
                    <div>
                        <div class="font-bold text-slate-800">2. Less: Eligible Input Tax Credit (ITC) from Purchases</div>
                        <div class="text-[11px] text-slate-500">Paid to registered grocery/produce suppliers</div>
                    </div>
                    <div class="font-mono font-bold text-emerald-700 text-sm">
                        - {{ $currency }} {{ number_format($totalPurchaseTax, 2) }}
                    </div>
                </div>

                <div class="flex justify-between py-3 border-t-2 border-red-600 bg-red-50 p-3 rounded font-black text-sm">
                    <div>
                        <div class="text-red-950 font-black">(=) Net GST Payable to Government:</div>
                        <div class="text-[11px] text-red-700 font-normal">Electronic Cash Ledger settlement required</div>
                    </div>
                    <div class="font-mono font-black text-red-800 text-base">
                        {{ $currency }} {{ number_format($netTaxPayable, 2) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

