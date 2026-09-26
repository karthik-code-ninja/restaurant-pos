@extends('layouts.app')

@section('title', 'Database Backup')
@section('page_title', 'Database Backup & System Maintenance')

@section('content')
<div class="max-w-4xl space-y-6">
    <!-- Backup Export Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xl">
                    <i class="fa-solid fa-database"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-800">Direct SQL Database Backup</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Generate and download a complete SQL dump of your restaurant database.</p>
                </div>
            </div>

            <a href="{{ route('backup.download') }}" class="px-5 py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700 flex items-center gap-2 shadow-sm transition">
                <i class="fa-solid fa-download"></i> Download Full SQL Backup
            </a>
        </div>

        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/60 text-xs text-slate-600 space-y-2">
            <div class="flex items-center gap-2 font-semibold text-slate-800">
                <i class="fa-solid fa-circle-info text-blue-500"></i> What is included in this backup?
            </div>
            <ul class="list-disc pl-5 space-y-1 text-slate-500">
                <li>All table structures and DDL schema statements (<code class="text-[11px] font-mono text-slate-700">DROP & CREATE TABLE</code>).</li>
                <li>All transaction data including bills, items, payments, tax calculations, and cash closings.</li>
                <li>Complete food catalog, categories, add-ons, combos, tables, recipes, and inventory raw materials.</li>
                <li>All historical audit logs and user activity logs.</li>
            </ul>
        </div>
    </div>

    <!-- System & Database Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- System Info -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">System Environment</h4>
            <div class="divide-y divide-slate-100 text-xs">
                <div class="py-2.5 flex justify-between">
                    <span class="text-slate-500">PHP Version</span>
                    <span class="font-bold text-slate-800 font-mono">{{ PHP_VERSION }}</span>
                </div>
                <div class="py-2.5 flex justify-between">
                    <span class="text-slate-500">Laravel Framework</span>
                    <span class="font-bold text-slate-800 font-mono">{{ app()->version() }}</span>
                </div>
                <div class="py-2.5 flex justify-between">
                    <span class="text-slate-500">Database Driver</span>
                    <span class="font-bold text-slate-800 font-mono">MySQL / MariaDB</span>
                </div>
                <div class="py-2.5 flex justify-between">
                    <span class="text-slate-500">App Environment</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase font-mono">{{ app()->environment() }}</span>
                </div>
            </div>
        </div>

        <!-- Audit Logs Quick Link -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4 flex flex-col justify-between">
            <div class="space-y-2">
                <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-base">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Security & Operational Audit Logs</h4>
                <p class="text-xs text-slate-500">Track critical actions, table transfers, cancellations, price edits, and staff logins.</p>
            </div>

            <a href="{{ route('backup.audit-logs') }}" class="px-4 py-2 border border-slate-200 hover:bg-slate-50 rounded-xl text-xs font-bold text-slate-700 flex items-center justify-center gap-2 transition">
                <i class="fa-solid fa-eye"></i> View Audit Trail
            </a>
        </div>
    </div>
</div>
@endsection
