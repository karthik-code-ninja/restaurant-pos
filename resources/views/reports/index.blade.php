@extends('layouts.app')

@section('title', 'Reports Hub')
@section('page_title', 'Analytics & Reports Hub (12 Reports)')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- 1. Daily Sales -->
        <a href="{{ route('reports.daily') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">1. Daily Sales Report</h4>
                <p class="text-xs text-slate-500 mt-1">Breakdown of gross sales, discount, GST, and net totals by day.</p>
            </div>
        </a>

        <!-- 2. Monthly Sales -->
        <a href="{{ route('reports.monthly') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">2. Monthly Sales Report</h4>
                <p class="text-xs text-slate-500 mt-1">Month-over-month revenue summary and bill counts.</p>
            </div>
        </a>

        <!-- 3. Food-wise Sales -->
        <a href="{{ route('reports.food') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-bowl-food"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">3. Food-wise Sales Report</h4>
                <p class="text-xs text-slate-500 mt-1">Quantity sold, revenue, and popularity of menu items.</p>
            </div>
        </a>

        <!-- 4. Category-wise Sales -->
        <a href="{{ route('reports.category') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">4. Category-wise Sales Report</h4>
                <p class="text-xs text-slate-500 mt-1">Revenue contributions categorized by food course.</p>
            </div>
        </a>

        <!-- 5. Table-wise Sales -->
        <a href="{{ route('reports.table') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-chair"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">5. Table-wise Sales Report</h4>
                <p class="text-xs text-slate-500 mt-1">Performance by dining table, floor, and counter takeaway.</p>
            </div>
        </a>

        <!-- 6. Payment-wise Sales -->
        <a href="{{ route('reports.payment') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">6. Payment-wise Sales Report</h4>
                <p class="text-xs text-slate-500 mt-1">Total volume split across Cash, UPI, and Card transactions.</p>
            </div>
        </a>

        <!-- 7. GST Report -->
        <a href="{{ route('reports.gst') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-percent"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">7. GST Tax Report</h4>
                <p class="text-xs text-slate-500 mt-1">Invoice-wise CGST, SGST, IGST filing audit details.</p>
            </div>
        </a>

        <!-- 8. Cancelled Bill Report -->
        <a href="{{ route('reports.cancelled') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">8. Cancelled Bill Report</h4>
                <p class="text-xs text-slate-500 mt-1">Voided bills with cancellation reason, staff, and date/time.</p>
            </div>
        </a>

        <!-- 9. Expense Report -->
        <a href="{{ route('reports.expenses') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-100 text-red-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">9. Expense Report</h4>
                <p class="text-xs text-slate-500 mt-1">Itemized restaurant operational expenses by head.</p>
            </div>
        </a>

        <!-- 10. Stock Report -->
        <a href="{{ route('reports.stock') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-cyan-100 text-cyan-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">10. Stock & Inventory Report</h4>
                <p class="text-xs text-slate-500 mt-1">Current levels, opening balances, and low stock warnings.</p>
            </div>
        </a>

        <!-- 11. Cashier-wise Sales -->
        <a href="{{ route('reports.cashier') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">11. Cashier-wise Sales Report</h4>
                <p class="text-xs text-slate-500 mt-1">Sales volume generated per billing cashier operator.</p>
            </div>
        </a>

        <!-- 12. Day Closing Report -->
        <a href="{{ route('reports.dayclosing') }}" class="p-5 bg-white rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition group flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl flex-shrink-0 group-hover:scale-105 transition">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 text-sm group-hover:text-orange-600 transition">12. Day Closing Report</h4>
                <p class="text-xs text-slate-500 mt-1">Cash reconciliation records, expected vs actual differences.</p>
            </div>
        </a>
    </div>
</div>
@endsection
