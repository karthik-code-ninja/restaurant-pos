@extends('layouts.app')

@section('title', 'Purchase Returns / Debit Notes')
@section('page_title', 'Purchase Return / Debit Notes (Shortcut: i)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded">Shortcut: i</span>
                <h1 class="text-xl font-bold text-slate-800">Purchase Returns / Debit Notes</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Return defective or excess goods to suppliers with automatic stock adjustment and ledger credit</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="document.getElementById('newReturnModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>Record Purchase Return</span>
            </button>
        </div>
    </div>

    <!-- Returns Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Return # (Debit Note)</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Supplier Name</th>
                        <th class="py-3 px-4">Original Purchase</th>
                        <th class="py-3 px-4 text-right">Items Count</th>
                        <th class="py-3 px-4 text-right">Debit Amount</th>
                        <th class="py-3 px-4 text-center">Refund Status</th>
                        <th class="py-3 px-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($purchaseReturns as $pr)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-mono font-bold text-blue-800">
                            <a href="{{ route('purchase-returns.show', $pr) }}" class="hover:underline">{{ $pr->return_no }}</a>
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $pr->return_date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 font-bold text-slate-800">{{ $pr->supplier_name }}</td>
                        <td class="py-3 px-4 text-slate-500 font-mono">{{ $pr->purchase?->purchase_no ?: '-' }}</td>
                        <td class="py-3 px-4 text-right font-mono">{{ $pr->items->count() }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-900">{{ $currency }} {{ number_format($pr->total_amount, 2) }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded-full text-[10px] uppercase">{{ $pr->refund_status }}</span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('purchase-returns.show', $pr) }}" title="View Debit Note" class="p-1 text-slate-600 hover:text-blue-600 transition">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-arrow-rotate-left text-3xl mb-2"></i>
                            <p class="font-medium">No purchase returns recorded yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Return -->
<div id="newReturnModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full overflow-hidden border border-slate-200">
        <div class="bg-slate-900 text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-arrow-rotate-left mr-2 text-blue-400"></i> Record Purchase Return (Debit Note)</h3>
            <button onclick="document.getElementById('newReturnModal').classList.add('hidden')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('purchase-returns.store') }}" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Return Date *</label>
                    <input type="date" name="return_date" value="{{ date('Y-m-d') }}" required class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Supplier *</label>
                    <select name="supplier_id" required class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                        <option value="">-- Choose Supplier --</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Adjustment Type</label>
                    <select name="refund_status" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                        <option value="adjusted">Adjust Against Due Balance</option>
                        <option value="refunded">Direct Cash/Bank Refund</option>
                        <option value="pending">Pending Credit Note</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2 border-t pt-3">
                <div class="font-bold text-slate-700">Returned Item Details:</div>
                <div class="grid grid-cols-12 gap-2">
                    <div class="col-span-5">
                        <input type="text" name="items[0][item_name]" placeholder="Item description" required class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                        <input type="hidden" name="items[0][item_type]" value="food">
                    </div>
                    <div class="col-span-2">
                        <input type="number" step="0.01" min="0.01" name="items[0][quantity]" value="1" placeholder="Qty" required class="w-full border border-slate-300 rounded px-2.5 py-1.5 text-right">
                    </div>
                    <div class="col-span-2">
                        <input type="text" name="items[0][unit]" value="Pcs" placeholder="Unit" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                    </div>
                    <div class="col-span-3">
                        <input type="number" step="0.01" min="0" name="items[0][unit_price]" value="100.00" placeholder="Return Rate" required class="w-full border border-slate-300 rounded px-2.5 py-1.5 text-right">
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Reason for Return</label>
                <textarea name="reason" rows="2" class="w-full border border-slate-300 rounded px-2.5 py-1.5" placeholder="e.g. Expired batch, packaging damaged, quality issue..."></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="document.getElementById('newReturnModal').classList.add('hidden')" class="px-4 py-1.5 text-slate-600 hover:bg-slate-100 rounded font-bold">Cancel</button>
                <button type="submit" class="px-5 py-1.5 bg-blue-700 hover:bg-blue-800 text-white rounded font-bold shadow">Save Debit Note</button>
            </div>
        </form>
    </div>
</div>
@endsection

