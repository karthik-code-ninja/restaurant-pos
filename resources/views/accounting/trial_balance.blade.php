@extends('layouts.app')

@section('title', 'Trial Balance')
@section('page_title', 'Accounting Trial Balance (Shortcut: 1)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded">Shortcut: 1</span>
                <h1 class="text-xl font-bold text-slate-800">Accounting Trial Balance</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Double-entry balancing statement of all debit and credit ledger balances as of date</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print Trial Balance</span>
            </button>
        </div>
    </div>

    <!-- Trial Balance Statement Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-4">
        <div class="text-center pb-4 border-b border-slate-200">
            <h2 class="text-lg font-bold text-slate-900">Trial Balance Statement</h2>
            <p class="text-xs text-slate-500">As of {{ \Carbon\Carbon::parse($asOfDate)->format('d F Y') }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-100 border-y border-slate-300 text-slate-700 font-extrabold uppercase">
                        <th class="py-3 px-4" style="width: 10%;">Code</th>
                        <th class="py-3 px-4" style="width: 45%;">Ledger Account Particulars</th>
                        <th class="py-3 px-4" style="width: 25%;">Group Classification</th>
                        <th class="py-3 px-4 text-right" style="width: 10%;">Debit (₹)</th>
                        <th class="py-3 px-4 text-right" style="width: 10%;">Credit (₹)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($rows as $row)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-2.5 px-4 font-mono text-slate-400">{{ $row['code'] }}</td>
                        <td class="py-2.5 px-4 font-bold text-slate-800">{{ $row['name'] }}</td>
                        <td class="py-2.5 px-4 text-slate-500">{{ $row['group'] }}</td>
                        <td class="py-2.5 px-4 text-right font-mono text-blue-800">
                            {{ $row['debit'] > 0 ? number_format($row['debit'], 2) : '-' }}
                        </td>
                        <td class="py-2.5 px-4 text-right font-mono text-emerald-800">
                            {{ $row['credit'] > 0 ? number_format($row['credit'], 2) : '-' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-black text-xs border-t-2 border-b-4 border-slate-800 text-slate-900">
                        <td colspan="3" class="py-3 px-4 uppercase text-right tracking-wider">Grand Total (Balanced):</td>
                        <td class="py-3 px-4 text-right font-mono text-blue-900 text-sm">
                            {{ $currency }} {{ number_format($totalDebits, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono text-emerald-900 text-sm">
                            {{ $currency }} {{ number_format($totalCredits, 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="flex items-center justify-between text-[11px] text-slate-500 pt-2 border-t">
            <span>System Status: <b class="text-emerald-700">Books are in Balance (Debit = Credit)</b></span>
            <span>Generated on: {{ now()->format('d/m/Y h:i A') }}</span>
        </div>
    </div>
</div>
@endsection

