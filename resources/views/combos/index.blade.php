@extends('layouts.app')

@section('title', 'Combo Packs')
@section('page_title', 'Combo Food Management')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('combos.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search combo name or code..."
                class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 w-full sm:w-60">
            <select name="status" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <option value="">All Status</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold">Filter</button>
            @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('combos.index') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs">Clear</a>
            @endif
        </form>

        <button type="button" onclick="openAddModal()" class="w-full sm:w-auto px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Add Combo Pack</span>
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($combos->isEmpty())
        <div class="py-12 text-center text-slate-400">
            <i class="fa-solid fa-box-archive text-3xl mb-2 text-slate-300 block"></i>
            <p class="text-sm font-semibold text-slate-600">No Combo Food Items Found</p>
            <p class="text-xs text-slate-400 mt-1">Bundle multiple foods into value meal combos.</p>
            <button type="button" onclick="openAddModal()" class="mt-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold inline-flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Add Combo Pack</span>
            </button>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Combo Code</th>
                        <th class="py-3 px-4">Combo Name</th>
                        <th class="py-3 px-4">Included Food Items</th>
                        <th class="py-3 px-4 text-right">Price</th>
                        <th class="py-3 px-4 text-center">Tax %</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($combos as $combo)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">
                            <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-700 font-bold">{{ $combo->code }}</span>
                        </td>
                        <td class="py-3 px-4 font-bold text-slate-900 text-sm">
                            {{ $combo->name }}
                            @if($combo->description)
                            <p class="text-xs text-slate-400 font-normal">{{ $combo->description }}</p>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex flex-wrap gap-1">
                                @foreach($combo->foods as $f)
                                <span class="px-2 py-0.5 bg-orange-50 border border-orange-200 text-orange-800 rounded text-[11px] font-semibold">
                                    {{ (float) $f->pivot->quantity }}x {{ $f->name }}
                                </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">{{ $currency }}{{ number_format($combo->price, 2) }}</td>
                        <td class="py-3 px-4 text-center font-medium text-slate-600">{{ number_format($combo->tax_rate, 1) }}%</td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('combos.status', $combo) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase transition {{ $combo->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $combo->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3 px-4 text-center space-x-1">
                            <button type="button" onclick="openEditModal({{ json_encode($combo) }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <form action="{{ route('combos.destroy', $combo) }}" method="POST" class="inline" onsubmit="return confirm('Delete this combo?')">
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
            {{ $combos->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal -->
<div id="comboModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between sticky top-0 z-10">
            <h3 id="modalTitle" class="text-sm font-bold">Add Combo Pack</h3>
            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="comboForm" method="POST" action="{{ route('combos.store') }}" class="p-6 space-y-4">
            @csrf
            <div id="methodSpoof"></div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Combo Code *</label>
                    <input type="text" name="code" id="comboCode" required placeholder="e.g. CMB101" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 uppercase font-mono font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Combo Name *</label>
                    <input type="text" name="name" id="comboName" required placeholder="e.g. Biriyani Feast" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Combo Price ({{ $currency }}) *</label>
                    <input type="number" step="0.01" name="price" id="comboPrice" required min="0" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tax Rate (%) *</label>
                    <input type="number" step="0.01" name="tax_rate" id="comboTax" required min="0" max="100" value="5.00" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Description</label>
                <textarea name="description" id="comboDesc" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500"></textarea>
            </div>

            <div class="border-t border-slate-200 pt-3">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold text-slate-800 uppercase">Included Food Items *</label>
                    <button type="button" onclick="addComboItemRow()" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 rounded text-xs font-semibold text-slate-700">
                        <i class="fa-solid fa-plus mr-1"></i> Add Item
                    </button>
                </div>
                <div id="comboItemsContainer" class="space-y-2"></div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="comboActive" value="1" checked class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                <label for="comboActive" class="text-xs font-semibold text-slate-700">Active and available in POS</label>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold">Save Combo</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const availableFoods = @json($foods);
    const modal = document.getElementById('comboModal');
    const form = document.getElementById('comboForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodSpoof = document.getElementById('methodSpoof');
    const comboContainer = document.getElementById('comboItemsContainer');
    let itemIdx = 0;

    function addComboItemRow(foodId = '', qty = 1) {
        const rowId = `combo_item_${itemIdx}`;
        let options = '<option value="">Select Food</option>';
        availableFoods.forEach(f => {
            options += `<option value="${f.id}" ${f.id == foodId ? 'selected' : ''}>${f.name} (${f.code})</option>`;
        });

        const html = `
            <div id="${rowId}" class="flex items-center gap-2">
                <select name="items[${itemIdx}][food_id]" required class="flex-1 px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                    ${options}
                </select>
                <input type="number" min="1" name="items[${itemIdx}][quantity]" value="${qty}" class="w-24 px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold" placeholder="Qty">
                <button type="button" onclick="document.getElementById('${rowId}').remove()" class="p-2 text-rose-500 hover:bg-rose-50 rounded">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
        comboContainer.insertAdjacentHTML('beforeend', html);
        itemIdx++;
    }

    function openAddModal() {
        modalTitle.innerText = 'Add Combo Pack';
        form.action = "{{ route('combos.store') }}";
        methodSpoof.innerHTML = '';
        document.getElementById('comboCode').value = '';
        document.getElementById('comboName').value = '';
        document.getElementById('comboPrice').value = '';
        document.getElementById('comboTax').value = '5.00';
        document.getElementById('comboDesc').value = '';
        document.getElementById('comboActive').checked = true;
        comboContainer.innerHTML = '';
        addComboItemRow();
        modal.classList.remove('hidden');
    }

    function openEditModal(combo) {
        modalTitle.innerText = 'Edit Combo: ' + combo.name;
        form.action = "{{ route('combos.update', ':id') }}".replace(':id', combo.id);
        methodSpoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('comboCode').value = combo.code;
        document.getElementById('comboName').value = combo.name;
        document.getElementById('comboPrice').value = combo.price;
        document.getElementById('comboTax').value = combo.tax_rate;
        document.getElementById('comboDesc').value = combo.description || '';
        document.getElementById('comboActive').checked = combo.is_active;

        comboContainer.innerHTML = '';
        if (combo.foods && combo.foods.length > 0) {
            combo.foods.forEach(f => {
                addComboItemRow(f.id, f.pivot ? f.pivot.quantity : 1);
            });
        } else {
            addComboItemRow();
        }

        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }
</script>
@endpush
@endsection
