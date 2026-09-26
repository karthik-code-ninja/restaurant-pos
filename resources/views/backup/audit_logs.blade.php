@extends('layouts.app')

@section('title', 'Audit Logs')
@section('page_title', 'System Audit Trail & Security Logs')

@section('content')
<div class="space-y-6">
    <!-- Filters & Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('backup.audit-logs') }}" class="flex flex-wrap items-center gap-2">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search description, reference ID..." 
                       class="pl-8 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900 w-64">
            </div>

            <select name="module" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                <option value="">All Modules</option>
                @foreach($modules as $mod)
                <option value="{{ $mod }}" {{ request('module') == $mod ? 'selected' : '' }}>{{ ucfirst($mod) }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">Filter</button>
            @if(request()->anyFilled(['search', 'module', 'action']))
                <a href="{{ route('backup.audit-logs') }}" class="text-xs text-slate-500 hover:text-slate-800">Clear</a>
            @endif
        </form>

        <a href="{{ route('backup.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold text-slate-700 flex items-center gap-1.5 transition">
            <i class="fa-solid fa-database"></i> Database Backup
        </a>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Timestamp</th>
                        <th class="py-3 px-4">User</th>
                        <th class="py-3 px-4">Module</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Reference</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 text-center">Changes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                            <span class="font-bold text-slate-700 block">{{ $log->created_at->format('d M Y') }}</span>
                            <span class="text-[10px] text-slate-400">{{ $log->created_at->format('h:i:s A') }}</span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="font-bold text-slate-800">{{ $log->user?->name ?? 'System' }}</span>
                            <span class="block text-[10px] text-slate-400">{{ $log->ip_address ?? '127.0.0.1' }}</span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 uppercase">
                                {{ $log->module }}
                            </span>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700">
                                {{ $log->action }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                            {{ $log->reference_id ?? '—' }}
                        </td>
                        <td class="py-3 px-4 text-slate-700">
                            {{ $log->description }}
                        </td>
                        <td class="py-3 px-4 text-center whitespace-nowrap">
                            @if(!empty($log->old_values) || !empty($log->new_values))
                                <button onclick='showLogDetails(@json($log))' class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[10px] font-bold transition">
                                    <i class="fa-solid fa-code-compare mr-1"></i> Diff
                                </button>
                            @else
                                <span class="text-slate-300 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No audit records found matching criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $logs->links() }}
        </div>
    </div>
</div>

<!-- Modal for Viewing Log Payload Diff -->
<div id="diffModal" class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-sm hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 id="diffTitle" class="text-sm font-bold text-slate-800">Audit Detail & Data Changes</h3>
            <button onclick="closeDiffModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-5 space-y-4 max-h-[75vh] overflow-y-auto text-xs">
            <div id="diffSummary" class="text-slate-600 font-medium pb-2 border-b border-slate-100"></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-bold text-rose-600 mb-1 flex items-center gap-1">
                        <i class="fa-solid fa-minus-circle"></i> Before (Old Values)
                    </h4>
                    <pre id="oldJson" class="p-3 bg-rose-50 border border-rose-100 rounded-xl text-[11px] font-mono text-rose-900 overflow-x-auto whitespace-pre-wrap"></pre>
                </div>
                <div>
                    <h4 class="font-bold text-emerald-600 mb-1 flex items-center gap-1">
                        <i class="fa-solid fa-plus-circle"></i> After (New Values)
                    </h4>
                    <pre id="newJson" class="p-3 bg-emerald-50 border border-emerald-100 rounded-xl text-[11px] font-mono text-emerald-900 overflow-x-auto whitespace-pre-wrap"></pre>
                </div>
            </div>
        </div>

        <div class="p-4 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeDiffModal()" class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function showLogDetails(log) {
        document.getElementById('diffTitle').innerText = 'Audit Record: ' + log.action + ' (' + log.module + ')';
        document.getElementById('diffSummary').innerText = log.description || 'No description provided';
        document.getElementById('oldJson').innerText = log.old_values ? JSON.stringify(log.old_values, null, 2) : 'No previous values recorded';
        document.getElementById('newJson').innerText = log.new_values ? JSON.stringify(log.new_values, null, 2) : 'No new values recorded';
        document.getElementById('diffModal').classList.remove('hidden');
    }

    function closeDiffModal() {
        document.getElementById('diffModal').classList.add('hidden');
    }
</script>
@endsection
