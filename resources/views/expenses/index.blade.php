@extends('layouts.app')

@section('title', 'Expenses')
@section('page_title', 'Expense Management')

@section('content')
<div class="space-y-6">
    <!-- Top Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Expenses (Period)</p>
                <h4 class="text-2xl font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($totalExpense, 2) }}</h4>
                <p class="text-xs text-slate-400 mt-1">{{ $startDate }} to {{ $endDate }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Cash Expenses</p>
                <h4 class="text-2xl font-black text-rose-600 mt-1">{{ $currency }}{{ number_format($cashExpense, 2) }}</h4>
                <p class="text-xs text-slate-400 mt-1">Paid directly from register</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Categories</p>
                <h4 class="text-2xl font-black text-slate-800 mt-1">{{ $categories->count() }}</h4>
                <p class="text-xs text-slate-400 mt-1">Classification heads</p>
            </div>
            <button type="button" onclick="openCategoriesModal()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition">
                Manage Heads
            </button>
        </div>
    </div>

    <!-- Filters & Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <input type="date" name="start_date" value="{{ $startDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
            <span class="text-slate-400 text-xs">to</span>
            <input type="date" name="end_date" value="{{ $endDate }}" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">

            <select name="category_id" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <select name="payment_method" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                <option value="">All Methods</option>
                <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="upi" {{ request('payment_method') === 'upi' ? 'selected' : '' }}>UPI</option>
                <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                <option value="bank_transfer" {{ request('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
            </select>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold">Filter</button>
            @if(request()->hasAny(['category_id', 'payment_method']))
            <a href="{{ route('expenses.index') }}" class="px-2.5 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-xs">Clear</a>
            @endif
        </form>

        <button type="button" onclick="openAddModal()" class="w-full sm:w-auto px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Add Expense</span>
        </button>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($expenses->isEmpty())
        <div class="py-12 text-center text-slate-400">
            <i class="fa-solid fa-wallet text-3xl mb-2 text-slate-300 block"></i>
            <p class="text-sm font-semibold text-slate-600">No Expenses Recorded</p>
            <p class="text-xs text-slate-400 mt-1">No expense records found for this period.</p>
            <button type="button" onclick="openAddModal()" class="mt-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold inline-flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Add Expense</span>
            </button>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4">Payment Method</th>
                        <th class="py-3 px-4 text-right">Amount</th>
                        <th class="py-3 px-4">Recorded By</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($expenses as $exp)
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
                        <td class="py-3 px-4 text-right font-black text-rose-600 text-sm">
                            {{ $currency }}{{ number_format($exp->amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-slate-500">{{ $exp->user?->name ?? 'Staff' }}</td>
                        <td class="py-3 px-4 text-center space-x-1">
                            <button type="button" onclick="openEditModal({{ json_encode($exp) }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <form action="{{ route('expenses.destroy', $exp) }}" method="POST" class="inline" onsubmit="return confirm('Delete this expense entry?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $expenses->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add/Edit Expense Modal -->
<div id="expenseModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 id="modalTitle" class="text-sm font-bold">Record Expense</h3>
            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="expenseForm" method="POST" action="{{ route('expenses.store') }}" class="p-6 space-y-4">
            @csrf
            <div id="methodSpoof"></div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Date *</label>
                    <input type="date" name="date" id="expDate" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Amount ({{ $currency }}) *</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="expAmount" required placeholder="0.00" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-black">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Category *</label>
                <select name="expense_category_id" id="expCategory" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Payment Method *</label>
                <select name="payment_method" id="expMethod" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                    <option value="cash">Cash (Register Outflow)</option>
                    <option value="upi">UPI / Online</option>
                    <option value="card">Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Description / Bill Notes</label>
                <textarea name="description" id="expDesc" rows="2" placeholder="e.g. Purchased fresh tomatoes & mint from market" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold">Save Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Manage Categories -->
<div id="categoriesModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 class="text-sm font-bold">Manage Expense Heads</h3>
            <button type="button" onclick="document.getElementById('categoriesModal').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="p-6 space-y-4">
            <form action="{{ route('expenses.category.store') }}" method="POST" class="space-y-3 pb-4 border-b border-slate-200">
                @csrf
                <label class="block text-xs font-bold text-slate-700 uppercase">New Expense Category</label>
                <div class="flex gap-2">
                    <input type="text" name="name" required placeholder="Category name..." class="flex-1 px-3 py-2 rounded-xl border border-slate-300 text-xs">
                    <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-xl text-xs font-bold">Add</button>
                </div>
            </form>

            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Existing Heads</p>
                <div class="space-y-1.5 max-h-48 overflow-y-auto">
                    @foreach($categories as $cat)
                    <div class="p-2 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-800">{{ $cat->name }}</span>
                        @if($cat->expenses()->count() == 0)
                        <form action="{{ route('expenses.category.destroy', $cat) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-500 hover:text-rose-700"><i class="fa-solid fa-trash-can"></i></button>
                        </form>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const modal = document.getElementById('expenseModal');
    const form = document.getElementById('expenseForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodSpoof = document.getElementById('methodSpoof');

    function openAddModal() {
        modalTitle.innerText = 'Record Expense';
        form.action = "{{ route('expenses.store') }}";
        methodSpoof.innerHTML = '';
        document.getElementById('expDate').value = "{{ date('Y-m-d') }}";
        document.getElementById('expAmount').value = '';
        document.getElementById('expMethod').value = 'cash';
        document.getElementById('expDesc').value = '';
        modal.classList.remove('hidden');
    }

    function openEditModal(exp) {
        modalTitle.innerText = 'Edit Expense #' + exp.id;
        form.action = "/expenses/" + exp.id;
        methodSpoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('expDate').value = exp.date ? exp.date.substring(0, 10) : '';
        document.getElementById('expAmount').value = exp.amount;
        document.getElementById('expCategory').value = exp.expense_category_id;
        document.getElementById('expMethod').value = exp.payment_method;
        document.getElementById('expDesc').value = exp.description || '';
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function openCategoriesModal() {
        document.getElementById('categoriesModal').classList.remove('hidden');
    }
</script>
@endpush
@endsection
