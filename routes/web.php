<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AddOnController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ComboController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DayClosingController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryReportController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\QuickAccessController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    if ($user->hasRole('cashier')) {
        return redirect()->route('pos.index');
    }

    if ($user->hasPermission('dashboard.view')) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('pos.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 1. Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    // 2. POS Billing
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->middleware('permission:pos.billing')->name('index');
        Route::get('/search', [PosController::class, 'searchFoods'])->middleware('permission:pos.billing')->name('search');
        Route::post('/calculate', [PosController::class, 'calculate'])->middleware('permission:pos.billing')->name('calculate');
        Route::post('/save', [PosController::class, 'saveBill'])->middleware('permission:pos.billing')->name('save');
        Route::post('/pay', [PosController::class, 'completePayment'])->middleware('permission:pos.billing')->name('pay');
        Route::get('/held-bills', [PosController::class, 'getHeldBills'])->middleware('permission:pos.billing')->name('held');
        Route::get('/draft-bills', [PosController::class, 'getDraftBills'])->middleware('permission:pos.billing')->name('drafts');
        Route::get('/bill/{bill}/resume', [PosController::class, 'resumeBill'])->middleware('permission:pos.billing')->name('resume');
        Route::post('/bill/{bill}/cancel', [PosController::class, 'cancelBill'])->middleware('permission:bill.cancel')->name('cancel');
        Route::post('/bill/{bill}/split', [PosController::class, 'splitBill'])->middleware('permission:bill.edit')->name('split');
        Route::post('/clear-cart', [PosController::class, 'clearCartOrder'])->middleware('permission:pos.billing')->name('clear');
        Route::post('/kot', [PosController::class, 'generateKot'])->middleware('permission:pos.billing')->name('kot');
        Route::get('/kot/print/{bill}', [PosController::class, 'printKot'])->middleware('permission:pos.billing')->name('kot.print');
        Route::get('/customers/search', [CustomerController::class, 'search'])->middleware('permission:pos.billing')->name('customers.search');
        Route::post('/customers/quick-store', [CustomerController::class, 'quickStore'])->middleware('permission:pos.billing')->name('customers.quick-store');
        Route::get('/customers/{customer}/ledger-json', [CustomerController::class, 'ledgerJson'])->middleware('permission:pos.billing')->name('customers.ledger.json');
    });

    // POS Print
    Route::get('/print/{bill}', [PrintController::class, 'print'])
        ->middleware('permission:pos.billing')
        ->name('print.bill');

    // Customer & Ledger Maintenance
    Route::prefix('customers')->name('customers.')->middleware('permission:customer.view')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/', [CustomerController::class, 'store'])->middleware('permission:customer.manage')->name('store');
        Route::put('/{customer}', [CustomerController::class, 'update'])->middleware('permission:customer.manage')->name('update');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customer.manage')->name('destroy');
        Route::get('/search', [CustomerController::class, 'search'])->name('search');
        Route::post('/quick-store', [CustomerController::class, 'quickStore'])->middleware('permission:customer.manage')->name('quick-store');
        Route::get('/{customer}/ledger', [CustomerController::class, 'ledger'])->name('ledger');
        Route::get('/{customer}/ledger-json', [CustomerController::class, 'ledgerJson'])->name('ledger-json');
        Route::match(['GET', 'POST'], '/{customer}/payment', [CustomerController::class, 'recordLedgerPayment'])->middleware('permission:customer.manage')->name('payment');
        Route::match(['GET', 'POST'], '/{customer}/debit', [CustomerController::class, 'recordLedgerDebit'])->middleware('permission:customer.manage')->name('debit');
        Route::get('/{customer}/print-statement', [CustomerController::class, 'printStatement'])->name('print-statement');
    });

    // 3. Food Category Management
    Route::prefix('categories')->name('categories.')->middleware('permission:food.view')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->middleware('permission:food.create')->name('store');
        Route::put('/{category}', [CategoryController::class, 'update'])->middleware('permission:food.edit')->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->middleware('permission:food.delete')->name('destroy');
        Route::patch('/{category}/status', [CategoryController::class, 'toggleStatus'])->middleware('permission:food.edit')->name('status');
    });

    // 4. Food Management
    Route::prefix('foods')->name('foods.')->middleware('permission:food.view')->group(function () {
        Route::get('/', [FoodController::class, 'index'])->name('index');
        Route::post('/', [FoodController::class, 'store'])->middleware('permission:food.create')->name('store');
        Route::put('/{food}', [FoodController::class, 'update'])->middleware('permission:food.edit')->name('update');
        Route::delete('/{food}', [FoodController::class, 'destroy'])->middleware('permission:food.delete')->name('destroy');
        Route::patch('/{food}/status', [FoodController::class, 'toggleStatus'])->middleware('permission:food.edit')->name('status');
    });

    // Add-ons
    Route::prefix('addons')->name('addons.')->middleware('permission:food.view')->group(function () {
        Route::get('/', [AddOnController::class, 'index'])->name('index');
        Route::post('/', [AddOnController::class, 'store'])->middleware('permission:food.create')->name('store');
        Route::put('/{addon}', [AddOnController::class, 'update'])->middleware('permission:food.edit')->name('update');
        Route::delete('/{addon}', [AddOnController::class, 'destroy'])->middleware('permission:food.delete')->name('destroy');
        Route::patch('/{addon}/status', [AddOnController::class, 'toggleStatus'])->middleware('permission:food.edit')->name('status');
    });

    // Combos
    Route::prefix('combos')->name('combos.')->middleware('permission:food.view')->group(function () {
        Route::get('/', [ComboController::class, 'index'])->name('index');
        Route::post('/', [ComboController::class, 'store'])->middleware('permission:food.create')->name('store');
        Route::put('/{combo}', [ComboController::class, 'update'])->middleware('permission:food.edit')->name('update');
        Route::delete('/{combo}', [ComboController::class, 'destroy'])->middleware('permission:food.delete')->name('destroy');
        Route::patch('/{combo}/status', [ComboController::class, 'toggleStatus'])->middleware('permission:food.edit')->name('status');
    });

    // 5. Table Management
    Route::prefix('tables')->name('tables.')->middleware('permission:table.view')->group(function () {
        Route::get('/', [TableController::class, 'index'])->name('index');
        Route::post('/', [TableController::class, 'store'])->middleware('permission:table.manage')->name('store');
        Route::put('/{table}', [TableController::class, 'update'])->middleware('permission:table.manage')->name('update');
        Route::delete('/{table}', [TableController::class, 'destroy'])->middleware('permission:table.manage')->name('destroy');
        Route::patch('/{table}/status', [TableController::class, 'updateStatus'])->middleware('permission:table.manage')->name('status');
        Route::post('/transfer', [TableController::class, 'transfer'])->middleware('permission:table.manage')->name('transfer');
        Route::post('/merge', [TableController::class, 'merge'])->middleware('permission:table.manage')->name('merge');
    });

    // 6. Tax / GST Management
    Route::prefix('tax')->name('tax.')->middleware('permission:settings.manage')->group(function () {
        Route::get('/', [TaxController::class, 'index'])->name('index');
        Route::post('/settings', [TaxController::class, 'updateSettings'])->name('settings');
    });

    // 7. Inventory / Stock Management
    Route::prefix('inventory')->name('inventory.')->middleware('permission:inventory.view')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::post('/', [InventoryController::class, 'store'])->middleware('permission:inventory.manage')->name('store');
        Route::put('/{item}', [InventoryController::class, 'update'])->middleware('permission:inventory.manage')->name('update');
        Route::delete('/{item}', [InventoryController::class, 'destroy'])->middleware('permission:inventory.manage')->name('destroy');
        Route::post('/{item}/adjust', [InventoryController::class, 'adjust'])->middleware('permission:inventory.manage')->name('adjust');
        Route::get('/{item}/history', [InventoryController::class, 'history'])->name('history');
    });

    // 8. Expense Management
    Route::prefix('expenses')->name('expenses.')->middleware('permission:expense.view')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::post('/', [ExpenseController::class, 'store'])->middleware('permission:expense.manage')->name('store');
        Route::put('/{expense}', [ExpenseController::class, 'update'])->middleware('permission:expense.manage')->name('update');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->middleware('permission:expense.manage')->name('destroy');
        Route::post('/categories', [ExpenseController::class, 'storeCategory'])->middleware('permission:expense.manage')->name('category.store');
        Route::delete('/categories/{category}', [ExpenseController::class, 'destroyCategory'])->middleware('permission:expense.manage')->name('category.destroy');
    });

    // 9. Day Closing
    Route::prefix('day-closing')->name('dayclosing.')->middleware('permission:dayclosing.view')->group(function () {
        Route::get('/', [DayClosingController::class, 'index'])->name('index');
        Route::post('/open', [DayClosingController::class, 'open'])->middleware('permission:dayclosing.manage')->name('open');
        Route::post('/{dayClosing}/withdrawal', [DayClosingController::class, 'recordWithdrawal'])->middleware('permission:dayclosing.manage')->name('withdrawal');
        Route::post('/{dayClosing}/close', [DayClosingController::class, 'close'])->middleware('permission:dayclosing.manage')->name('close');
    });

    // 10. Reports
    Route::prefix('reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/daily-sales', [ReportController::class, 'dailySales'])->name('daily');
        Route::get('/monthly-sales', [ReportController::class, 'monthlySales'])->name('monthly');
        Route::get('/food-sales', [ReportController::class, 'foodSales'])->name('food');
        Route::get('/category-sales', [ReportController::class, 'categorySales'])->name('category');
        Route::get('/table-sales', [ReportController::class, 'tableSales'])->name('table');
        Route::get('/payment-sales', [ReportController::class, 'paymentSales'])->name('payment');
        Route::get('/gst', [ReportController::class, 'gstReport'])->name('gst');
        Route::get('/cancelled-bills', [ReportController::class, 'cancelledBills'])->name('cancelled');
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
        Route::get('/stock', [ReportController::class, 'stockReport'])->name('stock');
        Route::get('/cashier-sales', [ReportController::class, 'cashierSales'])->name('cashier');
        Route::get('/day-closing', [ReportController::class, 'dayClosingReport'])->name('dayclosing');
        Route::get('/day-closing-report', [ReportController::class, 'dayClosingReport'])->name('day-closing');
    });

    // 11. User & Staff Management
    Route::prefix('users')->name('users.')->middleware('permission:users.manage')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}/status', [UserController::class, 'toggleStatus'])->name('status');
        Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/login-history', [UserController::class, 'loginHistory'])->name('login_history');
        Route::get('/login-history-list', [UserController::class, 'loginHistory'])->name('login-history');
    });

    // 12. Settings
    Route::prefix('settings')->name('settings.')->middleware('permission:settings.manage')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/', [SettingController::class, 'update'])->name('update');
        Route::post('/printer-profiles', [SettingController::class, 'savePrinterProfile'])->name('printer-profiles.save');
        Route::delete('/printer-profiles/{id}', [SettingController::class, 'deletePrinterProfile'])->name('printer-profiles.delete');
        Route::post('/printer-profiles/test', [SettingController::class, 'testPrinterProfile'])->name('printer-profiles.test');
    });

    // 13. Backup & Audit
    Route::prefix('backup')->name('backup.')->middleware('permission:backup.manage')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::get('/download', [BackupController::class, 'download'])->name('download');
        Route::get('/audit-logs-view', [BackupController::class, 'auditLogs'])->name('audit-logs');
    });

    Route::get('/audit-logs', [BackupController::class, 'auditLogs'])
        ->middleware('permission:audit.view')
        ->name('audit.logs');

    // 14. AccountsPro Quick Access ERP Dashboard & Shortcut Hub
    Route::prefix('quick-access')->name('quick-access')->group(function () {
        Route::get('/', [QuickAccessController::class, 'index']);
        Route::get('/data', [QuickAccessController::class, 'dashboardData'])->name('.data');
        Route::get('/profit', [QuickAccessController::class, 'todaysProfit'])->name('.profit');
    });

    // 15. Purchases & Purchase Register (j, c)
    Route::resource('purchases', PurchaseController::class);

    // 16. Purchase Orders (m)
    Route::prefix('purchase-orders')->name('purchase-orders.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::post('/{purchaseOrder}/convert', [PurchaseOrderController::class, 'convertToPurchase'])->name('convert');
    });

    // 17. Purchase Returns / Debit Notes (i)
    Route::prefix('purchase-returns')->name('purchase-returns.')->group(function () {
        Route::get('/', [PurchaseReturnController::class, 'index'])->name('index');
        Route::post('/', [PurchaseReturnController::class, 'store'])->name('store');
        Route::get('/{purchaseReturn}', [PurchaseReturnController::class, 'show'])->name('show');
    });

    // 18. Accounting Masters & Financial Reports (b, d, e, f, g, 1, 2, 3, 4, 8)
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/ledgers', [AccountingController::class, 'ledgers'])->name('ledgers');
        Route::post('/ledgers', [AccountingController::class, 'storeLedger'])->name('ledgers.store');
        Route::get('/receipt', [AccountingController::class, 'receipt'])->name('receipt');
        Route::post('/receipt', [AccountingController::class, 'storeReceipt'])->name('receipt.store');
        Route::get('/payment', [AccountingController::class, 'payment'])->name('payment');
        Route::post('/payment', [AccountingController::class, 'storePayment'])->name('payment.store');
        Route::get('/receivables', [AccountingController::class, 'receivables'])->name('receivables');
        Route::get('/payables', [AccountingController::class, 'payables'])->name('payables');
        Route::get('/trial-balance', [AccountingController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/ledger-statement', [AccountingController::class, 'ledgerStatement'])->name('ledger-statement');
        Route::get('/voucher-register', [AccountingController::class, 'voucherRegister'])->name('voucher-register');
        Route::get('/business-view', [AccountingController::class, 'businessView'])->name('business-view');
        Route::get('/sales-register', [InventoryReportController::class, 'salesRegister'])->name('sales-register');
    });

    // 19. Inventory Reports & Barcode Print (6, 7, k, n, l)
    Route::prefix('inventory-reports')->name('inventory-reports.')->group(function () {
        Route::get('/stock-status', [InventoryReportController::class, 'stockStatus'])->name('stock-status');
        Route::get('/stock-vouchers', [InventoryReportController::class, 'stockVouchers'])->name('stock-vouchers');
        Route::post('/stock-vouchers', [InventoryReportController::class, 'storeStockVoucher'])->name('stock-vouchers.store');
        Route::get('/movement-summary', [InventoryReportController::class, 'movementSummary'])->name('movement-summary');
        Route::get('/barcode-print', [InventoryReportController::class, 'barcodePrint'])->name('barcode-print');
        Route::get('/gst-reports', [InventoryReportController::class, 'gstReports'])->name('gst-reports');
    });

    // 20. Todos & Reminders (t, r, F9, F11, F12)
    Route::prefix('todos')->name('todos.')->group(function () {
        Route::post('/', [TodoController::class, 'store'])->name('store');
        Route::post('/toggle/{todo?}', [TodoController::class, 'toggle'])->name('toggle');
        Route::delete('/{todo}', [TodoController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reminders')->name('reminders.')->group(function () {
        Route::post('/', [ReminderController::class, 'store'])->name('store');
        Route::post('/{reminder}/dismiss', [ReminderController::class, 'dismiss'])->name('dismiss');
        Route::delete('/{reminder}', [ReminderController::class, 'destroy'])->name('destroy');
    });
});
