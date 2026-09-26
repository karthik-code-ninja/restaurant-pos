@extends('layouts.app')

@section('title', 'Login Logs')
@section('page_title', 'Staff Login & Access History')

@section('content')
<div class="space-y-6">
    <!-- Filters & Navigation -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('users.login-history') }}" class="flex items-center gap-2">
            <select name="user_id" class="px-3 py-1.5 rounded-lg border border-slate-300 text-xs">
                <option value="">All Staff Members</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>

            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-lg text-xs font-semibold hover:bg-slate-800 transition">Filter</button>
            @if(request('user_id'))
                <a href="{{ route('users.login-history') }}" class="text-xs text-slate-500 hover:text-slate-800">Clear</a>
            @endif
        </form>

        <a href="{{ route('users.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-xs font-semibold text-slate-700 flex items-center gap-1.5 transition">
            <i class="fa-solid fa-users"></i> Back to Staff Directory
        </a>
    </div>

    <!-- Login History Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Staff Member</th>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Login Time</th>
                        <th class="py-3 px-4">Logout Time</th>
                        <th class="py-3 px-4">Duration</th>
                        <th class="py-3 px-4">IP Address</th>
                        <th class="py-3 px-4">Client / Device</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-bold text-slate-900">
                            {{ $log->user?->name ?? 'Deleted User' }}
                            <span class="block font-normal text-[11px] text-slate-400">{{ $log->user?->email ?? '-' }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                {{ $log->user?->role?->name ?? '-' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-700 font-medium">
                            {{ $log->login_at->format('d M Y, h:i A') }}
                        </td>
                        <td class="py-3 px-4 text-slate-700">
                            @if($log->logout_at)
                                {{ $log->logout_at->format('d M Y, h:i A') }}
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">Active / In Session</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-600">
                            @if($log->logout_at)
                                {{ $log->login_at->diffForHumans($log->logout_at, true) }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-600">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                        <td class="py-3 px-4 text-slate-500 max-w-xs truncate" title="{{ $log->user_agent }}">
                            {{ Str::limit($log->user_agent, 45) ?? 'Browser / Terminal' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400">No login activity records found.</td>
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
@endsection
