@extends('layouts.app')

@section('title', 'Food Categories')
@section('page_title', 'Menu Category Management')

@section('content')
<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('categories.index') }}" class="flex items-center gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search category..."
                    class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <select name="status" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <option value="">All Status</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button type="submit" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-xs font-semibold">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status']))
            <a href="{{ route('categories.index') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs">Clear</a>
            @endif
        </form>

        <button type="button" onclick="openAddModal()" class="w-full sm:w-auto px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Add Category</span>
        </button>
    </div>

    <!-- Category Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        @if($categories->isEmpty())
        <div class="py-12 text-center text-slate-400">
            <i class="fa-solid fa-layer-group text-3xl mb-2 text-slate-300 block"></i>
            <p class="text-sm font-semibold text-slate-600">No Food Categories Found</p>
            <p class="text-xs text-slate-400 mt-1">Get started by creating your first food category.</p>
            <button type="button" onclick="openAddModal()" class="mt-4 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold inline-flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Add Category</span>
            </button>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4 w-16 text-center">Sort</th>
                        <th class="py-3 px-4">Category Name</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 text-center">Food Items</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($categories as $category)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="py-3 px-4 text-center font-mono font-bold text-slate-400">{{ $category->sort_order }}</td>
                        <td class="py-3 px-4 font-bold text-slate-900 text-sm">{{ $category->name }}</td>
                        <td class="py-3 px-4 text-slate-500 max-w-xs truncate">{{ $category->description ?? '-' }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2.5 py-1 bg-slate-100 rounded-full font-bold text-slate-700">
                                {{ $category->foods_count }} items
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('categories.status', $category) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase transition {{ $category->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3 px-4 text-center space-x-1">
                            <button type="button" onclick="openEditModal({{ json_encode($category) }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded" title="Edit">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <form action="{{ route('categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
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
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add / Edit Modal -->
<div id="categoryModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 id="modalTitle" class="text-sm font-bold">Add Category</h3>
            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="categoryForm" method="POST" action="{{ route('categories.store') }}" class="p-6 space-y-4">
            @csrf
            <div id="methodSpoof"></div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Category Name *</label>
                <input type="text" name="name" id="catName" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Sort Order</label>
                <input type="number" name="sort_order" id="catSort" value="0" min="0" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Description</label>
                <textarea name="description" id="catDesc" rows="3" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500"></textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="catActive" value="1" checked class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                <label for="catActive" class="text-xs font-semibold text-slate-700">Active Category</label>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold shadow-md shadow-orange-600/20">Save Category</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodSpoof = document.getElementById('methodSpoof');

    function openAddModal() {
        modalTitle.innerText = 'Add Category';
        form.action = "{{ route('categories.store') }}";
        methodSpoof.innerHTML = '';
        document.getElementById('catName').value = '';
        document.getElementById('catSort').value = '0';
        document.getElementById('catDesc').value = '';
        document.getElementById('catActive').checked = true;
        modal.classList.remove('hidden');
    }

    function openEditModal(category) {
        modalTitle.innerText = 'Edit Category: ' + category.name;
        form.action = "{{ route('categories.update', ['category' => ':id']) }}".replace(':id', category.id);
        methodSpoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('catName').value = category.name;
        document.getElementById('catSort').value = category.sort_order;
        document.getElementById('catDesc').value = category.description || '';
        document.getElementById('catActive').checked = category.is_active;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }
</script>
@endpush
@endsection
