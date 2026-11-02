@extends('layouts.app')

@section('title', 'Receipt Vouchers')
@section('page_title', 'Customer Receipt Voucher (Shortcut: b)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: b</span>
                <h1 class="text-xl font-bold text-slate-800">Customer Receipt Vouchers</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Collect and record payments received from customers / sundry debtors</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="document.getElementById('newReceiptModal').classList.remove('hidden')" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>Record New Receipt</span>
            </button>
        </div>
    </div>

    <!-- Receipts Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Receipt #</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Received From</th>
                        <th class="py-3 px-4">Deposited To</th>
                        <th class="py-3 px-4">Payment Mode</th>
                        <th class="py-3 px-4">Ref / Cheque #</th>
                        <th class="py-3 px-4 text-right">Amount Received</th>
                        <th class="py-3 px-4">Narration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($receipts as $r)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-mono font-bold text-emerald-800">{{ $r->voucher_no }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $r->voucher_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 font-bold text-slate-800">{{ $r->party_name }}</td>
                        <td class="py-3 px-4 text-slate-600 font-semibold">{{ $r->debit_ledger }}</td>
                        <td class="py-3 px-4 text-slate-700 uppercase font-mono">{{ $r->payment_method }}</td>
                        <td class="py-3 px-4 text-slate-500 font-mono">{{ $r->reference_no ?: '-' }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700">{{ $currency }} {{ number_format($r->amount, 2) }}</td>
                        <td class="py-3 px-4 text-slate-500 truncate max-w-xs">{{ $r->narration }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-receipt text-3xl mb-2"></i>
                            <p class="font-medium">No customer receipts recorded yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($receipts->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $receipts->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal: New Receipt Voucher -->
<div id="newReceiptModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
        <div class="bg-emerald-800 text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-hand-holding-dollar mr-2"></i> Record Receipt Voucher</h3>
            <button onclick="document.getElementById('newReceiptModal').classList.add('hidden')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('accounting.receipt.store') }}" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Receipt Date *</label>
                    <input type="date" name="voucher_date" value="{{ date('Y-m-d') }}" required class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Customer / Party *</label>
                    <select name="customer_id" required class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                        <option value="">-- Choose Customer --</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Due: ₹{{ number_format($c->balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Amount Received (₹) *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="w-full border border-slate-300 rounded px-2.5 py-1.5 font-bold font-mono text-emerald-800 text-right">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Payment Mode</label>
                    <select name="payment_method" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                        <option value="cash">Cash in Hand</option>
                        <option value="upi">UPI / QR Code</option>
                        <option value="bank">Bank Transfer (NEFT/RTGS)</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Deposit To (Debit Ledger) *</label>
                <select name="deposit_to" required class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                    <option value="Cash in Hand Account">Cash in Hand Account</option>
                    <option value="Primary Bank Account (HDFC)">Primary Bank Account (HDFC)</option>
                    <option value="UPI / QR Digital Account">UPI / QR Digital Account</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Reference / Cheque #</label>
                <input type="text" name="reference_no" placeholder="e.g. UTR-9902 or Cheque 112003" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Narration / Remarks</label>
                <textarea name="narration" rows="2" class="w-full border border-slate-300 rounded px-2.5 py-1.5" placeholder="Payment received against outstanding due balance..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="document.getElementById('newReceiptModal').classList.add('hidden')" class="px-4 py-1.5 text-slate-600 hover:bg-slate-100 rounded font-bold">Cancel</button>
                <button type="submit" class="px-5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white rounded font-bold shadow">Save Receipt Voucher</button>
            </div>
        </form>
    </div>
</div>
@endsection

