@extends('layouts.app')

@section('title', 'Table Management')
@section('page_title', 'Restaurant Tables & Dining Layout')

@section('content')
<div class="space-y-6">
    <!-- Header Controls -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <!-- Floor / Section Filter -->
        <form method="GET" action="{{ route('tables.index') }}" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
            <select name="floor" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <option value="">All Floors</option>
                @foreach($floors as $fl)
                <option value="{{ $fl }}" {{ request('floor') === $fl ? 'selected' : '' }}>{{ $fl }}</option>
                @endforeach
            </select>

            <select name="status" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                <option value="">All Statuses</option>
                <option value="available" {{ request('status') === 'available' ? 'selected' : '' }}>Available</option>
                <option value="occupied" {{ request('status') === 'occupied' ? 'selected' : '' }}>Occupied</option>
                <option value="reserved" {{ request('status') === 'reserved' ? 'selected' : '' }}>Reserved</option>
            </select>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-semibold">Filter</button>
            @if(request()->hasAny(['floor', 'status']))
            <a href="{{ route('tables.index') }}" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs">Clear</a>
            @endif
        </form>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <button type="button" onclick="openTransferModal()" class="px-3 py-2 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-right-arrow-left"></i>
                <span>Transfer Table</span>
            </button>
            <button type="button" onclick="openMergeModal()" class="px-3 py-2 bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                <i class="fa-solid fa-code-merge"></i>
                <span>Merge Tables</span>
            </button>
            <button type="button" onclick="openAddModal()" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-sm transition flex items-center gap-2">
                <i class="fa-solid fa-plus"></i>
                <span>Add Table</span>
            </button>
        </div>
    </div>

    <!-- Status Legend -->
    <div class="flex flex-wrap items-center gap-4 text-xs font-semibold px-2">
        <span class="flex items-center gap-2 text-slate-600">
            <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 inline-block"></span>
            Available (Ready for billing)
        </span>
        <span class="flex items-center gap-2 text-slate-600">
            <span class="w-3.5 h-3.5 rounded-full bg-amber-500 inline-block"></span>
            Occupied (Dining / Active Bill)
        </span>
        <span class="flex items-center gap-2 text-slate-600">
            <span class="w-3.5 h-3.5 rounded-full bg-blue-500 inline-block"></span>
            Reserved (Pre-booked)
        </span>
    </div>

    <!-- Visual Tables Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        @foreach($tables as $table)
        <div class="bg-white rounded-2xl border transition-all duration-200 p-4 shadow-sm relative flex flex-col justify-between
            {{ $table->status === 'occupied' ? 'border-amber-400 bg-amber-50/20' : ($table->status === 'reserved' ? 'border-blue-400 bg-blue-50/20' : 'border-slate-200 hover:border-orange-400') }}">
            
            <div>
                <!-- Top Table Info -->
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $table->floor }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                        {{ $table->status === 'occupied' ? 'bg-amber-100 text-amber-800' : ($table->status === 'reserved' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800') }}">
                        {{ ucfirst($table->status) }}
                    </span>
                </div>

                <!-- Table Number Big Badge -->
                <div class="my-3 text-center">
                    <div class="w-14 h-14 mx-auto rounded-2xl flex items-center justify-center font-black text-xl shadow-inner
                        {{ $table->status === 'occupied' ? 'bg-amber-500 text-white' : ($table->status === 'reserved' ? 'bg-blue-500 text-white' : 'bg-emerald-500 text-white') }}">
                        {{ $table->table_number }}
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm mt-2 truncate">{{ $table->name ?: 'Table ' . $table->table_number }}</h4>
                    <p class="text-[11px] text-slate-500 font-medium"><i class="fa-solid fa-users text-slate-400 mr-1"></i> {{ $table->capacity }} Seats &bull; {{ $table->section }}</p>
                </div>

                <!-- Active Bill Badge (if occupied) -->
                @if($table->activeBill)
                <div class="p-2 mb-3 bg-white rounded-xl border border-amber-200 text-xs">
                    <div class="flex justify-between items-center text-slate-700 font-bold">
                        <span>#{{ $table->activeBill->invoice_number }}</span>
                        <span class="text-amber-700">₹{{ number_format($table->activeBill->grand_total, 2) }}</span>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-0.5">{{ $table->activeBill->created_at->diffForHumans() }}</p>
                </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                <!-- Action to POS -->
                <a href="{{ route('pos.index', ['table_id' => $table->id]) }}" class="px-3 py-1.5 bg-slate-900 hover:bg-orange-600 text-white rounded-lg text-xs font-bold flex-1 text-center transition mr-2 shadow-sm">
                    <i class="fa-solid fa-cash-register mr-1"></i>
                    {{ $table->status === 'occupied' ? 'Manage Bill' : 'Open Bill' }}
                </a>

                <!-- Quick Menu Dropdown -->
                <div class="flex items-center gap-1">
                    <button type="button" onclick="openEditModal({{ json_encode($table) }})" class="p-1.5 text-slate-400 hover:text-blue-600 rounded" title="Edit Table">
                        <i class="fa-solid fa-pen"></i>
                    </button>
                    @if(!$table->activeBill)
                    <form action="{{ route('tables.destroy', $table) }}" method="POST" class="inline" onsubmit="return confirm('Delete table {{ $table->table_number }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 rounded" title="Delete Table">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Add / Edit Table Modal -->
<div id="tableModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 id="modalTitle" class="text-sm font-bold">Add Table</h3>
            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form id="tableForm" method="POST" action="{{ route('tables.store') }}" class="p-6 space-y-4">
            @csrf
            <div id="methodSpoof"></div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Table Number *</label>
                    <input type="text" name="table_number" id="tblNumber" required placeholder="e.g. T11" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Table Capacity *</label>
                    <input type="number" min="1" max="100" name="capacity" id="tblCapacity" value="4" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-bold">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Table Display Name</label>
                <input type="text" name="name" id="tblName" placeholder="e.g. Garden View 1" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Floor *</label>
                    <input type="text" name="floor" id="tblFloor" value="Ground Floor" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Section *</label>
                    <input type="text" name="section" id="tblSection" value="Main Hall" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Initial Status *</label>
                <select name="status" id="tblStatus" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 font-semibold">
                    <option value="available">Available (Free)</option>
                    <option value="occupied">Occupied</option>
                    <option value="reserved">Reserved</option>
                </select>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold">Save Table</button>
            </div>
        </form>
    </div>
</div>

<!-- Transfer Table Modal -->
<div id="transferModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-indigo-900 text-white flex items-center justify-between">
            <h3 class="text-sm font-bold flex items-center gap-2">
                <i class="fa-solid fa-arrow-right-arrow-left"></i>
                <span>Transfer Active Bill</span>
            </h3>
            <button type="button" onclick="document.getElementById('transferModal').classList.add('hidden')" class="text-indigo-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('tables.transfer') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">From Table (Source) *</label>
                <select name="from_table_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-indigo-500 font-bold">
                    <option value="">Select Occupied Table</option>
                    @foreach($tables->where('status', 'occupied') as $t)
                    <option value="{{ $t->id }}">Table {{ $t->table_number }} ({{ $t->name }}) - {{ $t->activeBill ? '#' . $t->activeBill->invoice_number : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">To Table (Destination) *</label>
                <select name="to_table_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-indigo-500 font-bold">
                    <option value="">Select Destination Table</option>
                    @foreach($tables->where('status', 'available') as $t)
                    <option value="{{ $t->id }}">Table {{ $t->table_number }} ({{ $t->floor }} - {{ $t->section }})</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('transferModal').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold">Transfer Bill</button>
            </div>
        </form>
    </div>
</div>

<!-- Merge Tables Modal -->
<div id="mergeModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-amber-900 text-white flex items-center justify-between">
            <h3 class="text-sm font-bold flex items-center gap-2">
                <i class="fa-solid fa-code-merge"></i>
                <span>Merge Active Tables</span>
            </h3>
            <button type="button" onclick="document.getElementById('mergeModal').classList.add('hidden')" class="text-amber-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('tables.merge') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Source Table (Will be merged and freed) *</label>
                <select name="source_table_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-amber-500 font-bold">
                    <option value="">Select Source Table</option>
                    @foreach($tables->where('status', 'occupied') as $t)
                    <option value="{{ $t->id }}">Table {{ $t->table_number }} ({{ $t->name }}) - ₹{{ $t->activeBill ? number_format($t->activeBill->grand_total, 2) : 0 }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Target Table (Will hold combined bill) *</label>
                <select name="target_table_id" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-amber-500 font-bold">
                    <option value="">Select Target Table</option>
                    @foreach($tables->where('status', 'occupied') as $t)
                    <option value="{{ $t->id }}">Table {{ $t->table_number }} ({{ $t->name }}) - ₹{{ $t->activeBill ? number_format($t->activeBill->grand_total, 2) : 0 }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('mergeModal').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold">Merge Tables</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const modal = document.getElementById('tableModal');
    const form = document.getElementById('tableForm');
    const modalTitle = document.getElementById('modalTitle');
    const methodSpoof = document.getElementById('methodSpoof');

    function openAddModal() {
        modalTitle.innerText = 'Add New Table';
        form.action = "{{ route('tables.store') }}";
        methodSpoof.innerHTML = '';
        document.getElementById('tblNumber').value = '';
        document.getElementById('tblName').value = '';
        document.getElementById('tblCapacity').value = '4';
        document.getElementById('tblFloor').value = 'Ground Floor';
        document.getElementById('tblSection').value = 'Main Hall';
        document.getElementById('tblStatus').value = 'available';
        modal.classList.remove('hidden');
    }

    function openEditModal(table) {
        modalTitle.innerText = 'Edit Table: ' + table.table_number;
        form.action = "{{ route('tables.update', ['table' => ':id']) }}".replace(':id', table.id);
        methodSpoof.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('tblNumber').value = table.table_number;
        document.getElementById('tblName').value = table.name || '';
        document.getElementById('tblCapacity').value = table.capacity;
        document.getElementById('tblFloor').value = table.floor;
        document.getElementById('tblSection').value = table.section;
        document.getElementById('tblStatus').value = table.status;
        modal.classList.remove('hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    function openTransferModal() {
        document.getElementById('transferModal').classList.remove('hidden');
    }

    function openMergeModal() {
        document.getElementById('mergeModal').classList.remove('hidden');
    }
</script>
@endpush
@endsection
