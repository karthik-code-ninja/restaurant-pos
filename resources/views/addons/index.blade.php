@extends('layouts.app')

@section('title', 'Add-ons / Extras')
@section('page_title', 'Menu Add-ons & Extras')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('addons.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search add-on..."
                class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 w-full sm:w-60">
            <select name="status" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <option value="">All Status</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>
            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold">Filter</button>
            @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('addons.index') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs">Clear</a>
            @endif
        </form>

        <button type="button" onclick="openAddModal()" class="w-full sm:w-auto px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Add Add-on</span>
        </button>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($addons->isEmpty())
        <div class="py-12 text-center text-slate-400">
            <i class="fa-solid fa-plus-circle text-3xl mb-2 text-slate-300 block"></i>
            <p class="text-sm font-semibold text-slate-600">No Add-ons Configured</p>
            <p class="text-xs text-slate-400 mt-1">Configure toppings, sauces, and extra dips for POS selection.</p>
            <button type="button" onclick="openAddModal()" class="mt-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold inline-flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Add Add-on</span>
            </button>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Add-on Name</th>
                        <th class="py-3 px-4 text-right">Price</th>
                        <th class="py-3 px-4 text-center">Tax %</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($addons as $addon)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 font-bold text-slate-900 text-sm">{{ $addon->name }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">{{ $currency }}{{ number_format($addon->price, 2) }}</td>
                        <td class="py-3 px-4 text-center font-medium text-slate-600">{{ number_format($addon->tax_rate, 1) }}%</td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('addons.status', $addon) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase transition {{ $addon->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $addon->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3 px-4 text-center space-x-1">
                            <button type="button" onclick="openEditModal({{ json_encode($addon) }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <form action="{{ route('addons.destroy', $addon) }}" method="POST" class="inline" onsubmit="return confirm('Delete this add-on?')">
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
            {{ $addons->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal -->
<div id="addonModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 id="modalTitle" class="text-sm font-bold">Add Add-on</h3>
            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="addonForm" method="POST" action="{{ route('addons.store') }}" class="p-6 space-y-4">
            @csrf
            <div id="methodSpoof"></div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Add-on Name *</label>
                <input type="text" name="name" id="addonName" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Price ({{ $currency }}) *</label>
                <input type="number" step="0.01" name="price" id="addonPrice" required min="0" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Tax Rate (%) *</label>
                <input type="number" step="0.01" name="tax_rate" id="addonTax" required min="0" max="100" value="5.00" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="addonActive" value="1" checked class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                <label for="addonActive" class="text-xs font-semibold text-slate-700">Active and available in POS</label>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold">Save Add-on</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const modal = document.getElementById('addonModal');
    const form = document.getElementById('addonForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodSpoof = document.getElementById('methodSpoof');

    function openAddModal() {
        modalTitle.innerText = 'Add Add-on / Extra';
        form.action = "{{ route('addons.store') }}";
        methodSpoof.innerHTML = '';
        document.getElementById('addonName').value = '';
        document.getElementById('addonPrice').value = '';
        document.getElementById('addonTax').value = '5.00';
        document.getElementById('addonActive').checked = true;
        modal.classList.remove('hidden');
    }

    function openEditModal(addon) {
        modalTitle.innerText = 'Edit Add-on: ' + addon.name;
        form.action = "{{ route('addons.update', ':id') }}".replace(':id', addon.id);
        methodSpoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('addonName').value = addon.name;
        document.getElementById('addonPrice').value = addon.price;
        document.getElementById('addonTax').value = addon.tax_rate;
        document.getElementById('addonActive').checked = addon.is_active;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }
</script>
@endpush
@endsection
