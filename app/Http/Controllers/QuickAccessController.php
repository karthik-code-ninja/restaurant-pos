<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\DayClosing;
use App\Models\Expense;
use App\Models\Food;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Reminder;
use App\Models\Setting;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuickAccessController extends Controller
{
    /**
     * Display the main Quick Access Dashboard.
     */
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $dateParam = $request->input('date', $today->toDateString());
        $currentDate = Carbon::parse($dateParam);

        $metrics = $this->calculateDashboardMetrics($currentDate);

        // Fetch active todos and reminders
        $todos = Todo::orderBy('is_completed')
            ->orderByDesc('id')
            ->take(15)
            ->get();

        $reminders = Reminder::where('is_dismissed', false)
            ->orderBy('reminder_date')
            ->take(15)
            ->get();

        $currency = Setting::get('currency_symbol', '₹');
        $restaurantName = Setting::get('restaurant_name', 'RestroPOS');

        // FY calculation
        $fyInfo = $this->getFinancialYearInfo($currentDate);

        return view('quick_access.index', compact(
            'metrics',
            'todos',
            'reminders',
            'currency',
            'restaurantName',
            'currentDate',
            'fyInfo'
        ));
    }

    /**
     * AJAX endpoint to refresh dashboard metrics.
     */
    public function dashboardData(Request $request): JsonResponse
    {
        $currentDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $metrics = $this->calculateDashboardMetrics($currentDate);
        $fyInfo = $this->getFinancialYearInfo($currentDate);

        return response()->json([
            'success' => true,
            'metrics' => $metrics,
            'fyInfo' => $fyInfo,
            'timestamp' => Carbon::now()->format('d/m/y D h:i:s A'),
        ]);
    }

    /**
     * Calculate Today's Profit breakdown (Sales - Purchases/COGS - Expenses).
     */
    public function todaysProfit(Request $request): JsonResponse
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->input('date'))
            : Carbon::today();

        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $todaySales = (float) Bill::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->sum('grand_total');

        $todayCostOfGoods = (float) Bill::where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->with('items.food')
            ->get()
            ->sum(function ($bill) {
                return $bill->items->sum(function ($item) {
                    $cost = $item->food?->cost_price ?? ($item->price * 0.45);

                    return $cost * $item->quantity;
                });
            });

        $todayPurchases = (float) Purchase::whereDate('purchase_date', $date->toDateString())
            ->sum('grand_total');

        $todayExpenses = (float) Expense::whereDate('date', $date->toDateString())
            ->sum('amount');

        $grossProfit = $todaySales - $todayCostOfGoods;
        $netProfit = $grossProfit - $todayExpenses;
        $profitMargin = $todaySales > 0 ? round(($netProfit / $todaySales) * 100, 1) : 0;

        return response()->json([
            'date' => $date->format('d/m/Y'),
            'sales' => $todaySales,
            'cogs' => $todayCostOfGoods,
            'purchases' => $todayPurchases,
            'expenses' => $todayExpenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
            'margin_percent' => $profitMargin,
        ]);
    }

    /**
     * Compute financial metrics for Today, Month, and Financial Year.
     *
     * @return array<string, array<string, float|int>>
     */
    private function calculateDashboardMetrics(Carbon $date): array
    {
        $todayStart = $date->copy()->startOfDay();
        $todayEnd = $date->copy()->endOfDay();

        $monthStart = $date->copy()->startOfMonth()->startOfDay();
        $monthEnd = $date->copy()->endOfMonth()->endOfDay();

        $fyInfo = $this->getFinancialYearInfo($date);
        $fyStart = $fyInfo['start'];
        $fyEnd = $fyInfo['end'];

        // 1. Sales
        $salesToday = (float) Bill::where('status', 'completed')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->sum('grand_total');

        $salesMonth = (float) Bill::where('status', 'completed')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('grand_total');

        $salesFy = (float) Bill::where('status', 'completed')
            ->whereBetween('created_at', [$fyStart, $fyEnd])
            ->sum('grand_total');

        // 2. Purchases
        $purchaseToday = (float) Purchase::whereDate('purchase_date', $date->toDateString())
            ->sum('grand_total');

        $purchaseMonth = (float) Purchase::whereBetween('purchase_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('grand_total');

        $purchaseFy = (float) Purchase::whereBetween('purchase_date', [$fyStart->toDateString(), $fyEnd->toDateString()])
            ->sum('grand_total');

        // 3. Expenses
        $expenseToday = (float) Expense::whereDate('date', $date->toDateString())
            ->sum('amount');

        $expenseMonth = (float) Expense::whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->sum('amount');

        $expenseFy = (float) Expense::whereBetween('date', [$fyStart->toDateString(), $fyEnd->toDateString()])
            ->sum('amount');

        // 4. Cash Balance
        // Calculated from Cash Sales + Cash Receipts - Cash Purchases - Cash Expenses
        $cashSalesToday = (float) Payment::whereHas('bill', function ($q) use ($todayStart, $todayEnd) {
            $q->where('status', 'completed')->whereBetween('created_at', [$todayStart, $todayEnd]);
        })->where('payment_method', 'cash')->sum('amount');

        $cashSalesMonth = (float) Payment::whereHas('bill', function ($q) use ($monthStart, $monthEnd) {
            $q->where('status', 'completed')->whereBetween('created_at', [$monthStart, $monthEnd]);
        })->where('payment_method', 'cash')->sum('amount');

        $cashSalesFy = (float) Payment::whereHas('bill', function ($q) use ($fyStart, $fyEnd) {
            $q->where('status', 'completed')->whereBetween('created_at', [$fyStart, $fyEnd]);
        })->where('payment_method', 'cash')->sum('amount');

        $latestDayClosing = DayClosing::latest('opened_at')->first();
        $closingCash = $latestDayClosing ? (float) ($latestDayClosing->actual_cash ?? $latestDayClosing->opening_cash) : 0;
        $cashBalanceToday = $closingCash > 0 ? $closingCash : $cashSalesToday;
        $cashBalanceMonth = $cashSalesMonth;
        $cashBalanceFy = $cashSalesFy;

        // 5. Bank Balance (UPI + Card + Bank Transfers)
        $bankSalesToday = (float) Payment::whereHas('bill', function ($q) use ($todayStart, $todayEnd) {
            $q->where('status', 'completed')->whereBetween('created_at', [$todayStart, $todayEnd]);
        })->whereIn('payment_method', ['upi', 'card'])->sum('amount');

        $bankSalesMonth = (float) Payment::whereHas('bill', function ($q) use ($monthStart, $monthEnd) {
            $q->where('status', 'completed')->whereBetween('created_at', [$monthStart, $monthEnd]);
        })->whereIn('payment_method', ['upi', 'card'])->sum('amount');

        $bankSalesFy = (float) Payment::whereHas('bill', function ($q) use ($fyStart, $fyEnd) {
            $q->where('status', 'completed')->whereBetween('created_at', [$fyStart, $fyEnd]);
        })->whereIn('payment_method', ['upi', 'card'])->sum('amount');

        // 6. Stock Value
        $foodStockValue = Food::all()->sum(function ($item) {
            $cost = $item->cost_price > 0 ? $item->cost_price : ($item->price * 0.5);

            return $cost * ($item->current_stock ?? 25);
        });

        $inventoryStockValue = InventoryItem::all()->sum(function ($item) {
            return ($item->cost_price ?? $item->unit_price ?? 0) * ($item->current_stock ?? 0);
        });

        $stockValue = (float) ($foodStockValue + $inventoryStockValue);

        // 7. Customer Balance (Total Outstanding Receivables)
        $customerBalance = (float) Customer::where('balance', '>', 0)->sum('balance');

        return [
            'sales' => [
                'today' => $salesToday,
                'month' => $salesMonth,
                'fy' => $salesFy,
                'fy_lacs' => round($salesFy / 100000, 2),
            ],
            'purchase' => [
                'today' => $purchaseToday,
                'month' => $purchaseMonth,
                'fy' => $purchaseFy,
                'fy_lacs' => round($purchaseFy / 100000, 2),
            ],
            'expense' => [
                'today' => $expenseToday,
                'month' => $expenseMonth,
                'fy' => $expenseFy,
                'fy_lacs' => round($expenseFy / 100000, 2),
            ],
            'cash_balance' => [
                'today' => $cashBalanceToday,
                'month' => $cashBalanceMonth,
                'fy' => $cashBalanceFy,
                'fy_lacs' => round($cashBalanceFy / 100000, 2),
            ],
            'bank_balance' => [
                'today' => $bankSalesToday,
                'month' => $bankSalesMonth,
                'fy' => $bankSalesFy,
                'fy_lacs' => round($bankSalesFy / 100000, 2),
            ],
            'stock_value' => [
                'today' => $stockValue,
                'month' => $stockValue,
                'fy' => $stockValue,
                'fy_lacs' => round($stockValue / 100000, 2),
            ],
            'customer_balance' => [
                'today' => $customerBalance,
                'month' => $customerBalance,
                'fy' => $customerBalance,
                'fy_lacs' => round($customerBalance / 100000, 2),
            ],
        ];
    }

    /**
     * Get start and end dates and label for Indian Financial Year.
     *
     * @return array{start: Carbon, end: Carbon, label: string, fy_short: string}
     */
    private function getFinancialYearInfo(Carbon $date): array
    {
        $year = $date->year;
        $month = $date->month;

        if ($month >= 4) {
            $fyStart = Carbon::create($year, 4, 1)->startOfDay();
            $fyEnd = Carbon::create($year + 1, 3, 31)->endOfDay();
            $fyShort = sprintf('%02d-%02d', $year % 100, ($year + 1) % 100);
        } else {
            $fyStart = Carbon::create($year - 1, 4, 1)->startOfDay();
            $fyEnd = Carbon::create($year, 3, 31)->endOfDay();
            $fyShort = sprintf('%02d-%02d', ($year - 1) % 100, $year % 100);
        }

        return [
            'start' => $fyStart,
            'end' => $fyEnd,
            'label' => sprintf('%s-%s', $fyStart->format('d/m/y'), $fyEnd->format('d/m/y')),
            'fy_short' => $fyShort,
        ];
    }
}
