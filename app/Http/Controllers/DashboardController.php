<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        // Parse date boundaries
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        // 1. Core Metrics for selected date range
        $completedBillsQuery = Bill::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end]);

        $totalSales = (float) $completedBillsQuery->sum('grand_total');
        $totalBills = $completedBillsQuery->count();

        // Payments breakdown
        $cashSales = (float) Payment::whereHas('bill', function ($q) use ($start, $end) {
            $q->where('status', 'completed')->whereBetween('created_at', [$start, $end]);
        })->where('payment_method', 'cash')->sum('amount');

        $upiSales = (float) Payment::whereHas('bill', function ($q) use ($start, $end) {
            $q->where('status', 'completed')->whereBetween('created_at', [$start, $end]);
        })->where('payment_method', 'upi')->sum('amount');

        $cardSales = (float) Payment::whereHas('bill', function ($q) use ($start, $end) {
            $q->where('status', 'completed')->whereBetween('created_at', [$start, $end]);
        })->where('payment_method', 'card')->sum('amount');

        // Pending & Cancelled Bills count
        $pendingBillsCount = Bill::where('status', 'pending')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $cancelledBillsCount = Bill::where('status', 'cancelled')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $heldBillsCount = Bill::where('status', 'held')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // 2. Food-wise Sales Summary (Top 8 selling items)
        $foodSalesSummary = BillItem::whereHas('bill', function ($q) use ($start, $end) {
            $q->where('status', 'completed')->whereBetween('created_at', [$start, $end]);
        })
        ->select('food_code', 'item_name', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total) as total_amount'))
        ->groupBy('food_code', 'item_name')
        ->orderByDesc('total_qty')
        ->limit(8)
        ->get();

        // 3. Daily Sales for last 7 days chart
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $daySales = (float) Bill::where('status', 'completed')
                ->whereDate('created_at', $day)
                ->sum('grand_total');

            $last7Days[] = [
                'date' => $day->format('D, d M'),
                'sales' => $daySales,
            ];
        }

        // 4. Weekly Sales (Current month weeks)
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $monthlyTotalSales = (float) Bill::where('status', 'completed')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('grand_total');

        // Recent completed and pending bills
        $recentBills = Bill::with(['table', 'cashier'])
            ->latest()
            ->limit(10)
            ->get();

        // Table occupancy summary
        $totalTables = RestaurantTable::count();
        $occupiedTables = RestaurantTable::where('status', 'occupied')->count();
        $availableTables = RestaurantTable::where('status', 'available')->count();
        $reservedTables = RestaurantTable::where('status', 'reserved')->count();

        $currency = Setting::get('currency_symbol', '₹');

        return view('dashboard.index', compact(
            'startDate',
            'endDate',
            'totalSales',
            'totalBills',
            'cashSales',
            'upiSales',
            'cardSales',
            'pendingBillsCount',
            'cancelledBillsCount',
            'heldBillsCount',
            'foodSalesSummary',
            'last7Days',
            'monthlyTotalSales',
            'recentBills',
            'totalTables',
            'occupiedTables',
            'availableTables',
            'reservedTables',
            'currency'
        ));
    }
}
