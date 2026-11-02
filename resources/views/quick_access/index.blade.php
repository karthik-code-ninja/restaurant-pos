<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quick Access - AccountsPro | {{ $restaurantName }}</title>
    <!-- Tailwind CSS (Play CDN for reliable styling) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Classic AccountsPro Desktop Theme Styling */
        body {
            background: #e8ebf0 radial-gradient(circle at 10% 20%, #fefcf7 0%, #ece6d8 40%, #ddd5c1 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            user-select: none;
        }

        .qa-card {
            background: linear-gradient(135deg, #fffdf8 0%, #fdf7ec 100%);
            border: 2px solid #b8a68b;
            box-shadow: 3px 4px 12px rgba(0,0,0,0.15);
        }

        .qa-header-red {
            background: linear-gradient(180deg, #991b1b 0%, #7f1d1d 100%);
            color: #ffffff;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .qa-btn {
            background: linear-gradient(180deg, #fffee8 0%, #f7f2d4 50%, #eee8c2 100%);
            border: 1px solid #7d6b53;
            color: #111827;
            font-weight: 700;
            font-size: 0.85rem;
            text-align: left;
            transition: all 0.15s ease-in-out;
            cursor: pointer;
            box-shadow: 1px 1px 2px rgba(0,0,0,0.12);
        }

        .qa-btn:hover {
            background: linear-gradient(180deg, #bbf7d0 0%, #86efac 100%);
            border-color: #15803d;
            color: #064e3b;
            transform: translateY(-1px);
            box-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .qa-btn.highlight {
            background: linear-gradient(180deg, #bfdbfe 0%, #93c5fd 100%);
            border-color: #1d4ed8;
            color: #1e3a8a;
        }

        .qa-section-title {
            color: #881337;
            font-weight: 800;
            font-size: 0.95rem;
            text-decoration: underline;
            text-decoration-thickness: 1.5px;
            letter-spacing: 0.02em;
        }

        /* Top Menu Bar */
        .top-menu-bar {
            background: #ffffff;
            border-bottom: 1px solid #cbd5e1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .menu-item-btn {
            padding: 4px 14px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            transition: background 0.15s;
        }
        .menu-item-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        /* Bottom Status Bar */
        .bottom-status-bar {
            background: linear-gradient(180deg, #ffffff 0%, #f1f5f9 100%);
            border-top: 1px solid #cbd5e1;
            box-shadow: 0 -1px 4px rgba(0,0,0,0.06);
            font-size: 0.82rem;
        }

        .status-btn {
            background: #e2e8f0;
            border: 1px solid #94a3b8;
            padding: 2px 10px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #1e293b;
            border-radius: 2px;
            cursor: pointer;
        }
        .status-btn:hover {
            background: #cbd5e1;
        }
        .status-btn.active-green {
            background: #22c55e;
            color: #ffffff;
            border-color: #16a34a;
        }

        /* Table styles for dashboard */
        .dash-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        .dash-table th, .dash-table td {
            border: 1px solid #94a3b8;
            padding: 4px 8px;
        }
        .dash-table th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            text-align: center;
        }
        .dash-table td.desc-col {
            font-weight: 800;
            color: #4c1d95;
            cursor: pointer;
        }
        .dash-table td.desc-col:hover {
            color: #1d4ed8;
            text-decoration: underline;
        }
        .dash-table td.num-col {
            text-align: right;
            font-family: "Courier New", Courier, monospace;
            font-weight: 700;
            color: #0f172a;
        }

        /* Background watermarked coins decoration */
        .bg-coin-art {
            position: fixed;
            bottom: -20px;
            left: -20px;
            opacity: 0.15;
            pointer-events: none;
            z-index: 0;
            width: 280px;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 3px; }
    </style>
</head>
<body class="flex flex-col justify-between h-screen overflow-hidden">

    <!-- TOP MENU BAR -->
    <header class="top-menu-bar h-10 flex items-center justify-between px-3 z-20 flex-shrink-0">
        <div class="flex items-center space-x-1 divide-x divide-slate-300">
            <!-- Back to POS / Standard View -->
            <a href="{{ route('dashboard') }}" title="Back to POS Admin" class="flex items-center gap-1.5 px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-xs font-bold mr-2 shadow-sm transition">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Main Admin</span>
            </a>

            <!-- Menu items with Dropdowns -->
            <div class="relative group inline-block">
                <button class="menu-item-btn flex items-center gap-1">
                    <span>Accounts</span>
                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                </button>
                <div class="hidden group-hover:block absolute left-0 mt-0 w-56 bg-white border border-slate-300 shadow-xl rounded-b py-1 z-50 text-xs font-medium text-slate-700">
                    <a href="{{ route('pos.index') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">a)</span> Sales / POS</a>
                    <a href="{{ route('accounting.receipt') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">b)</span> Receipt Voucher</a>
                    <a href="{{ route('purchases.create') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">c)</span> Purchase Invoice</a>
                    <a href="{{ route('accounting.payment') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">d)</span> Payment Voucher</a>
                    <a href="{{ route('accounting.receivables') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">e)</span> Receivable (Debtors)</a>
                    <a href="{{ route('accounting.payables') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">f)</span> Payable (Creditors)</a>
                    <hr class="my-1 border-slate-200">
                    <a href="{{ route('accounting.ledgers') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">g)</span> Ledgers Master</a>
                    <a href="{{ route('accounting.trial-balance') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-blue-700 mr-2">1)</span> Trial Balance</a>
                    <a href="{{ route('accounting.ledger-statement') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-blue-700 mr-2">2)</span> Ledger Statement</a>
                    <a href="{{ route('accounting.voucher-register') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-blue-700 mr-2">3)</span> Voucher Register</a>
                    <a href="{{ route('accounting.business-view') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-blue-700 mr-2">4)</span> Business View (P&L)</a>
                </div>
            </div>

            <div class="relative group inline-block">
                <button class="menu-item-btn flex items-center gap-1">
                    <span>Inventory</span>
                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                </button>
                <div class="hidden group-hover:block absolute left-0 mt-0 w-56 bg-white border border-slate-300 shadow-xl rounded-b py-1 z-50 text-xs font-medium text-slate-700">
                    <a href="{{ route('foods.index') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">h)</span> Items Master</a>
                    <a href="{{ route('inventory-reports.stock-status') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-blue-700 mr-2">6)</span> Stock Status</a>
                    <a href="{{ route('inventory-reports.stock-vouchers') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-blue-700 mr-2">7)</span> Stock Vouchers</a>
                    <a href="{{ route('accounting.sales-register') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-blue-700 mr-2">8)</span> Sales Register</a>
                    <hr class="my-1 border-slate-200">
                    <a href="{{ route('inventory-reports.movement-summary') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">k)</span> Movement Summary</a>
                    <a href="{{ route('inventory-reports.barcode-print') }}" class="block px-4 py-2 hover:bg-slate-100"><span class="font-bold text-red-700 mr-2">n)</span> Print Barcode</a>
                </div>
            </div>

            <div class="relative group inline-block">
                <button class="menu-item-btn flex items-center gap-1">
                    <span>Quick Mode</span>
                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                </button>
                <div class="hidden group-hover:block absolute left-0 mt-0 w-52 bg-white border border-slate-300 shadow-xl rounded-b py-1 z-50 text-xs font-medium text-slate-700">
                    <a href="{{ route('pos.index') }}" class="block px-4 py-2 hover:bg-slate-100 font-bold text-orange-600"><i class="fa-solid fa-bolt mr-2"></i> Touch POS Billing</a>
                    <button onclick="toggleFullScreen()" class="w-full text-left px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-expand mr-2"></i> Toggle Full Screen</button>
                    <button onclick="openGotoModal()" class="w-full text-left px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-magnifying-glass mr-2"></i> Quick Search (Goto)</button>
                </div>
            </div>

            <div class="relative group inline-block">
                <button class="menu-item-btn flex items-center gap-1">
                    <span>Admin</span>
                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                </button>
                <div class="hidden group-hover:block absolute left-0 mt-0 w-52 bg-white border border-slate-300 shadow-xl rounded-b py-1 z-50 text-xs font-medium text-slate-700">
                    <a href="{{ route('dayclosing.index') }}" class="block px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-lock mr-2 text-slate-500"></i> Cash & Day Closing</a>
                    <a href="{{ route('tax.index') }}" class="block px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-percent mr-2 text-slate-500"></i> Tax / GST Settings</a>
                    <a href="{{ route('users.index') }}" class="block px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-users mr-2 text-slate-500"></i> Users & Staff</a>
                    <a href="{{ route('settings.index') }}" class="block px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-gear mr-2 text-slate-500"></i> General Settings</a>
                    <a href="{{ route('backup.index') }}" class="block px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-database mr-2 text-slate-500"></i> Database Backup</a>
                </div>
            </div>

            <div class="relative group inline-block">
                <button class="menu-item-btn flex items-center gap-1">
                    <span>Utility</span>
                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                </button>
                <div class="hidden group-hover:block absolute left-0 mt-0 w-52 bg-white border border-slate-300 shadow-xl rounded-b py-1 z-50 text-xs font-medium text-slate-700">
                    <button onclick="openCalculatorModal()" class="w-full text-left px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-calculator mr-2 text-blue-500"></i> Calculator</button>
                    <button onclick="openTodoModal()" class="w-full text-left px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-list-check mr-2 text-green-600"></i> Add To-Do (F9)</button>
                    <button onclick="openReminderModal()" class="w-full text-left px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-bell mr-2 text-amber-600"></i> Add Reminder (F11)</button>
                    <a href="{{ route('reports.index') }}" class="block px-4 py-2 hover:bg-slate-100"><i class="fa-solid fa-file-excel mr-2 text-emerald-600"></i> Export All Reports</a>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <button id="modeToggleBtn" onclick="toggleDemoMode()" class="border border-green-600 text-green-800 bg-green-50 px-4 py-0.5 text-xs font-bold tracking-wider hover:bg-green-100 transition rounded-sm">
                LIVE MODE
            </button>
        </div>
    </header>

    <!-- MAIN BODY AREA (Two columns: Left Quick Access, Right Widgets) -->
    <main class="flex-1 p-3 overflow-y-auto z-10 flex gap-4">
        
        <!-- LEFT: QUICK ACCESS BOX -->
        <div class="w-7/12 flex flex-col">
            <div class="qa-card rounded border border-amber-900/40 p-0 overflow-hidden flex-1 shadow-lg">
                <!-- Red Title Banner -->
                <div class="qa-header-red py-1.5 text-center text-base tracking-wider uppercase">
                    QUICK ACCESS
                </div>

                <div class="p-4 grid grid-cols-2 gap-x-8 gap-y-3">
                    
                    <!-- 1. Transactions Column Left -->
                    <div>
                        <div class="qa-section-title mb-2">Transactions</div>
                        <div class="flex flex-col space-y-1.5">
                            <a href="{{ route('pos.index') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'a' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">a)</span> Sales</span>
                                <span class="text-[10px] text-slate-500">[a]</span>
                            </a>
                            <a href="{{ route('accounting.receipt') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'b' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">b)</span> Receipt</span>
                                <span class="text-[10px] text-slate-500">[b]</span>
                            </a>
                            <a href="{{ route('purchases.create') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'c' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">c)</span> Purchase</span>
                                <span class="text-[10px] text-slate-500">[c]</span>
                            </a>
                            <a href="{{ route('accounting.payment') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'd' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">d)</span> Payment</span>
                                <span class="text-[10px] text-slate-500">[d]</span>
                            </a>
                            <a href="{{ route('accounting.receivables') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'e' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">e)</span> Receivable</span>
                                <span class="text-[10px] text-slate-500">[e]</span>
                            </a>
                            <a href="{{ route('accounting.payables') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'f' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">f)</span> Payable</span>
                                <span class="text-[10px] text-slate-500">[f]</span>
                            </a>
                        </div>
                    </div>

                    <!-- 2. Account Reports Column Right -->
                    <div>
                        <div class="qa-section-title mb-2">Account Reports</div>
                        <div class="flex flex-col space-y-1.5">
                            <a href="{{ route('accounting.trial-balance') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press '1' on keyboard">
                                <span><span class="text-blue-900 font-extrabold mr-1">1)</span> Trial Balance</span>
                                <span class="text-[10px] text-slate-500">[1]</span>
                            </a>
                            <a href="{{ route('accounting.ledger-statement') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press '2' on keyboard">
                                <span><span class="text-blue-900 font-extrabold mr-1">2)</span> Ledger Stmnt.</span>
                                <span class="text-[10px] text-slate-500">[2]</span>
                            </a>
                            <a href="{{ route('accounting.voucher-register') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press '3' on keyboard">
                                <span><span class="text-blue-900 font-extrabold mr-1">3)</span> Voucher Regi.</span>
                                <span class="text-[10px] text-slate-500">[3]</span>
                            </a>
                            <a href="{{ route('accounting.business-view') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press '4' on keyboard">
                                <span><span class="text-blue-900 font-extrabold mr-1">4)</span> Busi. View</span>
                                <span class="text-[10px] text-slate-500">[4]</span>
                            </a>
                        </div>
                    </div>

                    <!-- 3. Masters (Bottom Left Row) -->
                    <div class="pt-2">
                        <div class="qa-section-title mb-2">Masters</div>
                        <div class="flex flex-col space-y-1.5">
                            <a href="{{ route('accounting.ledgers') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'g' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">g)</span> Ledgers</span>
                                <span class="text-[10px] text-slate-500">[g]</span>
                            </a>
                            <a href="{{ route('foods.index') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'h' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">h)</span> Items</span>
                                <span class="text-[10px] text-slate-500">[h]</span>
                            </a>
                        </div>
                    </div>

                    <!-- 4. Inventory Reports (Bottom Right Row) -->
                    <div class="pt-2">
                        <div class="qa-section-title mb-2">Inventory Reports</div>
                        <div class="flex flex-col space-y-1.5">
                            <a href="{{ route('inventory-reports.stock-status') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press '6' on keyboard">
                                <span><span class="text-blue-900 font-extrabold mr-1">6)</span> Stock Status</span>
                                <span class="text-[10px] text-slate-500">[6]</span>
                            </a>
                            <a href="{{ route('inventory-reports.stock-vouchers') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press '7' on keyboard">
                                <span><span class="text-blue-900 font-extrabold mr-1">7)</span> Stock Vouchers</span>
                                <span class="text-[10px] text-slate-500">[7]</span>
                            </a>
                            <a href="{{ route('accounting.sales-register') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press '8' on keyboard">
                                <span><span class="text-blue-900 font-extrabold mr-1">8)</span> Sales Register</span>
                                <span class="text-[10px] text-slate-500">[8]</span>
                            </a>
                        </div>
                    </div>

                    <!-- 5. Favourite Shortcuts Span Across Bottom -->
                    <div class="col-span-2 pt-2 border-t border-amber-900/20">
                        <div class="qa-section-title mb-2">Favourite Shortcuts</div>
                        <div class="grid grid-cols-2 gap-x-8 gap-y-1.5">
                            <a href="{{ route('purchase-returns.index') }}" class="qa-btn highlight px-3 py-1.5 rounded flex items-center justify-between" title="Press 'i' on keyboard">
                                <span><span class="text-blue-900 font-extrabold mr-1">i)</span> Pur/Return</span>
                                <span class="text-[10px] text-slate-500">[i]</span>
                            </a>
                            <a href="{{ route('inventory-reports.gst-reports') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'l' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">l)</span> GST Reports</span>
                                <span class="text-[10px] text-slate-500">[l]</span>
                            </a>

                            <a href="{{ route('purchases.index') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'j' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">j)</span> Purchase Registe</span>
                                <span class="text-[10px] text-slate-500">[j]</span>
                            </a>
                            <a href="{{ route('purchase-orders.index') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'm' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">m)</span> Purch Order</span>
                                <span class="text-[10px] text-slate-500">[m]</span>
                            </a>

                            <a href="{{ route('inventory-reports.movement-summary') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'k' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">k)</span> Movement Sum</span>
                                <span class="text-[10px] text-slate-500">[k]</span>
                            </a>
                            <a href="{{ route('inventory-reports.barcode-print') }}" class="qa-btn px-3 py-1.5 rounded flex items-center justify-between" title="Press 'n' on keyboard">
                                <span><span class="text-red-800 font-extrabold mr-1">n)</span> Print Barcode</span>
                                <span class="text-[10px] text-slate-500">[n]</span>
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- RIGHT: TO DO LIST, REMINDERS & LIVE DASHBOARD -->
        <div class="w-5/12 flex flex-col space-y-3">
            
            <!-- TOP RIGHT 2 BOXES: TO DO LIST & REMINDER -->
            <div class="grid grid-cols-2 gap-3 h-52">
                
                <!-- TO DO LIST -->
                <div class="bg-white rounded border-2 border-emerald-700 flex flex-col overflow-hidden shadow">
                    <div class="bg-emerald-600 text-white px-2 py-1 flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wide">t) TO DO LIST</span>
                        <button onclick="openTodoModal()" title="Add Task (F9)" class="text-[11px] bg-emerald-700 hover:bg-emerald-800 px-1.5 py-0.5 rounded text-white font-bold transition">
                            + New
                        </button>
                    </div>
                    <div class="bg-emerald-50 px-2 py-0.5 border-b border-emerald-200 text-[10px] text-emerald-900 font-semibold flex justify-between">
                        <span>[F9 New, F12 Completed]</span>
                        <span>Active: {{ $todos->where('is_completed', false)->count() }}</span>
                    </div>
                    <div class="flex-1 p-2 overflow-y-auto space-y-1.5 text-xs" id="todoListContainer">
                        @forelse($todos as $todo)
                        <div class="flex items-start justify-between gap-1 p-1 rounded hover:bg-emerald-50 border-b border-slate-100 {{ $todo->is_completed ? 'opacity-50 line-through' : '' }}">
                            <div class="flex items-start gap-1.5 overflow-hidden">
                                <input type="checkbox" {{ $todo->is_completed ? 'checked' : '' }} onchange="toggleTodo({{ $todo->id }})" class="mt-0.5 cursor-pointer accent-emerald-600">
                                <span class="truncate font-medium text-slate-800" title="{{ $todo->title }}">{{ $todo->title }}</span>
                            </div>
                            <button onclick="deleteTodo({{ $todo->id }})" class="text-slate-400 hover:text-red-500 text-[10px] ml-1"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        @empty
                        <div class="h-full flex flex-col items-center justify-center text-center text-slate-400 py-6">
                            <i class="fa-regular fa-clipboard text-2xl text-emerald-400 mb-1"></i>
                            <p class="font-bold text-slate-600">Add your</p>
                            <p class="font-bold text-slate-600">Todo List Here !!!</p>
                            <button onclick="openTodoModal()" class="mt-2 text-[11px] text-emerald-700 font-semibold underline">Click or Press F9</button>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- REMINDER -->
                <div class="bg-white rounded border-2 border-teal-700 flex flex-col overflow-hidden shadow">
                    <div class="bg-teal-600 text-white px-2 py-1 flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wide">r) REMINDER</span>
                        <button onclick="openReminderModal()" title="Add Reminder (F11)" class="text-[11px] bg-teal-700 hover:bg-teal-800 px-1.5 py-0.5 rounded text-white font-bold transition">
                            + New
                        </button>
                    </div>
                    <div class="bg-teal-50 px-2 py-0.5 border-b border-teal-200 text-[10px] text-teal-900 font-semibold flex justify-between">
                        <span>[F11 New]</span>
                        <span>Date: {{ $currentDate->format('d/m') }}</span>
                    </div>
                    <div class="flex-1 p-2 overflow-y-auto space-y-1.5 text-xs" id="reminderListContainer">
                        @forelse($reminders as $reminder)
                        <div class="p-1 rounded bg-teal-50/70 border border-teal-100 flex items-start justify-between">
                            <div class="overflow-hidden">
                                <div class="font-semibold text-slate-800 truncate" title="{{ $reminder->title }}">{{ $reminder->title }}</div>
                                <div class="text-[10px] text-teal-700 font-mono">{{ $reminder->reminder_date->format('d-M') }} {{ $reminder->reminder_time }}</div>
                            </div>
                            <button onclick="dismissReminder({{ $reminder->id }})" title="Dismiss" class="text-slate-400 hover:text-red-500 text-[10px]"><i class="fa-solid fa-check"></i></button>
                        </div>
                        @empty
                        <div class="h-full flex flex-col items-center justify-center text-center text-slate-400 py-6">
                            <i class="fa-regular fa-bell text-2xl text-teal-400 mb-1"></i>
                            <p class="font-bold text-slate-600">Add your</p>
                            <p class="font-bold text-slate-600">Reminders Here !!!</p>
                            <button onclick="openReminderModal()" class="mt-2 text-[11px] text-teal-700 font-semibold underline">Click or Press F11</button>
                        </div>
                        @endforelse
                    </div>
                </div>

            </div>

            <!-- BOTTOM RIGHT: LIVE DASHBOARD TABLE -->
            <div class="bg-white rounded border-2 border-blue-900 flex-1 flex flex-col overflow-hidden shadow-lg">
                <!-- Blue Header Banner with action buttons -->
                <div class="bg-sky-700 text-white px-3 py-1 flex items-center justify-between">
                    <button onclick="openTodaysProfitModal()" class="bg-white text-sky-900 hover:bg-sky-50 px-2 py-0.5 rounded text-[11px] font-bold shadow-sm transition">
                        Today's Profit
                    </button>
                    <span class="font-extrabold text-sm tracking-wide" id="dashBoardHeaderDate">
                        DASH BOARD ({{ $currentDate->format('d/m/Y') }})
                    </span>
                    <button onclick="refreshDashboard()" id="refreshDashBtn" class="bg-white text-sky-900 hover:bg-sky-50 px-2 py-0.5 rounded text-[11px] font-bold shadow-sm transition">
                        <i class="fa-solid fa-rotate mr-1 text-[10px]"></i> Refresh
                    </button>
                </div>

                <!-- Financial Metrics Table -->
                <div class="flex-1 p-1 overflow-y-auto">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th style="width: 40%; text-align: left;">Description</th>
                                <th style="width: 20%;" id="thDateCol">{{ $currentDate->format('j-M') }}</th>
                                <th style="width: 20%;" id="thMonthCol">{{ $currentDate->format('M-y') }}</th>
                                <th style="width: 20%;" id="thFyCol">{{ $fyInfo['fy_short'] }} (Lacs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- SALES -->
                            <tr>
                                <td class="desc-col" onclick="window.location='{{ route('accounting.sales-register') }}'">SALES</td>
                                <td class="num-col" id="m_sales_today">{{ number_format($metrics['sales']['today'], 0) }}</td>
                                <td class="num-col" id="m_sales_month">{{ number_format($metrics['sales']['month'], 0) }}</td>
                                <td class="num-col" id="m_sales_fy">{{ number_format($metrics['sales']['fy_lacs'], 2) }}</td>
                            </tr>
                            <!-- PURCHASE -->
                            <tr>
                                <td class="desc-col" onclick="window.location='{{ route('purchases.index') }}'">PURCHASE</td>
                                <td class="num-col" id="m_purchase_today">{{ number_format($metrics['purchase']['today'], 0) }}</td>
                                <td class="num-col" id="m_purchase_month">{{ number_format($metrics['purchase']['month'], 0) }}</td>
                                <td class="num-col" id="m_purchase_fy">{{ number_format($metrics['purchase']['fy_lacs'], 2) }}</td>
                            </tr>
                            <!-- EXPENSE -->
                            <tr>
                                <td class="desc-col" onclick="window.location='{{ route('expenses.index') }}'">EXPENSE</td>
                                <td class="num-col" id="m_expense_today">{{ number_format($metrics['expense']['today'], 0) }}</td>
                                <td class="num-col" id="m_expense_month">{{ number_format($metrics['expense']['month'], 0) }}</td>
                                <td class="num-col" id="m_expense_fy">{{ number_format($metrics['expense']['fy_lacs'], 2) }}</td>
                            </tr>
                            <!-- CASH BALANCE -F2 -->
                            <tr>
                                <td class="desc-col" onclick="showCashModal()">CASH BALANCE -F2</td>
                                <td class="num-col" id="m_cash_today">{{ number_format($metrics['cash_balance']['today'], 0) }}</td>
                                <td class="num-col" id="m_cash_month">{{ number_format($metrics['cash_balance']['month'], 0) }}</td>
                                <td class="num-col" id="m_cash_fy">{{ number_format($metrics['cash_balance']['fy_lacs'], 2) }}</td>
                            </tr>
                            <!-- BANK BALANCE -F3 -->
                            <tr>
                                <td class="desc-col" onclick="showBankModal()">BANK BALANCE -F3</td>
                                <td class="num-col" id="m_bank_today">{{ number_format($metrics['bank_balance']['today'], 0) }}</td>
                                <td class="num-col" id="m_bank_month">{{ number_format($metrics['bank_balance']['month'], 0) }}</td>
                                <td class="num-col" id="m_bank_fy">{{ number_format($metrics['bank_balance']['fy_lacs'], 2) }}</td>
                            </tr>
                            <!-- STOCK VALUE -F4 -->
                            <tr>
                                <td class="desc-col" onclick="window.location='{{ route('inventory-reports.stock-status') }}'">STOCK VALUE -F4</td>
                                <td class="num-col" id="m_stock_today">{{ number_format($metrics['stock_value']['today'], 0) }}</td>
                                <td class="num-col" id="m_stock_month">{{ number_format($metrics['stock_value']['month'], 0) }}</td>
                                <td class="num-col" id="m_stock_fy">{{ number_format($metrics['stock_value']['fy_lacs'], 2) }}</td>
                            </tr>
                            <!-- CUSTOMER BALANCE -F5 -->
                            <tr>
                                <td class="desc-col" onclick="window.location='{{ route('accounting.receivables') }}'">CUSTOMER BALANCE -F5</td>
                                <td class="num-col" id="m_cust_today">{{ number_format($metrics['customer_balance']['today'], 0) }}</td>
                                <td class="num-col" id="m_cust_month">{{ number_format($metrics['customer_balance']['month'], 0) }}</td>
                                <td class="num-col" id="m_cust_fy">{{ number_format($metrics['customer_balance']['fy_lacs'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>

    <!-- BOTTOM STATUS BAR -->
    <footer class="bottom-status-bar h-8 px-4 flex items-center justify-between text-slate-700 z-20 flex-shrink-0 select-none">
        <div class="flex items-center space-x-3 text-xs font-semibold">
            <span class="font-extrabold text-blue-900" id="footerModeText">DEMO</span>
            <span>|</span>
            <span id="footerFyLabel">{{ $fyInfo['label'] }}</span>
            <span>|</span>
            <span id="footerCurrentDate">{{ $currentDate->format('d-m-y') }}</span>
            <span>|</span>
            <span class="text-slate-600">{{ auth()->user()->name ?? 'admin' }}</span>
        </div>

        <div class="flex items-center space-x-2">
            <button onclick="toggleDistractionFree()" class="status-btn">Minimize</button>
            <button onclick="openExplorerModal()" class="status-btn">Explorer</button>
            <button onclick="openGotoModal()" class="status-btn">Goto</button>
            <button onclick="openVideoGuideModal()" class="status-btn active-green">Video</button>
        </div>

        <div class="flex items-center space-x-4 text-xs">
            <div class="font-mono font-bold text-slate-800" id="liveClockDisplay">
                {{ now()->format('d/m/y D h:i:s A') }}
            </div>
            <div class="flex items-center space-x-2">
                <span class="font-black text-sky-800 tracking-tight">Accounts<span class="text-orange-600 font-extrabold">Pro</span></span>
                <span class="text-slate-400">|</span>
                <a href="http://www.accountspro.co.in" target="_blank" class="text-blue-700 underline text-[11px]">www.accountspro.co.in</a>
                <span class="text-slate-600 font-semibold text-[11px]">Ph. 7043730217</span>
            </div>
        </div>
    </footer>

    <!-- MODAL: ADD TO DO (F9) -->
    <div id="todoModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
            <div class="bg-emerald-700 text-white px-4 py-3 flex items-center justify-between">
                <h3 class="font-bold text-sm"><i class="fa-solid fa-list-check mr-2"></i> Add New Task (F9)</h3>
                <button onclick="closeTodoModal()" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="todoForm" onsubmit="submitTodo(event)" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Task Description *</label>
                    <input type="text" id="todoTitle" name="title" required class="w-full text-sm border border-slate-300 rounded px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none" placeholder="e.g. Check milk delivery invoices">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Priority</label>
                        <select name="priority" id="todoPriority" class="w-full text-sm border border-slate-300 rounded px-2 py-1.5">
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Due Date</label>
                        <input type="date" name="due_date" id="todoDueDate" value="{{ date('Y-m-d') }}" class="w-full text-sm border border-slate-300 rounded px-2 py-1.5">
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeTodoModal()" class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white rounded shadow">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: ADD REMINDER (F11) -->
    <div id="reminderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
            <div class="bg-teal-700 text-white px-4 py-3 flex items-center justify-between">
                <h3 class="font-bold text-sm"><i class="fa-solid fa-bell mr-2"></i> Add Reminder (F11)</h3>
                <button onclick="closeReminderModal()" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="reminderForm" onsubmit="submitReminder(event)" class="p-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Reminder Title *</label>
                    <input type="text" id="reminderTitle" name="title" required class="w-full text-sm border border-slate-300 rounded px-3 py-2 focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="e.g. Call supplier for rice order">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Reminder Date *</label>
                        <input type="date" name="reminder_date" id="reminderDate" value="{{ date('Y-m-d') }}" required class="w-full text-sm border border-slate-300 rounded px-2 py-1.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Time</label>
                        <input type="time" name="reminder_time" id="reminderTime" value="17:00" class="w-full text-sm border border-slate-300 rounded px-2 py-1.5">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Notes / Instructions</label>
                    <textarea name="notes" id="reminderNotes" rows="2" class="w-full text-sm border border-slate-300 rounded px-3 py-1.5" placeholder="Optional details..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeReminderModal()" class="px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded">Cancel</button>
                    <button type="submit" class="px-4 py-1.5 text-xs font-bold bg-teal-700 hover:bg-teal-800 text-white rounded shadow">Set Reminder</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: TODAY'S PROFIT -->
    <div id="profitModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-md w-full overflow-hidden border border-slate-200">
            <div class="bg-sky-800 text-white px-4 py-3 flex items-center justify-between">
                <h3 class="font-bold text-sm"><i class="fa-solid fa-coins mr-2 text-amber-400"></i> Today's Profit Analysis</h3>
                <button onclick="closeProfitModal()" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-5 space-y-4">
                <div class="text-xs text-slate-500 font-semibold text-center border-b pb-2">
                    Financial Performance for <span id="profitModalDate" class="text-slate-800 font-bold"></span>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600 font-medium">Total Sales Revenue:</span>
                        <span class="font-bold text-slate-900" id="profitModalSales">₹ 0</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600 font-medium">(-) Estimated Cost of Goods (COGS):</span>
                        <span class="font-bold text-red-600" id="profitModalCogs">₹ 0</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-200 bg-sky-50 px-2 rounded">
                        <span class="text-sky-900 font-bold">(=) Gross Profit:</span>
                        <span class="font-extrabold text-sky-900" id="profitModalGross">₹ 0</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-100">
                        <span class="text-slate-600 font-medium">(-) Operating Expenses:</span>
                        <span class="font-bold text-red-600" id="profitModalExpenses">₹ 0</span>
                    </div>
                    <div class="flex justify-between py-2 border-t-2 border-emerald-600 bg-emerald-50 px-2 rounded">
                        <span class="text-emerald-950 font-black text-base">(=) Net Profit:</span>
                        <span class="font-black text-base text-emerald-700" id="profitModalNet">₹ 0</span>
                    </div>
                    <div class="text-right text-xs font-semibold text-slate-500">
                        Net Profit Margin: <span id="profitModalMargin" class="font-bold text-slate-800">0%</span>
                    </div>
                </div>
                <div class="flex justify-end pt-2">
                    <button onclick="closeProfitModal()" class="px-4 py-1.5 text-xs font-bold bg-slate-800 hover:bg-slate-900 text-white rounded">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: GOTO QUICK SEARCH -->
    <div id="gotoModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
            <div class="bg-slate-900 text-white px-4 py-3 flex items-center justify-between">
                <h3 class="font-bold text-sm"><i class="fa-solid fa-magnifying-glass mr-2 text-orange-400"></i> Goto - Quick Menu Search</h3>
                <button onclick="closeGotoModal()" class="text-white/80 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-4">
                <input type="text" id="gotoSearchInput" onkeyup="filterGotoList(this.value)" placeholder="Type menu name or shortcut (e.g. Sales, Ledger, F4)..." class="w-full text-sm border-2 border-orange-500 rounded px-3 py-2.5 mb-3 focus:outline-none">
                <div class="max-h-60 overflow-y-auto space-y-1 text-xs" id="gotoListContainer">
                    <a href="{{ route('pos.index') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Sales / POS Billing</span> <span class="font-bold text-red-700">[a]</span></a>
                    <a href="{{ route('accounting.receipt') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Customer Receipt Voucher</span> <span class="font-bold text-red-700">[b]</span></a>
                    <a href="{{ route('purchases.create') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Inward Purchase Invoice</span> <span class="font-bold text-red-700">[c]</span></a>
                    <a href="{{ route('accounting.payment') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Payment Voucher</span> <span class="font-bold text-red-700">[d]</span></a>
                    <a href="{{ route('accounting.receivables') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Receivables (Customer Dues)</span> <span class="font-bold text-red-700">[e]</span></a>
                    <a href="{{ route('accounting.payables') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Payables (Vendor Dues)</span> <span class="font-bold text-red-700">[f]</span></a>
                    <a href="{{ route('accounting.ledgers') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Master Ledgers</span> <span class="font-bold text-red-700">[g]</span></a>
                    <a href="{{ route('foods.index') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Items & Menu Master</span> <span class="font-bold text-red-700">[h]</span></a>
                    <a href="{{ route('purchase-returns.index') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Purchase Return (Debit Note)</span> <span class="font-bold text-red-700">[i]</span></a>
                    <a href="{{ route('purchases.index') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Purchase Register</span> <span class="font-bold text-red-700">[j]</span></a>
                    <a href="{{ route('inventory-reports.movement-summary') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Stock Movement Summary</span> <span class="font-bold text-red-700">[k]</span></a>
                    <a href="{{ route('inventory-reports.gst-reports') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>GST Tax Reports</span> <span class="font-bold text-red-700">[l]</span></a>
                    <a href="{{ route('purchase-orders.index') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Purchase Orders</span> <span class="font-bold text-red-700">[m]</span></a>
                    <a href="{{ route('inventory-reports.barcode-print') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Print Barcode Labels</span> <span class="font-bold text-red-700">[n]</span></a>
                    <a href="{{ route('accounting.trial-balance') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Trial Balance</span> <span class="font-bold text-blue-700">[1]</span></a>
                    <a href="{{ route('accounting.ledger-statement') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Ledger Statement</span> <span class="font-bold text-blue-700">[2]</span></a>
                    <a href="{{ route('accounting.voucher-register') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Voucher Register</span> <span class="font-bold text-blue-700">[3]</span></a>
                    <a href="{{ route('accounting.business-view') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Business View (Trading / P&L)</span> <span class="font-bold text-blue-700">[4]</span></a>
                    <a href="{{ route('inventory-reports.stock-status') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Stock Status</span> <span class="font-bold text-blue-700">[6]</span></a>
                    <a href="{{ route('inventory-reports.stock-vouchers') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Stock Vouchers</span> <span class="font-bold text-blue-700">[7]</span></a>
                    <a href="{{ route('accounting.sales-register') }}" class="goto-item flex justify-between p-2 rounded hover:bg-orange-50 border border-transparent hover:border-orange-200"><span>Sales Register</span> <span class="font-bold text-blue-700">[8]</span></a>
                </div>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT & SHORTCUT LOGIC -->
    <script>
        // Live Clock Ticking Every Second
        function updateLiveClock() {
            const now = new Date();
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            const pad = (n) => String(n).padStart(2, '0');
            
            const day = pad(now.getDate());
            const month = pad(now.getMonth() + 1);
            const year = String(now.getFullYear()).slice(-2);
            const dayName = days[now.getDay()];

            let hours = now.getHours();
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // 0 becomes 12
            const hoursStr = pad(hours);
            const minutes = pad(now.getMinutes());
            const seconds = pad(now.getSeconds());

            const formatted = `${day}/${month}/${year} ${dayName} ${hoursStr}:${minutes}:${seconds} ${ampm}`;
            const clockEl = document.getElementById('liveClockDisplay');
            if (clockEl) {
                clockEl.textContent = formatted;
            }
        }
        setInterval(updateLiveClock, 1000);

        // AJAX Refresh Dashboard Metrics
        function refreshDashboard() {
            const btn = document.getElementById('refreshDashBtn');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading...';
            btn.disabled = true;

            fetch("{{ route('quick-access.data') }}")
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.metrics) {
                        const m = data.metrics;
                        const fmt = (num) => new Intl.NumberFormat('en-IN').format(Math.round(num));
                        const fmtLacs = (num) => Number(num).toFixed(2);

                        // Sales
                        document.getElementById('m_sales_today').textContent = fmt(m.sales.today);
                        document.getElementById('m_sales_month').textContent = fmt(m.sales.month);
                        document.getElementById('m_sales_fy').textContent = fmtLacs(m.sales.fy_lacs);

                        // Purchase
                        document.getElementById('m_purchase_today').textContent = fmt(m.purchase.today);
                        document.getElementById('m_purchase_month').textContent = fmt(m.purchase.month);
                        document.getElementById('m_purchase_fy').textContent = fmtLacs(m.purchase.fy_lacs);

                        // Expense
                        document.getElementById('m_expense_today').textContent = fmt(m.expense.today);
                        document.getElementById('m_expense_month').textContent = fmt(m.expense.month);
                        document.getElementById('m_expense_fy').textContent = fmtLacs(m.expense.fy_lacs);

                        // Cash
                        document.getElementById('m_cash_today').textContent = fmt(m.cash_balance.today);
                        document.getElementById('m_cash_month').textContent = fmt(m.cash_balance.month);
                        document.getElementById('m_cash_fy').textContent = fmtLacs(m.cash_balance.fy_lacs);

                        // Bank
                        document.getElementById('m_bank_today').textContent = fmt(m.bank_balance.today);
                        document.getElementById('m_bank_month').textContent = fmt(m.bank_balance.month);
                        document.getElementById('m_bank_fy').textContent = fmtLacs(m.bank_balance.fy_lacs);

                        // Stock
                        document.getElementById('m_stock_today').textContent = fmt(m.stock_value.today);
                        document.getElementById('m_stock_month').textContent = fmt(m.stock_value.month);
                        document.getElementById('m_stock_fy').textContent = fmtLacs(m.stock_value.fy_lacs);

                        // Customer
                        document.getElementById('m_cust_today').textContent = fmt(m.customer_balance.today);
                        document.getElementById('m_cust_month').textContent = fmt(m.customer_balance.month);
                        document.getElementById('m_cust_fy').textContent = fmtLacs(m.customer_balance.fy_lacs);

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2000
                        });
                        Toast.fire({ icon: 'success', title: 'Dashboard refreshed!' });
                    }
                })
                .catch(err => {
                    console.error(err);
                })
                .finally(() => {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                });
        }

        // Today's Profit Modal
        function openTodaysProfitModal() {
            fetch("{{ route('quick-access.profit') }}")
                .then(res => res.json())
                .then(data => {
                    const fmt = (num) => '₹ ' + new Intl.NumberFormat('en-IN', { maximumFractionDigits: 2 }).format(num);
                    document.getElementById('profitModalDate').textContent = data.date;
                    document.getElementById('profitModalSales').textContent = fmt(data.sales);
                    document.getElementById('profitModalCogs').textContent = fmt(data.cogs);
                    document.getElementById('profitModalGross').textContent = fmt(data.gross_profit);
                    document.getElementById('profitModalExpenses').textContent = fmt(data.expenses);
                    document.getElementById('profitModalNet').textContent = fmt(data.net_profit);
                    document.getElementById('profitModalMargin').textContent = data.margin_percent + '%';

                    document.getElementById('profitModal').classList.remove('hidden');
                    document.getElementById('profitModal').classList.add('flex');
                });
        }
        function closeProfitModal() {
            document.getElementById('profitModal').classList.add('hidden');
            document.getElementById('profitModal').classList.remove('flex');
        }

        // Todo Modal & Ajax Actions
        function openTodoModal() {
            document.getElementById('todoModal').classList.remove('hidden');
            document.getElementById('todoModal').classList.add('flex');
            setTimeout(() => document.getElementById('todoTitle').focus(), 100);
        }
        function closeTodoModal() {
            document.getElementById('todoModal').classList.add('hidden');
            document.getElementById('todoModal').classList.remove('flex');
        }
        function submitTodo(e) {
            e.preventDefault();
            const form = document.getElementById('todoForm');
            const data = new FormData(form);

            fetch("{{ route('todos.store') }}", {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    closeTodoModal();
                    form.reset();
                    location.reload();
                }
            });
        }
        function toggleTodo(id) {
            const url = id ? `/todos/toggle/${id}` : `/todos/toggle`;
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    location.reload();
                }
            });
        }
        function deleteTodo(id) {
            fetch(`/todos/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(() => location.reload());
        }

        // Reminder Modal & Ajax Actions
        function openReminderModal() {
            document.getElementById('reminderModal').classList.remove('hidden');
            document.getElementById('reminderModal').classList.add('flex');
            setTimeout(() => document.getElementById('reminderTitle').focus(), 100);
        }
        function closeReminderModal() {
            document.getElementById('reminderModal').classList.add('hidden');
            document.getElementById('reminderModal').classList.remove('flex');
        }
        function submitReminder(e) {
            e.preventDefault();
            const form = document.getElementById('reminderForm');
            const data = new FormData(form);

            fetch("{{ route('reminders.store') }}", {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    closeReminderModal();
                    form.reset();
                    location.reload();
                }
            });
        }
        function dismissReminder(id) {
            fetch(`/reminders/${id}/dismiss`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(() => location.reload());
        }

        // Quick Goto Modal
        function openGotoModal() {
            document.getElementById('gotoModal').classList.remove('hidden');
            document.getElementById('gotoModal').classList.add('flex');
            setTimeout(() => document.getElementById('gotoSearchInput').focus(), 100);
        }
        function closeGotoModal() {
            document.getElementById('gotoModal').classList.add('hidden');
            document.getElementById('gotoModal').classList.remove('flex');
        }
        function filterGotoList(val) {
            const filter = val.toLowerCase();
            const items = document.querySelectorAll('.goto-item');
            items.forEach(item => {
                if (item.textContent.toLowerCase().includes(filter)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Detail modals for F2 (Cash), F3 (Bank)
        function showCashModal() {
            Swal.fire({
                title: 'Cash in Hand Details',
                html: `
                    <div class="text-left text-sm space-y-2 p-2">
                        <div class="flex justify-between border-b pb-1"><span>Today's Cash Collection:</span> <b>₹ ${document.getElementById('m_cash_today').textContent}</b></div>
                        <div class="flex justify-between border-b pb-1"><span>Current Month Cash:</span> <b>₹ ${document.getElementById('m_cash_month').textContent}</b></div>
                        <div class="flex justify-between border-b pb-1"><span>FY Cumulative:</span> <b>${document.getElementById('m_cash_fy').textContent} Lacs</b></div>
                        <div class="pt-2 text-center">
                            <a href="{{ route('accounting.ledger-statement', ['type' => 'cash', 'id' => 1]) }}" class="inline-block bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-bold">Open Full Cash Book Statement</a>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Close',
                confirmButtonColor: '#1e293b'
            });
        }
        function showBankModal() {
            Swal.fire({
                title: 'Bank & Digital Balances (UPI/Card)',
                html: `
                    <div class="text-left text-sm space-y-2 p-2">
                        <div class="flex justify-between border-b pb-1"><span>Today's Digital Receipts:</span> <b>₹ ${document.getElementById('m_bank_today').textContent}</b></div>
                        <div class="flex justify-between border-b pb-1"><span>Current Month Digital:</span> <b>₹ ${document.getElementById('m_bank_month').textContent}</b></div>
                        <div class="flex justify-between border-b pb-1"><span>FY Cumulative:</span> <b>${document.getElementById('m_bank_fy').textContent} Lacs</b></div>
                        <div class="pt-2 text-center">
                            <a href="{{ route('accounting.ledger-statement', ['type' => 'bank', 'id' => 1]) }}" class="inline-block bg-blue-700 text-white px-3 py-1.5 rounded text-xs font-bold">Open Bank Statement</a>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Close',
                confirmButtonColor: '#1e293b'
            });
        }

        // Demo Mode Toggle
        function toggleDemoMode() {
            const btn = document.getElementById('modeToggleBtn');
            const footer = document.getElementById('footerModeText');
            if (btn.textContent.trim() === 'LIVE MODE') {
                btn.textContent = 'DEMO MODE';
                btn.className = 'border border-amber-600 text-amber-800 bg-amber-50 px-4 py-0.5 text-xs font-bold tracking-wider hover:bg-amber-100 transition rounded-sm';
                footer.textContent = 'DEMO';
            } else {
                btn.textContent = 'LIVE MODE';
                btn.className = 'border border-green-600 text-green-800 bg-green-50 px-4 py-0.5 text-xs font-bold tracking-wider hover:bg-green-100 transition rounded-sm';
                footer.textContent = 'LIVE';
            }
        }

        // Fullscreen & Distraction-free toggles
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }
        function toggleDistractionFree() {
            toggleFullScreen();
        }

        function openExplorerModal() {
            Swal.fire({
                title: 'Data & Report Explorer',
                html: `
                    <div class="text-left text-xs space-y-2 p-2">
                        <p class="text-slate-600 mb-2">Direct access to accounting journals, ledgers, and raw export formats:</p>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('purchases.index') }}" class="p-2 border rounded hover:bg-slate-50 font-bold block text-blue-700"><i class="fa-solid fa-file-invoice mr-1"></i> Purchases Register</a>
                            <a href="{{ route('accounting.sales-register') }}" class="p-2 border rounded hover:bg-slate-50 font-bold block text-blue-700"><i class="fa-solid fa-cart-shopping mr-1"></i> Sales Register</a>
                            <a href="{{ route('accounting.voucher-register') }}" class="p-2 border rounded hover:bg-slate-50 font-bold block text-blue-700"><i class="fa-solid fa-book mr-1"></i> Voucher Register</a>
                            <a href="{{ route('accounting.trial-balance') }}" class="p-2 border rounded hover:bg-slate-50 font-bold block text-blue-700"><i class="fa-solid fa-scale-balanced mr-1"></i> Trial Balance</a>
                            <a href="{{ route('inventory-reports.stock-status') }}" class="p-2 border rounded hover:bg-slate-50 font-bold block text-blue-700"><i class="fa-solid fa-boxes-stacked mr-1"></i> Stock Status</a>
                            <a href="{{ route('inventory-reports.gst-reports') }}" class="p-2 border rounded hover:bg-slate-50 font-bold block text-blue-700"><i class="fa-solid fa-percent mr-1"></i> GST Filing Reports</a>
                        </div>
                    </div>
                `,
                showCloseButton: true,
                confirmButtonText: 'Close'
            });
        }

        function openVideoGuideModal() {
            Swal.fire({
                title: 'AccountsPro Quick Help & Shortcuts',
                html: `
                    <div class="text-left text-xs space-y-2 p-2">
                        <div class="p-3 bg-amber-50 rounded border border-amber-200 text-amber-950 font-medium">
                            <b>Quick Access Keyboard Navigation:</b><br>
                            - Press <b>a</b>: Sales (POS Billing)<br>
                            - Press <b>b</b>: Customer Receipt Voucher<br>
                            - Press <b>c</b>: Purchase Invoice Entry<br>
                            - Press <b>d</b>: Payment Voucher<br>
                            - Press <b>e</b>: Customer Receivables<br>
                            - Press <b>f</b>: Vendor Payables<br>
                            - Press <b>g</b>: Ledgers Master<br>
                            - Press <b>h</b>: Item Master<br>
                            - Press <b>1 to 4</b>: Trial Balance, Ledger Statement, Voucher Register, Business View<br>
                            - Press <b>6 to 8</b>: Stock Status, Stock Vouchers, Sales Register<br>
                            - Press <b>F9</b>: Add New Todo | <b>F12</b>: Complete Task<br>
                            - Press <b>F11</b>: Add New Reminder<br>
                            - Press <b>F2 to F5</b>: Cash, Bank, Stock & Customer summaries
                        </div>
                    </div>
                `,
                confirmButtonText: 'Got It!',
                confirmButtonColor: '#15803d'
            });
        }

        // GLOBAL KEYBOARD SHORTCUTS DISPATCHER
        document.addEventListener('keydown', function(e) {
            // Ignore if active inside an input or textarea
            const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
            if (activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select') {
                if (e.key === 'Escape') {
                    closeTodoModal();
                    closeReminderModal();
                    closeGotoModal();
                    closeProfitModal();
                }
                return;
            }

            // Function keys
            if (e.key === 'F2') {
                e.preventDefault();
                showCashModal();
                return;
            }
            if (e.key === 'F3') {
                e.preventDefault();
                showBankModal();
                return;
            }
            if (e.key === 'F4') {
                e.preventDefault();
                window.location = "{{ route('inventory-reports.stock-status') }}";
                return;
            }
            if (e.key === 'F5') {
                e.preventDefault();
                window.location = "{{ route('accounting.receivables') }}";
                return;
            }
            if (e.key === 'F9') {
                e.preventDefault();
                openTodoModal();
                return;
            }
            if (e.key === 'F11') {
                e.preventDefault();
                openReminderModal();
                return;
            }
            if (e.key === 'F12') {
                e.preventDefault();
                toggleTodo();
                return;
            }

            // Ctrl + G for Goto
            if ((e.ctrlKey || e.metaKey) && (e.key === 'g' || e.key === 'G')) {
                e.preventDefault();
                openGotoModal();
                return;
            }

            // Character shortcut keys
            const key = e.key.toLowerCase();
            switch (key) {
                case 'a':
                    window.location = "{{ route('pos.index') }}";
                    break;
                case 'b':
                    window.location = "{{ route('accounting.receipt') }}";
                    break;
                case 'c':
                    window.location = "{{ route('purchases.create') }}";
                    break;
                case 'd':
                    window.location = "{{ route('accounting.payment') }}";
                    break;
                case 'e':
                    window.location = "{{ route('accounting.receivables') }}";
                    break;
                case 'f':
                    window.location = "{{ route('accounting.payables') }}";
                    break;
                case 'g':
                    window.location = "{{ route('accounting.ledgers') }}";
                    break;
                case 'h':
                    window.location = "{{ route('foods.index') }}";
                    break;
                case 'i':
                    window.location = "{{ route('purchase-returns.index') }}";
                    break;
                case 'j':
                    window.location = "{{ route('purchases.index') }}";
                    break;
                case 'k':
                    window.location = "{{ route('inventory-reports.movement-summary') }}";
                    break;
                case 'l':
                    window.location = "{{ route('inventory-reports.gst-reports') }}";
                    break;
                case 'm':
                    window.location = "{{ route('purchase-orders.index') }}";
                    break;
                case 'n':
                    window.location = "{{ route('inventory-reports.barcode-print') }}";
                    break;
                case '1':
                    window.location = "{{ route('accounting.trial-balance') }}";
                    break;
                case '2':
                    window.location = "{{ route('accounting.ledger-statement') }}";
                    break;
                case '3':
                    window.location = "{{ route('accounting.voucher-register') }}";
                    break;
                case '4':
                    window.location = "{{ route('accounting.business-view') }}";
                    break;
                case '6':
                    window.location = "{{ route('inventory-reports.stock-status') }}";
                    break;
                case '7':
                    window.location = "{{ route('inventory-reports.stock-vouchers') }}";
                    break;
                case '8':
                    window.location = "{{ route('accounting.sales-register') }}";
                    break;
                case 't':
                    openTodoModal();
                    break;
                case 'r':
                    openReminderModal();
                    break;
            }
        });
    </script>
</body>
</html>

