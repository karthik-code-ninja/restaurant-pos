@extends('layouts.app')

@section('title', 'Food Items')
@section('page_title', 'Food / Menu Management')

@section('content')
<div class="space-y-6">
    <!-- Filter & Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('foods.index') }}" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <div class="relative w-full sm:w-60">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name or food code..."
                    class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <select name="category_id" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <select name="is_veg" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <option value="">Veg & Non-Veg</option>
                <option value="1" {{ request('is_veg') === '1' ? 'selected' : '' }}>Veg Only</option>
                <option value="0" {{ request('is_veg') === '0' ? 'selected' : '' }}>Non-Veg Only</option>
            </select>

            <select name="status" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <option value="">All Status</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold">
                Filter
            </button>
            @if(request()->hasAny(['search', 'category_id', 'is_veg', 'status']))
            <a href="{{ route('foods.index') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs">Clear</a>
            @endif
        </form>

        <button type="button" onclick="openAddModal()" class="w-full md:w-auto px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Add Food Item</span>
        </button>
    </div>

    <!-- Foods Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($foods->isEmpty())
        <div class="py-12 text-center text-slate-400">
            <i class="fa-solid fa-bowl-food text-3xl mb-2 text-slate-300 block"></i>
            <p class="text-sm font-semibold text-slate-600">No Food Items Found</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria or add a new food item.</p>
            <button type="button" onclick="openAddModal()" class="mt-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold inline-flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>+ Add Food</span>
            </button>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Item Code</th>
                        <th class="py-3 px-4">Food Name</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4 text-center">Type</th>
                        <th class="py-3 px-4 text-right">Price</th>
                        <th class="py-3 px-4 text-center">GST %</th>
                        <th class="py-3 px-4">Ingredients Mapped</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($foods as $food)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 font-mono font-bold text-slate-900">
                            <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-700 font-bold">{{ $food->code }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ $food->is_veg ? 'bg-emerald-500 ring-2 ring-emerald-200' : 'bg-rose-500 ring-2 ring-rose-200' }}" title="{{ $food->is_veg ? 'Vegetarian' : 'Non-Vegetarian' }}"></span>
                                <span class="font-bold text-slate-800 text-sm">{{ $food->name }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-600">{{ $food->category?->name ?? 'Uncategorized' }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($food->is_veg)
                            <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">VEG</span>
                            @else
                            <span class="text-[11px] font-bold text-rose-700 bg-rose-50 px-2 py-0.5 rounded">NON-VEG</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">
                            {{ $currency }}{{ number_format($food->price, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center font-semibold text-slate-600">
                            {{ number_format($food->tax_rate, 1) }}%
                        </td>
                        <td class="py-3 px-4 text-slate-500">
                            @if($food->ingredients->isEmpty())
                            <span class="text-slate-400 italic text-[11px]">No ingredients mapped</span>
                            @else
                            <div class="flex flex-wrap gap-1">
                                @foreach($food->ingredients as $ing)
                                <span class="px-1.5 py-0.5 bg-slate-100 rounded text-[10px] text-slate-700">
                                    {{ $ing->name }} ({{ (float) $ing->pivot->quantity }}{{ $ing->unit }})
                                </span>
                                @endforeach
                            </div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('foods.status', $food) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase transition {{ $food->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' }}">
                                    {{ $food->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3 px-4 text-center space-x-1">
                            <button type="button" onclick="openEditModal({{ json_encode($food) }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <form action="{{ route('foods.destroy', $food) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete {{ $food->name }}?')">
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
            {{ $foods->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add / Edit Food Modal with Ingredient Mapping -->
<div id="foodModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between sticky top-0 z-10">
            <h3 id="modalTitle" class="text-sm font-bold">Add Food Item</h3>
            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="foodForm" method="POST" action="{{ route('foods.store') }}" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div id="methodSpoof"></div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Food Code / Item Code *</label>
                    <input type="text" name="code" id="foodCode" required placeholder="e.g. FD101"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 uppercase font-mono font-bold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Food Name *</label>
                    <input type="text" name="name" id="foodName" required placeholder="e.g. Chicken Dum Biriyani"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Category *</label>
                    <select name="category_id" id="foodCat" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Selling Price ({{ $currency }}) *</label>
                    <input type="number" step="0.01" name="price" id="foodPrice" required min="0" placeholder="0.00"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">GST / Tax Rate (%) *</label>
                    <input type="number" step="0.01" name="tax_rate" id="foodTax" required min="0" max="100" value="5.00"
                        class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Food Type *</label>
                    <select name="is_veg" id="foodVeg" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-semibold">
                        <option value="1">Vegetarian (VEG)</option>
                        <option value="0">Non-Vegetarian (NON-VEG)</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Description / Notes</label>
                <textarea name="description" id="foodDesc" rows="2" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500"></textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="foodActive" value="1" checked class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                <label for="foodActive" class="text-xs font-semibold text-slate-700">Item is Active and visible in POS</label>
            </div>

            <!-- Ingredient Mapping Section -->
            <div class="border-t border-slate-200 pt-4">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Raw Material / Ingredient Mapping</h4>
                        <p class="text-[11px] text-slate-500">Auto-deducted from inventory when bill is settled</p>
                    </div>
                    <button type="button" onclick="addIngredientRow()" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 rounded text-slate-700 text-xs font-semibold">
                        <i class="fa-solid fa-plus mr-1"></i> Add Ingredient
                    </button>
                </div>

                <div id="ingredientsContainer" class="space-y-2">
                    <!-- Dynamic ingredient rows -->
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold shadow-md shadow-orange-600/20">Save Food Item</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const inventoryOptions = @json($inventoryItems);
    const modal = document.getElementById('foodModal');
    const form = document.getElementById('foodForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodSpoof = document.getElementById('methodSpoof');
    const ingredientsContainer = document.getElementById('ingredientsContainer');
    let ingRowIndex = 0;

    function addIngredientRow(selectedId = '', quantity = '') {
        const rowId = `ing_row_${ingRowIndex}`;
        let optionsHtml = '<option value="">Select Raw Material</option>';
        inventoryOptions.forEach(item => {
            const isSel = item.id == selectedId ? 'selected' : '';
            optionsHtml += `<option value="${item.id}" ${isSel}>${item.name} (${item.unit})</option>`;
        });

        const html = `
            <div id="${rowId}" class="flex items-center gap-2">
                <select name="ingredients[${ingRowIndex}][id]" class="flex-1 px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                    ${optionsHtml}
                </select>
                <input type="number" step="0.001" min="0.001" name="ingredients[${ingRowIndex}][quantity]" value="${quantity}" placeholder="Qty per unit" class="w-32 px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <button type="button" onclick="document.getElementById('${rowId}').remove()" class="p-2 text-rose-500 hover:bg-rose-50 rounded">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </div>
        `;
        ingredientsContainer.insertAdjacentHTML('beforeend', html);
        ingRowIndex++;
    }

    function openAddModal() {
        modalTitle.innerText = 'Add Food Item';
        form.action = "{{ route('foods.store') }}";
        methodSpoof.innerHTML = '';
        document.getElementById('foodCode').value = '';
        document.getElementById('foodName').value = '';
        document.getElementById('foodPrice').value = '';
        document.getElementById('foodTax').value = '5.00';
        document.getElementById('foodVeg').value = '1';
        document.getElementById('foodDesc').value = '';
        document.getElementById('foodActive').checked = true;
        ingredientsContainer.innerHTML = '';
        modal.classList.remove('hidden');
    }

    function openEditModal(food) {
        modalTitle.innerText = 'Edit Food Item: ' + food.name;
        form.action = "{{ route('foods.update', ['food' => ':id']) }}".replace(':id', food.id);
        methodSpoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('foodCode').value = food.code;
        document.getElementById('foodName').value = food.name;
        document.getElementById('foodCat').value = food.category_id;
        document.getElementById('foodPrice').value = food.price;
        document.getElementById('foodTax').value = food.tax_rate;
        document.getElementById('foodVeg').value = food.is_veg ? '1' : '0';
        document.getElementById('foodDesc').value = food.description || '';
        document.getElementById('foodActive').checked = food.is_active;

        ingredientsContainer.innerHTML = '';
        if (food.ingredients && food.ingredients.length > 0) {
            food.ingredients.forEach(ing => {
                addIngredientRow(ing.id, ing.pivot ? ing.pivot.quantity : '');
            });
        }

        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }
</script>
@endpush
@endsection
