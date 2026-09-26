@extends('layouts.app')

@section('title', 'Expense Report')
@section('page_title', '9. Restaurant Expense Report')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('reports.expenses') }}" class="flex flex-wrap items-center gap-2">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <span class="text-slate-400 text-xs">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <select name="category_id" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">Filter</button>
        </form>

        <div class="flex items-center gap-3">
            <span class="font-bold text-xs text-rose-600">Total: {{ $currency }}{{ number_format($totalAmount, 2) }}</span>
            <button onclick="window.print()" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Print
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Expense Head / Category</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4">Payment Method</th>
                        <th class="py-3 px-4 text-right">Amount</th>
                        <th class="py-3 px-4">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($expenses as $exp)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ $exp->date->format('d/m/Y') }}</td>
                        <td class="py-3 px-4 font-bold text-slate-900">{{ $exp->category?->name }}</td>
                        <td class="py-3 px-4 text-slate-600 max-w-xs truncate">{{ $exp->description ?? '-' }}</td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                {{ $exp->payment_method === 'cash' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $exp->payment_method }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-black text-rose-600 text-sm">{{ $currency }}{{ number_format($exp->amount, 2) }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $exp->user?->name ?? 'Staff' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No expense records found for this period.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@endsection
