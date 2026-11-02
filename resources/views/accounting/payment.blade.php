@extends('layouts.app')

@section('title', 'Payment Vouchers')
@section('page_title', 'Payment Voucher (Shortcut: d)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: d</span>
                <h1 class="text-xl font-bold text-slate-800">Outward Payment Vouchers</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Disburse payments to vendors, suppliers, and operating expenses</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="document.getElementById('newPaymentModal').classList.remove('hidden')" class="px-4 py-2 bg-red-700 hover:bg-red-800 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>Record New Payment</span>
            </button>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Payment #</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Paid To (Payee)</th>
                        <th class="py-3 px-4">Debit Ledger</th>
                        <th class="py-3 px-4">Paid From</th>
                        <th class="py-3 px-4">Payment Mode</th>
                        <th class="py-3 px-4 text-right">Amount Paid</th>
                        <th class="py-3 px-4">Narration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($payments as $p)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-mono font-bold text-red-800">{{ $p->voucher_no }}</td>
                        <td class="py-3 px-4 text-slate-600">{{ $p->voucher_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 font-bold text-slate-800">{{ $p->party_name }}</td>
                        <td class="py-3 px-4 text-slate-600 font-semibold">{{ $p->debit_ledger }}</td>
                        <td class="py-3 px-4 text-slate-700">{{ $p->credit_ledger }}</td>
                        <td class="py-3 px-4 text-slate-700 uppercase font-mono">{{ $p->payment_method }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-red-700">{{ $currency }} {{ number_format($p->amount, 2) }}</td>
                        <td class="py-3 px-4 text-slate-500 truncate max-w-xs">{{ $p->narration }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-money-bill-transfer text-3xl mb-2"></i>
                            <p class="font-medium">No payment vouchers recorded yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal: New Payment Voucher -->
<div id="newPaymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
        <div class="bg-red-800 text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-money-bill-transfer mr-2"></i> Record Payment Voucher</h3>
            <button onclick="document.getElementById('newPaymentModal').classList.add('hidden')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('accounting.payment.store') }}" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Payment Date *</label>
                    <input type="date" name="voucher_date" value="{{ date('Y-m-d') }}" required class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Supplier / Vendor</label>
                    <select name="supplier_id" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                        <option value="">-- Choose Supplier or General --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} (Due: ₹{{ number_format($s->current_balance, 2) }})</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Payee Name (if not supplier)</label>
                <input type="text" name="party_name" placeholder="e.g. Landlord Rent / Gas Cylinder Agency" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Amount Paid (₹) *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="w-full border border-slate-300 rounded px-2.5 py-1.5 font-bold font-mono text-red-800 text-right">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Payment Mode</label>
                    <select name="payment_method" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                        <option value="cash">Cash in Hand</option>
                        <option value="bank">Bank Transfer (NEFT/RTGS)</option>
                        <option value="upi">UPI / Online</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Paid From (Credit Ledger) *</label>
                <select name="paid_from" required class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                    <option value="Cash in Hand Account">Cash in Hand Account</option>
                    <option value="Primary Bank Account (HDFC)">Primary Bank Account (HDFC)</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Reference / Cheque #</label>
                <input type="text" name="reference_no" placeholder="e.g. Bank Ref # or Cheque Number" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Narration / Remarks</label>
                <textarea name="narration" rows="2" class="w-full border border-slate-300 rounded px-2.5 py-1.5" placeholder="Payment disbursed against invoice..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="document.getElementById('newPaymentModal').classList.add('hidden')" class="px-4 py-1.5 text-slate-600 hover:bg-slate-100 rounded font-bold">Cancel</button>
                <button type="submit" class="px-5 py-1.5 bg-red-700 hover:bg-red-800 text-white rounded font-bold shadow">Save Payment Voucher</button>
            </div>
        </form>
    </div>
</div>
@endsection

