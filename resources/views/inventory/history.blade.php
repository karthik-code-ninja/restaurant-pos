@extends('layouts.app')

@section('title', 'Stock Movement History - ' . $item->name)
@section('page_title', 'Stock Movement Audit History')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('inventory.index') }}" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-left"></i></a>
                <h3 class="text-base font-bold text-slate-800">{{ $item->name }} ({{ $item->code }})</h3>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Current Stock: <strong class="text-slate-900">{{ (float) $item->current_stock }} {{ $item->unit }}</strong> &bull; Alert Level: {{ (float) $item->min_stock_alert }} {{ $item->unit }}</p>
        </div>

        <form method="GET" action="{{ route('inventory.history', $item) }}" class="flex items-center gap-2">
            <select name="type" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <option value="">All Movement Types</option>
                <option value="sale_deduction" {{ request('type') === 'sale_deduction' ? 'selected' : '' }}>Sale Deductions</option>
                <option value="cancellation_reversal" {{ request('type') === 'cancellation_reversal' ? 'selected' : '' }}>Bill Cancellations</option>
                <option value="adjustment_add" {{ request('type') === 'adjustment_add' ? 'selected' : '' }}>Manual Additions</option>
                <option value="adjustment_reduce" {{ request('type') === 'adjustment_reduce' ? 'selected' : '' }}>Manual Reductions</option>
                <option value="opening" {{ request('type') === 'opening' ? 'selected' : '' }}>Opening Stock</option>
            </select>
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">Filter</button>
            @if(request()->filled('type'))
            <a href="{{ route('inventory.history', $item) }}" class="px-2.5 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-xs">Clear</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($movements->isEmpty())
        <div class="py-12 text-center text-slate-400">
            <p class="text-xs font-semibold">No stock movements recorded for this item.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Type</th>
                        <th class="py-3 px-4 text-right">Quantity</th>
                        <th class="py-3 px-4 text-right">Previous</th>
                        <th class="py-3 px-4 text-right">New Stock</th>
                        <th class="py-3 px-4">Notes / Bill Reference</th>
                        <th class="py-3 px-4">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($movements as $m)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-mono text-slate-500">{{ $m->created_at->format('d/m/Y h:i A') }}</td>
                        <td class="py-3 px-4">
                            @if($m->type === 'sale_deduction')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">Sale Deduction</span>
                            @elseif($m->type === 'cancellation_reversal')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Reversal</span>
                            @elseif($m->type === 'adjustment_add')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">Manual Inward</span>
                            @elseif($m->type === 'adjustment_reduce')
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Manual Wastage</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700">Opening</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-black {{ $m->quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $m->quantity > 0 ? '+' : '' }}{{ (float) $m->quantity }} {{ $item->unit }}
                        </td>
                        <td class="py-3 px-4 text-right text-slate-500">{{ (float) $m->previous_stock }}</td>
                        <td class="py-3 px-4 text-right font-bold text-slate-900">{{ (float) $m->current_stock }}</td>
                        <td class="py-3 px-4 text-slate-600 max-w-xs truncate">{{ $m->notes ?? '-' }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $m->user?->name ?? 'System' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $movements->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
