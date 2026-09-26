@extends('layouts.app')

@section('title', 'Users & Staff Management')
@section('page_title', 'Users & Staff Management')

@section('content')
<div class="space-y-6">
    <!-- Action Bar & Filters -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user name, email, phone..." 
                       class="pl-8 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900 w-64">
            </div>

            <select name="role_id" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                <option value="">All Roles</option>
                @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>

            <select name="status" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                <option value="">All Statuses</option>
                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">Filter</button>
            @if(request()->anyFilled(['search', 'role_id', 'status']))
                <a href="{{ route('users.index') }}" class="text-xs text-slate-500 hover:text-slate-800">Clear</a>
            @endif
        </form>

        <div class="flex items-center gap-2">
            <a href="{{ route('users.login-history') }}" class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-1.5 transition">
                <i class="fa-solid fa-clock-rotate-left"></i> Login Logs
            </a>
            <button onclick="openCreateModal()" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 flex items-center gap-1.5 transition shadow-sm">
                <i class="fa-solid fa-user-plus"></i> Add New Staff
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Staff Member</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Contact</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Joined Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 flex items-center gap-1.5">
                                        {{ $user->name }}
                                        @if($user->id === auth()->id())
                                            <span class="text-[9px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-medium">You</span>
                                        @endif
                                    </p>
                                    <p class="text-slate-400 text-[11px]">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            @php
                                $roleSlug = $user->role?->slug ?? '';
                                $badgeColor = match($roleSlug) {
                                    'admin' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'manager' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'cashier' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    default => 'bg-slate-100 text-slate-700 border-slate-200'
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $badgeColor }}">
                                {{ $user->role?->name ?? 'None' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-600 font-medium">
                            {{ $user->phone ?? '—' }}
                        </td>
                        <td class="py-3 px-4">
                            <form action="{{ route('users.toggle-status', $user->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        @if($user->id === auth()->id()) disabled title="Cannot deactivate yourself" @endif
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold transition {{ $user->status ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }} {{ $user->id === auth()->id() ? 'opacity-50 cursor-not-allowed' : '' }}">
                                    {{ $user->status ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3 px-4 text-slate-500">
                            {{ $user->created_at->format('d M Y') }}
                        </td>
                        <td class="py-3 px-4 text-right">
                            <button onclick='openEditModal(@json($user))' class="p-1.5 text-slate-400 hover:text-slate-800 rounded-lg hover:bg-slate-100 transition" title="Edit Staff Details">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-400">No staff members found matching criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $users->links() }}
        </div>
    </div>
</div>

<!-- Create / Edit User Modal -->
<div id="userModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 id="modalTitle" class="text-sm font-bold text-slate-800">Add New Staff Member</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="userForm" method="POST" action="{{ route('users.store') }}" class="p-5 space-y-4">
            @csrf
            <div id="methodContainer"></div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Full Name *</label>
                <input type="text" id="name" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address (Login) *</label>
                <input type="email" id="email" name="email" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">
                    Password <span id="passReqNotice" class="text-slate-400 font-normal">*</span>
                </label>
                <input type="password" id="password" name="password" minlength="6" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900" placeholder="Minimum 6 characters">
                <p id="passHint" class="text-[10px] text-slate-400 mt-1 hidden">Leave blank to keep existing password.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">System Role *</label>
                    <select id="role_id" name="role_id" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Phone Number</label>
                    <input type="text" id="phone" name="phone" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900" placeholder="+91 9876543210">
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="status" name="status" value="1" checked class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                <label for="status" class="text-xs font-medium text-slate-700">Account Active (Permitted to log in)</label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-xs font-semibold hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">
                    Save Staff Member
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Add New Staff Member';
        document.getElementById('userForm').action = "{{ route('users.store') }}";
        document.getElementById('methodContainer').innerHTML = '';
        document.getElementById('name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
        document.getElementById('password').required = true;
        document.getElementById('passReqNotice').innerText = '*';
        document.getElementById('passHint').classList.add('hidden');
        document.getElementById('phone').value = '';
        document.getElementById('status').checked = true;
        document.getElementById('userModal').classList.remove('hidden');
    }

    function openEditModal(user) {
        document.getElementById('modalTitle').innerText = 'Edit Staff Member: ' + user.name;
        document.getElementById('userForm').action = "{{ route('users.update', ['user' => ':id']) }}".replace(':id', user.id);
        document.getElementById('methodContainer').innerHTML = '@method("PUT")';
        document.getElementById('name').value = user.name;
        document.getElementById('email').value = user.email;
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('passReqNotice').innerText = '(Optional)';
        document.getElementById('passHint').classList.remove('hidden');
        document.getElementById('role_id').value = user.role_id;
        document.getElementById('phone').value = user.phone || '';
        document.getElementById('status').checked = Boolean(user.status);
        document.getElementById('userModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('userModal').classList.add('hidden');
    }
</script>
@endsection
