@extends('layouts.app')

@section('title', 'Stock Vouchers')
@section('page_title', 'Stock Vouchers & Adjustments (Shortcut: 7)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-xs font-bold rounded">Shortcut: 7</span>
                <h1 class="text-xl font-bold text-slate-800">Stock Vouchers & Physical Adjustments</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Audit log of inventory movements, manual stock corrections, wastage, and kitchen consumption</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="document.getElementById('newVoucherModal').classList.remove('hidden')" class="px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus"></i>
                <span>Create Stock Voucher</span>
            </button>
        </div>
    </div>

    <!-- Vouchers Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Item Name</th>
                        <th class="py-3 px-4 text-center">Movement Type</th>
                        <th class="py-3 px-4 text-right">Quantity</th>
                        <th class="py-3 px-4">Reason / Narration</th>
                        <th class="py-3 px-4">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($movements as $m)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-2.5 px-4 text-slate-600 font-mono">{{ $m->created_at->format('d/m/Y h:i A') }}</td>
                        <td class="py-2.5 px-4 font-bold text-slate-800">{{ $m->inventoryItem?->name ?? 'General Item' }}</td>
                        <td class="py-2.5 px-4 text-center">
                            @if($m->type === 'in')
                                <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded-full text-[10px] uppercase">
                                    <i class="fa-solid fa-arrow-down mr-1"></i> Stock In
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 bg-red-100 text-red-800 font-bold rounded-full text-[10px] uppercase">
                                    <i class="fa-solid fa-arrow-up mr-1"></i> Stock Out / Waste
                                </span>
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-right font-mono font-bold {{ $m->type === 'in' ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $m->type === 'in' ? '+' : '-' }}{{ number_format($m->quantity, 2) }}
                        </td>
                        <td class="py-2.5 px-4 text-slate-600">{{ $m->reason ?: 'Physical stock adjustment' }}</td>
                        <td class="py-2.5 px-4 text-slate-500 font-semibold">{{ $m->user?->name ?? 'Admin' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">
                            <i class="fa-solid fa-boxes-stacked text-3xl mb-2"></i>
                            <p class="font-medium">No stock adjustment vouchers recorded.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
        <div class="p-4 border-t border-slate-200">
            {{ $movements->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal: New Stock Voucher -->
<div id="newVoucherModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
        <div class="bg-blue-800 text-white px-5 py-3 flex items-center justify-between">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-boxes-stacked mr-2"></i> Record Stock Voucher</h3>
            <button onclick="document.getElementById('newVoucherModal').classList.add('hidden')" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="{{ route('inventory-reports.stock-vouchers.store') }}" method="POST" class="p-5 space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Item Category Type *</label>
                <select name="item_type" id="itemTypeSelect" onchange="toggleItemSelect(this.value)" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                    <option value="food">Menu / Food Item</option>
                    <option value="inventory">Raw Inventory Material</option>
                </select>
            </div>

            <div id="foodSelectDiv">
                <label class="block font-bold text-slate-700 mb-1">Select Food Item *</label>
                <select name="item_id" id="foodSelectId" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                    @foreach($foods as $f)
                        <option value="{{ $f->id }}">{{ $f->name }} (Current: {{ $f->current_stock ?? 0 }})</option>
                    @endforeach
                </select>
            </div>

            <div id="invSelectDiv" class="hidden">
                <label class="block font-bold text-slate-700 mb-1">Select Raw Material *</label>
                <select name="item_id" id="invSelectId" disabled class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                    @foreach($inventoryItems as $inv)
                        <option value="{{ $inv->id }}">{{ $inv->name }} (Current: {{ $inv->current_stock ?? 0 }} {{ $inv->unit }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Voucher Action *</label>
                    <select name="type" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                        <option value="in">Stock In (Add Qty)</option>
                        <option value="out">Stock Out (Deduct Qty)</option>
                        <option value="waste">Wastage / Spoilage</option>
                        <option value="adjustment">Physical Count Adjustment</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Quantity *</label>
                    <input type="number" step="0.01" min="0.01" name="quantity" required placeholder="0.00" class="w-full border border-slate-300 rounded px-2.5 py-1.5 text-right font-mono font-bold">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Reason / Notes *</label>
                <textarea name="reason" rows="2" required placeholder="e.g. Expired batch discarded, kitchen prep shrinkage, supplier bonus qty..." class="w-full border border-slate-300 rounded px-2.5 py-1.5"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="document.getElementById('newVoucherModal').classList.add('hidden')" class="px-4 py-1.5 text-slate-600 hover:bg-slate-100 rounded font-bold">Cancel</button>
                <button type="submit" class="px-5 py-1.5 bg-blue-700 hover:bg-blue-800 text-white rounded font-bold shadow">Save Voucher</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleItemSelect(type) {
        const foodDiv = document.getElementById('foodSelectDiv');
        const invDiv = document.getElementById('invSelectDiv');
        const foodId = document.getElementById('foodSelectId');
        const invId = document.getElementById('invSelectId');

        if (type === 'food') {
            foodDiv.classList.remove('hidden');
            invDiv.classList.add('hidden');
            foodId.disabled = false;
            invId.disabled = true;
        } else {
            foodDiv.classList.add('hidden');
            invDiv.classList.remove('hidden');
            foodId.disabled = true;
            invId.disabled = false;
        }
    }
</script>
@endsection

