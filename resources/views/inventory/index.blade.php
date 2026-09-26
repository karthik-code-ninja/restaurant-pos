@extends('layouts.app')

@section('title', 'Inventory & Stock')
@section('page_title', 'Raw Material & Stock Management')

@section('content')
<div class="space-y-6">
    <!-- Top Summary Banner -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Raw Materials</p>
                <h4 class="text-2xl font-black text-slate-900 mt-1">{{ $totalItems }}</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg font-bold">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border {{ $lowStockCount > 0 ? 'border-rose-300 bg-rose-50/20' : 'border-slate-200' }} shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-500' }} uppercase tracking-wider">Low Stock Alerts</p>
                <h4 class="text-2xl font-black {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900' }} mt-1">{{ $lowStockCount }} Items</h4>
            </div>
            <div class="w-10 h-10 rounded-xl {{ $lowStockCount > 0 ? 'bg-rose-100 text-rose-600' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center text-lg font-bold">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Auto Consumption</p>
                <h4 class="text-sm font-bold text-slate-800 mt-1">Recipe Based</h4>
                <p class="text-[11px] text-slate-400">Deducts automatically on bill settlement</p>
            </div>
            <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-lg font-bold">
                <i class="fa-solid fa-calculator"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('inventory.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search stock item..."
                class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 w-full sm:w-60">

            <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-600 cursor-pointer px-2">
                <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} onchange="this.form.submit()" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                <span>Low Stock Only</span>
            </label>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold">Filter</button>
            @if(request()->hasAny(['search', 'low_stock']))
            <a href="{{ route('inventory.index') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs">Clear</a>
            @endif
        </form>

        <button type="button" onclick="openAddModal()" class="w-full sm:w-auto px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Add Stock Item</span>
        </button>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($items->isEmpty())
        <div class="py-12 text-center text-slate-400">
            <i class="fa-solid fa-boxes-stacked text-3xl mb-2 text-slate-300 block"></i>
            <p class="text-sm font-semibold text-slate-600">No Inventory Items Found</p>
            <p class="text-xs text-slate-400 mt-1">Start tracking raw ingredients by adding inventory items.</p>
            <button type="button" onclick="openAddModal()" class="mt-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold inline-flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Add Stock Item</span>
            </button>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Item Code</th>
                        <th class="py-3 px-4">Material Name</th>
                        <th class="py-3 px-4 text-center">Unit</th>
                        <th class="py-3 px-4 text-right">Opening Stock</th>
                        <th class="py-3 px-4 text-right">Current Stock</th>
                        <th class="py-3 px-4 text-right">Min Alert Level</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($items as $item)
                    @php
                        $isLow = $item->current_stock <= $item->min_stock_alert;
                    @endphp
                    <tr class="hover:bg-slate-50/80 transition {{ $isLow ? 'bg-rose-50/30' : '' }}">
                        <td class="py-3 px-4 font-mono font-bold text-slate-700">{{ $item->code }}</td>
                        <td class="py-3 px-4 font-bold text-slate-900 text-sm">
                            {{ $item->name }}
                            @if($isLow)
                            <span class="ml-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">LOW STOCK</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded bg-slate-100 font-mono text-slate-600 font-bold uppercase">{{ $item->unit }}</span>
                        </td>
                        <td class="py-3 px-4 text-right text-slate-600 font-medium">{{ (float) $item->opening_stock }} {{ $item->unit }}</td>
                        <td class="py-3 px-4 text-right font-black text-sm {{ $isLow ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ (float) $item->current_stock }} {{ $item->unit }}
                        </td>
                        <td class="py-3 px-4 text-right text-slate-500 font-semibold">{{ (float) $item->min_stock_alert }} {{ $item->unit }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $item->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center space-x-1">
                            <!-- Quick Adjust Button -->
                            <button type="button" onclick="openAdjustModal({{ json_encode($item) }})" class="px-2 py-1 bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold rounded text-xs" title="Stock Adjustment">
                                <i class="fa-solid fa-sliders mr-1"></i> Adjust
                            </button>
                            <!-- History -->
                            <a href="{{ route('inventory.history', $item) }}" class="p-1.5 text-slate-600 hover:bg-slate-100 rounded inline-block" title="Stock Movements History">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </a>
                            <!-- Edit -->
                            <button type="button" onclick="openEditModal({{ json_encode($item) }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <!-- Delete -->
                            <form action="{{ route('inventory.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Delete this stock item?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded" title="Delete">
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
            {{ $items->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal 1: Add/Edit Stock Item -->
<div id="itemModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 id="modalTitle" class="text-sm font-bold">Add Raw Material</h3>
            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="itemForm" method="POST" action="{{ route('inventory.store') }}" class="p-6 space-y-4">
            @csrf
            <div id="methodSpoof"></div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Item Code *</label>
                    <input type="text" name="code" id="itemCode" required placeholder="e.g. RAW06" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 uppercase font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Unit of Measure *</label>
                    <select name="unit" id="itemUnit" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                        <option value="kg">kg (Kilogram)</option>
                        <option value="g">g (Gram)</option>
                        <option value="l">l (Liter)</option>
                        <option value="ml">ml (Milliliter)</option>
                        <option value="pcs">pcs (Pieces)</option>
                        <option value="pkt">pkt (Packets)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Material Name *</label>
                <input type="text" name="name" id="itemName" required placeholder="e.g. Basmati Rice" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div id="openingStockGroup">
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Opening Stock *</label>
                    <input type="number" step="0.001" min="0" name="opening_stock" id="itemOpening" value="0.000" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Min Alert Level *</label>
                    <input type="number" step="0.001" min="0" name="min_stock_alert" id="itemAlert" value="5.000" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="itemActive" value="1" checked class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                <label for="itemActive" class="text-xs font-semibold text-slate-700">Active Material</label>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold">Save Material</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Stock Adjustment Modal -->
<div id="adjustModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-amber-900 text-white flex items-center justify-between">
            <h3 id="adjustModalTitle" class="text-sm font-bold">Stock Adjustment</h3>
            <button type="button" onclick="document.getElementById('adjustModal').classList.add('hidden')" class="text-amber-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="adjustForm" method="POST" action="" class="p-6 space-y-4">
            @csrf
            <div>
                <p class="text-xs text-slate-500">Adjusting stock for: <strong id="adjustItemName" class="text-slate-800"></strong></p>
                <p class="text-xs text-slate-500">Current Stock: <strong id="adjustCurrentStock" class="text-slate-900"></strong></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Adjustment Type *</label>
                <select name="type" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-amber-500 font-bold">
                    <option value="adjustment_add">+ Add Stock (Inward / Found stock)</option>
                    <option value="adjustment_reduce">- Reduce Stock (Wastage / Spoilage / Correction)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Quantity *</label>
                <input type="number" step="0.001" min="0.001" name="quantity" required placeholder="Quantity to adjust" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-amber-500 font-black text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Reason / Notes</label>
                <input type="text" name="notes" placeholder="e.g. Physical inventory count correction" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-amber-500">
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('adjustModal').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold">Confirm Adjustment</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const modal = document.getElementById('itemModal');
    const form = document.getElementById('itemForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodSpoof = document.getElementById('methodSpoof');
    const openingGroup = document.getElementById('openingStockGroup');

    function openAddModal() {
        modalTitle.innerText = 'Add Raw Material';
        form.action = "{{ route('inventory.store') }}";
        methodSpoof.innerHTML = '';
        openingGroup.classList.remove('hidden');
        document.getElementById('itemCode').value = '';
        document.getElementById('itemName').value = '';
        document.getElementById('itemUnit').value = 'kg';
        document.getElementById('itemOpening').value = '0.000';
        document.getElementById('itemAlert').value = '5.000';
        document.getElementById('itemActive').checked = true;
        modal.classList.remove('hidden');
    }

    function openEditModal(item) {
        modalTitle.innerText = 'Edit Material: ' + item.name;
        form.action = "{{ route('inventory.update', ['item' => ':id']) }}".replace(':id', item.id);
        methodSpoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        openingGroup.classList.add('hidden'); // Opening stock cannot be altered directly after creation
        document.getElementById('itemCode').value = item.code;
        document.getElementById('itemName').value = item.name;
        document.getElementById('itemUnit').value = item.unit;
        document.getElementById('itemAlert').value = item.min_stock_alert;
        document.getElementById('itemActive').checked = item.is_active;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function openAdjustModal(item) {
        document.getElementById('adjustItemName').innerText = `${item.name} (${item.code})`;
        document.getElementById('adjustCurrentStock').innerText = `${parseFloat(item.current_stock)} ${item.unit}`;
        document.getElementById('adjustForm').action = "{{ route('inventory.adjust', ['item' => ':id']) }}".replace(':id', item.id);
        document.getElementById('adjustModal').classList.remove('hidden');
    }
</script>
@endpush
@endsection
