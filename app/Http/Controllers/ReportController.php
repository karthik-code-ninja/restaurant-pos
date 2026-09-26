<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Category;
use App\Models\DayClosing;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Reports index hub with quick links to all 12 reports.
     */
    public function index(): View
    {
        return view('reports.index');
    }

    /**
     * 1. Daily Sales Report
     */
    public function dailySales(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->subDays(14)->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $report = Bill::where('status', 'completed')
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->select(
                DB::raw('DATE(created_at) as sale_date'),
                DB::raw('COUNT(id) as total_bills'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(tax_total) as total_tax'),
                DB::raw('SUM(grand_total) as total_sales')
            )
            ->groupBy('sale_date')
            ->orderByDesc('sale_date')
            ->paginate(15)
            ->withQueryString();

        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.daily_sales', compact('report', 'startDate', 'endDate', 'currency'));
    }

    /**
     * 2. Monthly Sales Report
     */
    public function monthlySales(Request $request): View
    {
        $year = $request->input('year', date('Y'));

        $report = Bill::where('status', 'completed')
            ->whereYear('created_at', $year)
            ->select(
                DB::raw('MONTH(created_at) as sale_month'),
                DB::raw('COUNT(id) as total_bills'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(tax_total) as total_tax'),
                DB::raw('SUM(grand_total) as total_sales')
            )
            ->groupBy('sale_month')
            ->orderBy('sale_month')
            ->get();

        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.monthly_sales', compact('report', 'year', 'currency'));
    }

    /**
     * 3. Food-wise Sales Report
     */
    public function foodSales(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $query = BillItem::whereHas('bill', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
              ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        });

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('item_name', 'like', "%{$s}%")->orWhere('food_code', 'like', "%{$s}%");
            });
        }

        $report = $query->select(
            'food_code',
            'item_name',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('AVG(unit_price) as avg_price'),
            DB::raw('SUM(tax_amount) as total_tax'),
            DB::raw('SUM(total) as total_amount')
        )
        ->groupBy('food_code', 'item_name')
        ->orderByDesc('total_qty')
        ->paginate(15)
        ->withQueryString();

        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.food_sales', compact('report', 'startDate', 'endDate', 'currency'));
    }

    /**
     * 4. Category-wise Sales Report
     */
    public function categorySales(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $report = DB::table('bill_items')
            ->join('bills', 'bill_items.bill_id', '=', 'bills.id')
            ->leftJoin('foods', function ($join) {
                $join->on('bill_items.item_id', '=', 'foods.id')
                     ->where('bill_items.item_type', '=', 'food');
            })
            ->leftJoin('categories', 'foods.category_id', '=', 'categories.id')
            ->where('bills.status', 'completed')
            ->whereBetween('bills.created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->select(
                DB::raw('COALESCE(categories.name, "Combo / Other") as category_name'),
                DB::raw('SUM(bill_items.quantity) as total_qty'),
                DB::raw('SUM(bill_items.total) as total_sales')
            )
            ->groupBy('category_name')
            ->orderByDesc('total_sales')
            ->get();

        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.category_sales', compact('report', 'startDate', 'endDate', 'currency'));
    }

    /**
     * 5. Table-wise Sales Report
     */
    public function tableSales(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $report = DB::table('bills')
            ->leftJoin('restaurant_tables', 'bills.table_id', '=', 'restaurant_tables.id')
            ->where('bills.status', 'completed')
            ->whereBetween('bills.created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->select(
                DB::raw('CASE WHEN bills.order_type = "counter" THEN "Counter Takeaway" ELSE COALESCE(restaurant_tables.table_number, "Unassigned Table") END as table_identifier'),
                DB::raw('COALESCE(restaurant_tables.floor, "-") as floor'),
                DB::raw('COALESCE(restaurant_tables.section, "-") as section'),
                DB::raw('COUNT(bills.id) as total_bills'),
                DB::raw('SUM(bills.grand_total) as total_sales')
            )
            ->groupBy('table_identifier', 'floor', 'section')
            ->orderByDesc('total_sales')
            ->get();

        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.table_sales', compact('report', 'startDate', 'endDate', 'currency'));
    }

    /**
     * 6. Payment-wise Sales Report
     */
    public function paymentSales(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $report = Payment::whereHas('bill', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
              ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        })
        ->select('payment_method', DB::raw('COUNT(id) as count'), DB::raw('SUM(amount) as total_amount'))
        ->groupBy('payment_method')
        ->get();

        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.payment_sales', compact('report', 'startDate', 'endDate', 'currency'));
    }

    /**
     * 7. GST Report
     */
    public function gstReport(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $bills = Bill::with('items')
            ->where('status', 'completed')
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totalSubtotal = Bill::where('status', 'completed')->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])->sum('subtotal');
        $totalCgst = Bill::where('status', 'completed')->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])->sum('cgst_total');
        $totalSgst = Bill::where('status', 'completed')->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])->sum('sgst_total');
        $totalTax = Bill::where('status', 'completed')->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])->sum('tax_total');
        $grandTotal = Bill::where('status', 'completed')->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])->sum('grand_total');

        $currency = Setting::get('currency_symbol', '₹');
        $gstin = Setting::get('restaurant_gstin', '');

        return view('reports.gst_report', compact(
            'bills',
            'startDate',
            'endDate',
            'totalSubtotal',
            'totalCgst',
            'totalSgst',
            'totalTax',
            'grandTotal',
            'currency',
            'gstin'
        ));
    }

    /**
     * 8. Cancelled Bill Report
     */
    public function cancelledBills(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $query = Bill::with(['cashier', 'cancelledByUser', 'table'])
            ->where('status', 'cancelled')
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('invoice_number', 'like', "%{$s}%");
        }

        $bills = $query->latest('cancelled_at')->paginate(15)->withQueryString();
        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.cancelled_bills', compact('bills', 'startDate', 'endDate', 'currency'));
    }

    /**
     * 9. Expense Report
     */
    public function expenses(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $query = Expense::with(['category', 'user'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        $expenses = $query->latest('date')->paginate(15)->withQueryString();
        $categories = \App\Models\ExpenseCategory::all();
        $totalAmount = (float) Expense::whereBetween('date', [$startDate, $endDate])->sum('amount');
        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.expenses', compact('expenses', 'categories', 'startDate', 'endDate', 'totalAmount', 'currency'));
    }

    /**
     * 10. Stock Report
     */
    public function stockReport(Request $request): View
    {
        $items = InventoryItem::orderBy('name')->get();
        $lowStockItems = InventoryItem::lowStock()->get();

        return view('reports.stock_report', compact('items', 'lowStockItems'));
    }

    /**
     * 11. Cashier-wise Sales Report
     */
    public function cashierSales(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $report = Bill::where('status', 'completed')
            ->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->select(
                'cashier_id',
                DB::raw('COUNT(id) as total_bills'),
                DB::raw('SUM(subtotal) as total_subtotal'),
                DB::raw('SUM(discount_amount) as total_discount'),
                DB::raw('SUM(tax_total) as total_tax'),
                DB::raw('SUM(grand_total) as total_sales')
            )
            ->groupBy('cashier_id')
            ->with('cashier')
            ->get();

        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.cashier_sales', compact('report', 'startDate', 'endDate', 'currency'));
    }

    /**
     * 12. Day Closing Report
     */
    public function dayClosingReport(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());

        $closings = DayClosing::with(['openedByUser', 'closedByUser'])
            ->whereBetween('opened_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()])
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        $currency = Setting::get('currency_symbol', '₹');

        return view('reports.day_closing', compact('closings', 'startDate', 'endDate', 'currency'));
    }
}
