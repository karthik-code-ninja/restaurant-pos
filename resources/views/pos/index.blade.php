<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Terminal - {{ \App\Models\Setting::get('restaurant_name', 'RestroPOS') }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .touch-action-manipulation { touch-action: manipulation; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 h-screen flex flex-col overflow-hidden select-none">

    <!-- Top POS Header -->
    <header class="h-14 bg-slate-900 text-white flex items-center justify-between px-4 flex-shrink-0 z-20 shadow-md">
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-300 hover:text-white transition" title="Back to Dashboard">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-orange-600 flex items-center justify-center text-white font-bold">
                    <i class="fa-solid fa-utensils text-sm"></i>
                </span>
                <span class="font-bold text-sm hidden sm:inline">{{ \App\Models\Setting::get('restaurant_name', 'RestroPOS') }}</span>
            </div>
            
            <!-- Order Type Switcher -->
            <div class="flex items-center bg-slate-800 p-0.5 rounded-lg text-xs font-semibold ml-2">
                <button type="button" id="btnTypeTable" onclick="setOrderType('table')" class="px-3 py-1.5 rounded-md transition bg-orange-600 text-white shadow-sm">
                    <i class="fa-solid fa-chair mr-1"></i> Table
                </button>
                <button type="button" id="btnTypeCounter" onclick="setOrderType('counter')" class="px-3 py-1.5 rounded-md transition text-slate-300 hover:text-white">
                    <i class="fa-solid fa-store mr-1"></i> Counter / Takeaway
                </button>
            </div>

            <!-- Table Selector (Visible if Table mode) -->
            <div id="tableSelectWrapper" class="flex items-center">
                <select id="selectedTableId" onchange="onTableSelected()" class="bg-slate-800 text-white text-xs font-bold px-3 py-1.5 rounded-lg border border-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500">
                    <option value="">Select Table...</option>
                    @foreach($tables as $tbl)
                    <option value="{{ $tbl->id }}" data-number="{{ $tbl->table_number }}" {{ ((isset($activeBill) && $activeBill->table_id == $tbl->id) || request('table_id') == $tbl->id) ? 'selected' : '' }}>
                        {{ $tbl->table_number }} ({{ $tbl->status }})
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Center Search Bar -->
        <div class="flex-1 max-w-md mx-4">
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i class="fa-solid fa-barcode text-xs"></i>
                </span>
                <input type="text" id="foodSearchInput" oninput="onSearchInput()" placeholder="Search food by name or food code (e.g. FD101)..."
                    class="w-full pl-9 pr-8 py-1.5 rounded-lg bg-slate-800 text-white text-xs border border-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-orange-500">
                <button type="button" onclick="clearSearch()" id="clearSearchBtn" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-white hidden">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Right Quick Actions -->
        <div class="flex items-center gap-2">
            <button type="button" onclick="openHeldBillsModal()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition">
                <i class="fa-solid fa-pause text-amber-400"></i>
                <span class="hidden sm:inline">Held Bills</span>
            </button>
            
            <button type="button" onclick="openDraftsModal()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition">
                <i class="fa-solid fa-file-pen text-blue-400"></i>
                <span class="hidden sm:inline">Drafts</span>
            </button>

            <div class="h-6 w-px bg-slate-800 mx-1"></div>

            <div class="text-right hidden md:block">
                <p class="text-xs font-bold text-white">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-orange-400 font-medium">Cashier</p>
            </div>
        </div>
    </header>

    <!-- Main 3-Section Split Area -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- SECTION 1: Categories Left Sidebar -->
        <div class="w-48 bg-white border-r border-slate-200 flex flex-col flex-shrink-0 z-10">
            <div class="p-3 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Categories</span>
                <button type="button" id="btnVegFilter" onclick="toggleVegFilter()" class="text-[10px] px-2 py-0.5 rounded-full border border-slate-200 text-slate-600 font-bold hover:bg-slate-50">
                    Veg
                </button>
            </div>
            
            <div class="flex-1 overflow-y-auto p-2 space-y-1" id="categoriesContainer">
                <button type="button" onclick="selectCategory('all')" id="cat_all"
                    class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-between bg-orange-600 text-white shadow-sm category-btn">
                    <span><i class="fa-solid fa-fire mr-1.5"></i> All Items</span>
                </button>

                @foreach($categories as $cat)
                <button type="button" onclick="selectCategory({{ $cat->id }})" id="cat_{{ $cat->id }}"
                    class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-medium text-slate-700 hover:bg-slate-100 transition flex items-center justify-between category-btn">
                    <span class="truncate">{{ $cat->name }}</span>
                    <span class="text-[10px] text-slate-400">{{ $cat->foods()->active()->count() }}</span>
                </button>
                @endforeach
            </div>
        </div>

        <!-- SECTION 2: Food Grid Area -->
        <div class="flex-1 bg-slate-50 flex flex-col overflow-hidden">
            <!-- Active Search / Filter Status Banner -->
            <div id="filterBanner" class="px-4 py-2 bg-white border-b border-slate-200 text-xs text-slate-500 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-700" id="currentCategoryTitle">All Items</span>
                    <span class="text-[11px] text-slate-400" id="foodsCountBadge">Loading items...</span>
                </div>
                <div id="vegIndicator" class="hidden text-xs font-bold text-emerald-600 flex items-center gap-1">
                    <i class="fa-solid fa-leaf"></i> Veg Items Only
                </div>
            </div>

            <!-- Food Items Grid -->
            <div class="flex-1 overflow-y-auto p-4" id="foodsGrid">
                <!-- Food items dynamically rendered by JavaScript -->
            </div>
        </div>

        <!-- SECTION 3: Current Bill / Cart -->
        <div class="w-96 bg-white border-l border-slate-200 flex flex-col flex-shrink-0 shadow-lg z-10">
            <!-- Cart Header -->
            <div class="p-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-800">Current Order</h3>
                        <span id="cartOrderTypeBadge" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-100 text-indigo-700">Table</span>
                        <span id="activeBillIdDisplay" class="text-xs font-mono font-bold text-orange-600"></span>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium" id="orderTargetDisplay">Table: None selected</p>
                </div>

                <button type="button" onclick="clearCart()" title="Clear Current Cart" class="px-2 py-1 text-xs text-rose-600 hover:bg-rose-50 rounded font-semibold transition">
                    <i class="fa-solid fa-rotate-left mr-1"></i> Clear
                </button>
            </div>

            <!-- Cart Items List (Scrollable) -->
            <div class="flex-1 overflow-y-auto p-3 divide-y divide-slate-100" id="cartItemsList">
                <div id="emptyCartMessage" class="py-16 text-center text-slate-400">
                    <i class="fa-solid fa-cart-shopping text-3xl mb-2 text-slate-300 block"></i>
                    <p class="text-xs font-bold text-slate-600">Cart is Empty</p>
                    <p class="text-[11px] text-slate-400 mt-1">Select items from the menu to build the bill.</p>
                </div>
            </div>

            <!-- Cart Totals & Bill Summary (Bottom) -->
            <div class="p-4 border-t border-slate-200 bg-slate-50 space-y-2.5">
                <!-- Customer Details Accordion -->
                <div class="flex items-center gap-2 text-xs">
                    <input type="text" id="custName" placeholder="Customer Name (optional)" class="w-1/2 px-2.5 py-1 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                    <input type="text" id="custPhone" placeholder="Phone Number" class="w-1/2 px-2.5 py-1 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>

                <!-- Discount Input -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <div class="flex items-center gap-2">
                        <span class="text-slate-500 font-medium">Discount:</span>
                        <select id="discountType" onchange="triggerCalculation()" class="border border-slate-300 rounded px-1.5 py-0.5 text-xs">
                            <option value="fixed">Fixed (₹)</option>
                            <option value="percentage">Percent (%)</option>
                        </select>
                        <input type="number" id="discountValue" value="0" min="0" oninput="triggerCalculation()" class="w-16 border border-slate-300 rounded px-1.5 py-0.5 text-xs text-right font-bold">
                    </div>
                    <span id="discountAmountDisplay" class="font-bold text-rose-600">-₹0.00</span>
                </div>

                <!-- Financial Breakdown -->
                <div class="space-y-1 text-xs text-slate-600 pt-1 border-t border-slate-200">
                    <div class="flex justify-between">
                        <span>Subtotal (<span id="cartTotalQty">0</span> items)</span>
                        <span id="subtotalDisplay" class="font-semibold text-slate-800">₹0.00</span>
                    </div>
                    <div class="flex justify-between">
                        <span>GST Tax (<span id="taxTypeDisplay">{{ ucfirst($taxType) }}</span>)</span>
                        <span id="taxTotalDisplay" class="font-semibold text-slate-800">₹0.00</span>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-400">
                        <span>CGST + SGST</span>
                        <span id="cgstSgstDisplay">₹0.00 + ₹0.00</span>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-400">
                        <span>Rounding Diff</span>
                        <span id="roundingDisplay">₹0.00</span>
                    </div>
                </div>

                <!-- Grand Total Big Banner -->
                <div class="p-3 rounded-xl bg-slate-900 text-white flex items-center justify-between shadow-inner">
                    <div>
                        <p class="text-[10px] font-bold text-orange-400 uppercase tracking-wider">Grand Total</p>
                        <h4 id="grandTotalDisplay" class="text-2xl font-black text-white">₹0.00</h4>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 font-bold border border-emerald-800">Tax Verified</span>
                    </div>
                </div>

                <!-- Bottom POS Action Buttons -->
                <div class="grid grid-cols-4 gap-2 pt-1">
                    <button type="button" onclick="saveAsHeld()" class="py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs flex flex-col items-center justify-center gap-1 shadow-sm transition">
                        <i class="fa-solid fa-pause"></i>
                        <span>Hold</span>
                    </button>

                    <button type="button" onclick="saveAsDraft()" class="py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 text-white font-bold text-xs flex flex-col items-center justify-center gap-1 shadow-sm transition">
                        <i class="fa-solid fa-file-pen"></i>
                        <span>Draft</span>
                    </button>

                    <button type="button" onclick="openPaymentModal()" class="col-span-2 py-2.5 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-500 hover:to-amber-500 text-white font-extrabold text-sm flex items-center justify-center gap-2 shadow-lg shadow-orange-600/30 transition">
                        <i class="fa-solid fa-credit-card"></i>
                        <span>Pay & Settle</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: Payment Checkout Modal -->
    <div id="paymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold">Process Bill Payment</h3>
                    <p class="text-xs text-orange-400" id="payModalInvoiceDisplay">New Bill Settlement</p>
                </div>
                <button type="button" onclick="closePaymentModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <div class="p-6 space-y-4">
                <!-- Amount Due Banner -->
                <div class="p-4 bg-orange-50 border border-orange-200 rounded-xl flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-orange-800 uppercase tracking-wider">Total Payable</span>
                        <h3 id="payDueAmountDisplay" class="text-3xl font-black text-orange-950">₹0.00</h3>
                    </div>
                    <div class="text-right text-xs text-orange-700 font-medium">
                        <span id="payDueItemsCount">0 items</span>
                    </div>
                </div>

                <!-- Payment Method Tabs -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Select Payment Method</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" onclick="selectPayMethod('cash')" id="payTab_cash" class="pay-method-btn p-3 rounded-xl border border-orange-600 bg-orange-50 text-orange-800 font-bold text-xs flex flex-col items-center gap-1.5 transition">
                            <i class="fa-solid fa-money-bill-wave text-lg"></i>
                            <span>Cash</span>
                        </button>
                        <button type="button" onclick="selectPayMethod('upi')" id="payTab_upi" class="pay-method-btn p-3 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs flex flex-col items-center gap-1.5 transition">
                            <i class="fa-solid fa-qrcode text-lg"></i>
                            <span>UPI / QR</span>
                        </button>
                        <button type="button" onclick="selectPayMethod('card')" id="payTab_card" class="pay-method-btn p-3 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs flex flex-col items-center gap-1.5 transition">
                            <i class="fa-solid fa-credit-card text-lg"></i>
                            <span>Card</span>
                        </button>
                    </div>
                </div>

                <!-- Cash Tendered & Change Area (For Cash) -->
                <div id="cashDetailsArea">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cash Tendered (Received)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 font-bold text-slate-400">₹</span>
                        <input type="number" step="1" id="cashTenderedInput" oninput="calculateChange()" class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-300 font-black text-lg focus:ring-2 focus:ring-orange-500">
                    </div>

                    <!-- Quick Denominations -->
                    <div class="flex items-center gap-2 mt-2">
                        <button type="button" onclick="setTenderedExact()" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">Exact</button>
                        <button type="button" onclick="setTendered(100)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">₹100</button>
                        <button type="button" onclick="setTendered(200)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">₹200</button>
                        <button type="button" onclick="setTendered(500)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">₹500</button>
                        <button type="button" onclick="setTendered(2000)" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold">₹2000</button>
                    </div>

                    <!-- Change Return Display -->
                    <div class="mt-3 p-3 bg-slate-100 rounded-xl flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-600 uppercase">Change to Return:</span>
                        <span id="cashChangeDisplay" class="font-black text-lg text-slate-900">₹0.00</span>
                    </div>
                </div>

                <!-- Reference / Transaction ID (For UPI/Card) -->
                <div id="referenceDetailsArea" class="hidden">
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Transaction Ref / UTR / Approval Code</label>
                    <input type="text" id="payReferenceInput" placeholder="Optional transaction reference" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>

                <div class="pt-4 border-t border-slate-200 flex items-center justify-between">
                    <button type="button" onclick="closePaymentModal()" class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50">Cancel</button>
                    
                    <button type="button" id="btnSubmitPayment" onclick="submitFinalPayment()" class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-extrabold shadow-lg shadow-emerald-600/30 transition flex items-center gap-2">
                        <i class="fa-solid fa-check-double"></i>
                        <span>Complete & Print Bill</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Add-ons Selector Popover Modal -->
    <div id="addonsModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between">
                <h4 class="text-xs font-bold" id="addonsModalItemTitle">Select Add-ons</h4>
                <button type="button" onclick="closeAddonsModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-4 space-y-2 max-h-60 overflow-y-auto" id="addonsListContainer">
                @foreach($addons as $addon)
                <label class="flex items-center justify-between p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" value="{{ $addon->id }}" data-price="{{ $addon->price }}" data-name="{{ $addon->name }}" class="addon-checkbox rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <span class="font-bold text-slate-800">{{ $addon->name }}</span>
                    </div>
                    <span class="font-mono text-slate-600">+₹{{ number_format($addon->price, 2) }}</span>
                </label>
                @endforeach
            </div>
            <div class="p-3 border-t border-slate-100 flex justify-end">
                <button type="button" onclick="saveAddonsForCurrentItem()" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-xs font-bold">Apply Add-ons</button>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Held Bills Modal -->
    <div id="heldBillsModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
            <div class="px-6 py-4 bg-amber-900 text-white flex items-center justify-between">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <i class="fa-solid fa-pause"></i>
                    <span>Held Bills Queue</span>
                </h3>
                <button type="button" onclick="closeHeldBillsModal()" class="text-amber-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-6 max-h-96 overflow-y-auto" id="heldBillsList">
                <div class="text-center py-8 text-slate-400 text-xs">Loading held bills...</div>
            </div>
        </div>
    </div>

    <!-- MODAL 4: Draft Bills Modal -->
    <div id="draftBillsModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <i class="fa-solid fa-file-pen"></i>
                    <span>Saved Draft Orders</span>
                </h3>
                <button type="button" onclick="closeDraftsModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-6 max-h-96 overflow-y-auto" id="draftBillsList">
                <div class="text-center py-8 text-slate-400 text-xs">Loading drafts...</div>
            </div>
        </div>
    </div>

    <!-- MODAL 5: Cancel Bill Confirmation Modal -->
    <div id="cancelModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 bg-rose-900 text-white flex items-center justify-between">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Cancel Active Bill</span>
                </h3>
                <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')" class="text-rose-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-xs text-slate-600">Are you sure you want to cancel this bill? This will restore any deducted inventory and release the dining table.</p>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cancellation Reason *</label>
                    <textarea id="cancelReasonInput" rows="3" required placeholder="Reason for cancellation..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-rose-500"></textarea>
                </div>
                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">No, Keep Bill</button>
                    <button type="button" onclick="confirmCancelBill()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold">Yes, Cancel Bill</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Application Logic -->
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const CURRENCY = "{{ $currency }}";
        const AVAILABLE_ADDONS = @json($addons);
        const INITIAL_ACTIVE_BILL = @json($activeBill);

        // State
        let orderType = 'table'; // 'table' or 'counter'
        let currentTableId = null;
        let activeBillId = null;
        let activeInvoiceNumber = null;
        let cart = []; // [ { id, item_type: 'food'|'combo', item_id, code, name, unit_price, quantity, notes, addons: [] } ]
        let calculationState = null;
        let selectedCategory = 'all';
        let vegFilterOnly = false;
        let currentActiveItemCartIndex = null;
        let selectedPaymentMethod = 'cash';

        document.addEventListener('DOMContentLoaded', () => {
            fetchFoods();

            // Load initial active bill if passed from table or route
            if (INITIAL_ACTIVE_BILL) {
                resumeBillData(INITIAL_ACTIVE_BILL);
            } else {
                const sel = document.getElementById('selectedTableId');
                if (sel && sel.value) {
                    currentTableId = sel.value;
                    updateOrderTargetDisplay();
                }
            }
        });

        // 1. ORDER TYPE & TABLE LOGIC
        function setOrderType(type) {
            orderType = type;
            const btnTbl = document.getElementById('btnTypeTable');
            const btnCnt = document.getElementById('btnTypeCounter');
            const tblWrapper = document.getElementById('tableSelectWrapper');
            const badge = document.getElementById('cartOrderTypeBadge');

            if (type === 'table') {
                btnTbl.className = 'px-3 py-1.5 rounded-md transition bg-orange-600 text-white shadow-sm';
                btnCnt.className = 'px-3 py-1.5 rounded-md transition text-slate-300 hover:text-white';
                tblWrapper.classList.remove('hidden');
                badge.innerText = 'Table';
                badge.className = 'px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-100 text-indigo-700';
            } else {
                btnCnt.className = 'px-3 py-1.5 rounded-md transition bg-orange-600 text-white shadow-sm';
                btnTbl.className = 'px-3 py-1.5 rounded-md transition text-slate-300 hover:text-white';
                tblWrapper.classList.add('hidden');
                currentTableId = null;
                badge.innerText = 'Counter';
                badge.className = 'px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700';
            }
            updateOrderTargetDisplay();
        }

        function onTableSelected() {
            const sel = document.getElementById('selectedTableId');
            currentTableId = sel.value || null;
            updateOrderTargetDisplay();
        }

        function updateOrderTargetDisplay() {
            const display = document.getElementById('orderTargetDisplay');
            if (!display) return;
            if (orderType === 'table') {
                const sel = document.getElementById('selectedTableId');
                const opt = (sel && sel.selectedIndex >= 0) ? sel.options[sel.selectedIndex] : null;
                display.innerText = (currentTableId && opt && opt.value) ? `Table: ${opt.text}` : 'Table: None selected';
            } else {
                display.innerText = 'Takeaway / Walk-in Counter';
            }
        }

        // 2. FOODS SEARCH & RENDERING
        async function fetchFoods() {
            const query = document.getElementById('foodSearchInput').value;
            const params = new URLSearchParams({
                category_id: selectedCategory,
                query: query,
                veg_only: vegFilterOnly ? '1' : '0'
            });

            try {
                const res = await fetch(
                    `{{ route('pos.search') }}?${params.toString()}`
                );
                const data = await res.json();
                if (data.success) {
                    renderFoodsGrid(data.foods, data.combos);
                }
            } catch (err) {
                console.error("Food fetch error:", err);
            }
        }

        function renderFoodsGrid(foods, combos) {
            const grid = document.getElementById('foodsGrid');
            const countBadge = document.getElementById('foodsCountBadge');
            let html = '';

            const allItems = [...combos, ...foods];
            countBadge.innerText = `${allItems.length} items available`;

            if (allItems.length === 0) {
                grid.innerHTML = `
                    <div class="col-span-full py-16 text-center text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-3xl mb-2 text-slate-300 block"></i>
                        <p class="text-xs font-semibold">No food items found matching criteria.</p>
                    </div>
                `;
                return;
            }

            html += `<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">`;

            allItems.forEach(item => {
                const isCombo = item.type === 'combo';
                const vegDot = isCombo ? '' : (item.is_veg 
                    ? '<span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200" title="Veg"></span>'
                    : '<span class="w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-rose-200" title="Non-Veg"></span>');

                html += `
                    <button type="button" onclick="addToCart('${item.type}', ${item.id}, '${escapeQuotes(item.code)}', '${escapeQuotes(item.name)}', ${item.price})"
                        class="bg-white p-3 rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition text-left flex flex-col justify-between active:scale-95 touch-action-manipulation group">
                        
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded ${isCombo ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600'}">
                                    ${item.code}
                                </span>
                                ${vegDot}
                            </div>
                            
                            <h4 class="font-bold text-slate-800 text-xs line-clamp-2 group-hover:text-orange-600 transition leading-snug">
                                ${item.name}
                            </h4>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="font-black text-slate-900 text-sm">${CURRENCY}${item.price.toFixed(2)}</span>
                            <span class="w-6 h-6 rounded-lg bg-orange-50 text-orange-600 group-hover:bg-orange-600 group-hover:text-white flex items-center justify-center text-xs font-bold transition">
                                <i class="fa-solid fa-plus"></i>
                            </span>
                        </div>
                    </button>
                `;
            });

            html += `</div>`;
            grid.innerHTML = html;
        }

        function escapeQuotes(str) {
            return (str || '').replace(/'/g, "\\'");
        }

        function selectCategory(catId) {
            selectedCategory = catId;
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.className = 'w-full text-left px-3 py-2.5 rounded-xl text-xs font-medium text-slate-700 hover:bg-slate-100 transition flex items-center justify-between category-btn';
            });
            const activeBtn = document.getElementById(`cat_${catId}`);
            if (activeBtn) {
                activeBtn.className = 'w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-between bg-orange-600 text-white shadow-sm category-btn';
            }
            document.getElementById('currentCategoryTitle').innerText = activeBtn ? activeBtn.querySelector('span').innerText : 'All Items';
            fetchFoods();
        }

        function toggleVegFilter() {
            vegFilterOnly = !vegFilterOnly;
            const btn = document.getElementById('btnVegFilter');
            const ind = document.getElementById('vegIndicator');
            if (vegFilterOnly) {
                btn.className = 'text-[10px] px-2 py-0.5 rounded-full bg-emerald-600 text-white font-bold';
                ind.classList.remove('hidden');
            } else {
                btn.className = 'text-[10px] px-2 py-0.5 rounded-full border border-slate-200 text-slate-600 font-bold hover:bg-slate-50';
                ind.classList.add('hidden');
            }
            fetchFoods();
        }

        let searchDebounce = null;
        function onSearchInput() {
            const query = document.getElementById('foodSearchInput').value;
            document.getElementById('clearSearchBtn').classList.toggle('hidden', query.length === 0);
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(fetchFoods, 200);
        }

        function clearSearch() {
            document.getElementById('foodSearchInput').value = '';
            document.getElementById('clearSearchBtn').classList.add('hidden');
            fetchFoods();
        }

        // 3. CART OPERATIONS
        function addToCart(type, id, code, name, price) {
            // Check if existing item in cart (without notes/addons)
            const existingIndex = cart.findIndex(c => c.item_type === type && c.item_id === id && (!c.addons || c.addons.length === 0) && !c.notes);
            if (existingIndex > -1) {
                cart[existingIndex].quantity += 1;
            } else {
                cart.push({
                    item_type: type,
                    item_id: id,
                    code: code,
                    name: name,
                    unit_price: parseFloat(price),
                    quantity: 1,
                    notes: '',
                    addons: []
                });
            }
            renderCart();
            triggerCalculation();
        }

        function updateCartQty(index, delta) {
            cart[index].quantity += delta;
            if (cart[index].quantity <= 0) {
                cart.splice(index, 1);
            }
            renderCart();
            triggerCalculation();
        }

        function removeCartItem(index) {
            cart.splice(index, 1);
            renderCart();
            triggerCalculation();
        }

        async function clearCart(skipServerSync = false) {
            if (!skipServerSync) {
                if (cart.length === 0 && !activeBillId && !currentTableId) {
                    return;
                }

                const confirmMsg = activeBillId
                    ? `Clear order #${activeInvoiceNumber}? This will remove it from database and set table status to available.`
                    : 'Are you sure you want to clear current order?';

                if (!confirm(confirmMsg)) return;

                if (activeBillId || currentTableId) {
                    try {
                        const res = await fetch(`{{ route('pos.clear') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF_TOKEN
                            },
                            body: JSON.stringify({
                                bill_id: activeBillId,
                                table_id: currentTableId
                            })
                        });
                        const data = await res.json();
                        if (data.success && data.table_id) {
                            const sel = document.getElementById('selectedTableId');
                            if (sel) {
                                for (let i = 0; i < sel.options.length; i++) {
                                    if (sel.options[i].value == data.table_id) {
                                        const tblNum = sel.options[i].getAttribute('data-number') || data.table_number || '';
                                        sel.options[i].text = `${tblNum} (available)`;
                                        break;
                                    }
                                }
                            }
                        }
                    } catch (err) {
                        console.error('Error clearing bill in database:', err);
                    }
                }
            }

            cart = [];
            activeBillId = null;
            activeInvoiceNumber = null;
            document.getElementById('activeBillIdDisplay').innerText = '';

            const sel = document.getElementById('selectedTableId');
            if (sel) {
                sel.value = '';
            }
            currentTableId = null;
            updateOrderTargetDisplay();

            const custName = document.getElementById('custName');
            if (custName) custName.value = '';
            const custPhone = document.getElementById('custPhone');
            if (custPhone) custPhone.value = '';
            const discType = document.getElementById('discountType');
            if (discType) discType.value = 'fixed';
            const discVal = document.getElementById('discountValue');
            if (discVal) discVal.value = '0';

            renderCart();
            triggerCalculation();

            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        }

        function renderCart() {
            const container = document.getElementById('cartItemsList');
            const totalQtySpan = document.getElementById('cartTotalQty');

            if (cart.length === 0) {
                container.innerHTML = `
                    <div id="emptyCartMessage" class="py-16 text-center text-slate-400">
                        <i class="fa-solid fa-cart-shopping text-3xl mb-2 text-slate-300 block"></i>
                        <p class="text-xs font-bold text-slate-600">Cart is Empty</p>
                        <p class="text-[11px] text-slate-400 mt-1">Select items from the menu to build the bill.</p>
                    </div>
                `;
                totalQtySpan.innerText = '0';
                return;
            }

            let totalQty = 0;
            let html = '';

            cart.forEach((item, index) => {
                totalQty += item.quantity;
                const itemTotal = (item.unit_price * item.quantity).toFixed(2);
                
                // Addons list string
                let addonsHtml = '';
                if (item.addons && item.addons.length > 0) {
                    const addonNames = AVAILABLE_ADDONS.filter(a => item.addons.includes(a.id)).map(a => a.name).join(', ');
                    addonsHtml = `<p class="text-[10px] text-orange-600 font-medium mt-0.5">+ ${addonNames}</p>`;
                }

                html += `
                    <div class="py-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <h5 class="text-xs font-bold text-slate-800 truncate">${item.name}</h5>
                                <p class="text-[10px] text-slate-400">${item.code} &bull; ${CURRENCY}${item.unit_price.toFixed(2)} ea</p>
                                ${addonsHtml}
                            </div>
                            
                            <!-- Qty Controls -->
                            <div class="flex items-center border border-slate-200 rounded-lg overflow-hidden bg-white">
                                <button type="button" onclick="updateCartQty(${index}, -1)" class="w-6 h-6 flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold text-xs">-</button>
                                <span class="w-7 text-center font-bold text-xs text-slate-900">${item.quantity}</span>
                                <button type="button" onclick="updateCartQty(${index}, 1)" class="w-6 h-6 flex items-center justify-center text-slate-600 hover:bg-slate-100 font-bold text-xs">+</button>
                            </div>

                            <span class="w-16 text-right font-black text-xs text-slate-900">${CURRENCY}${itemTotal}</span>

                            <button type="button" onclick="removeCartItem(${index})" class="text-slate-300 hover:text-rose-500 p-1">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>

                        <!-- Item extras / Add-ons button -->
                        <div class="mt-1 flex items-center gap-2">
                            <button type="button" onclick="openAddonsModal(${index})" class="text-[10px] text-slate-500 hover:text-orange-600 font-semibold">
                                <i class="fa-solid fa-circle-plus text-orange-500 mr-1"></i> Add-on
                            </button>
                            <span class="text-slate-200">&bull;</span>
                            <input type="text" value="${item.notes || ''}" onchange="cart[${index}].notes = this.value" placeholder="Kitchen note..." class="text-[10px] border-b border-transparent hover:border-slate-200 focus:border-orange-500 px-1 py-0.5 text-slate-500 flex-1 focus:outline-none">
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
            totalQtySpan.innerText = totalQty;
        }

        // 4. CENTRALIZED BACKEND CALCULATION TRIGGER
        async function triggerCalculation() {
            if (cart.length === 0) {
                document.getElementById('subtotalDisplay').innerText = `${CURRENCY}0.00`;
                document.getElementById('taxTotalDisplay').innerText = `${CURRENCY}0.00`;
                document.getElementById('cgstSgstDisplay').innerText = `${CURRENCY}0.00 + ${CURRENCY}0.00`;
                document.getElementById('discountAmountDisplay').innerText = `-${CURRENCY}0.00`;
                document.getElementById('roundingDisplay').innerText = `${CURRENCY}0.00`;
                document.getElementById('grandTotalDisplay').innerText = `${CURRENCY}0.00`;
                calculationState = null;
                return;
            }

            const discountType = document.getElementById('discountType').value;
            const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;

            try {
                const res = await fetch(
                    `{{ route('pos.calculate') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        items: cart,
                        discount_type: discountType,
                        discount_value: discountValue
                    })
                });

                const json = await res.json();
                if (json.success) {
                    calculationState = json.data;
                    document.getElementById('subtotalDisplay').innerText = `${CURRENCY}${calculationState.subtotal.toFixed(2)}`;
                    document.getElementById('taxTotalDisplay').innerText = `${CURRENCY}${calculationState.tax_total.toFixed(2)}`;
                    document.getElementById('cgstSgstDisplay').innerText = `${CURRENCY}${calculationState.cgst_total.toFixed(2)} + ${CURRENCY}${calculationState.sgst_total.toFixed(2)}`;
                    document.getElementById('discountAmountDisplay').innerText = `-${CURRENCY}${calculationState.discount_amount.toFixed(2)}`;
                    document.getElementById('roundingDisplay').innerText = `${CURRENCY}${calculationState.rounding_difference.toFixed(2)}`;
                    document.getElementById('grandTotalDisplay').innerText = `${CURRENCY}${calculationState.grand_total.toFixed(2)}`;
                }
            } catch (err) {
                console.error("Calculation error:", err);
            }
        }

        // 5. HOLD & DRAFT BILLS
        async function saveAsHeld() {
            await saveBillWithStatus('held');
        }

        async function saveAsDraft() {
            await saveBillWithStatus('draft');
        }

        async function saveBillWithStatus(status) {
            if (cart.length === 0) {
                alert('Cart is empty. Please add items to hold/save bill.');
                return;
            }

            if (orderType === 'table' && !currentTableId) {
                alert('Please select a dining table for table-based orders.');
                return;
            }

            const payload = {
                bill_id: activeBillId,
                order_type: orderType,
                table_id: currentTableId,
                status: status,
                customer_name: document.getElementById('custName').value,
                customer_phone: document.getElementById('custPhone').value,
                discount_type: document.getElementById('discountType').value,
                discount_value: parseFloat(document.getElementById('discountValue').value) || 0,
                items: cart
            };

            try {
                const res = await fetch(
                    `{{ route('pos.save') }}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    clearCart(true);
                } else {
                    alert(data.message || 'Failed to save bill.');
                }
            } catch (err) {
                alert('Network error while saving bill.');
            }
        }

        // 6. PAYMENT MODAL & COMPLETION
        function openPaymentModal() {
            if (cart.length === 0 || !calculationState) {
                alert('Cart is empty! Add items before initiating payment.');
                return;
            }

            if (orderType === 'table' && !currentTableId) {
                alert('Please select a dining table before settling.');
                return;
            }

            document.getElementById('payDueAmountDisplay').innerText = `${CURRENCY}${calculationState.grand_total.toFixed(2)}`;
            document.getElementById('payDueItemsCount').innerText = `${calculationState.total_quantity} items`;
            document.getElementById('cashTenderedInput').value = calculationState.grand_total;
            calculateChange();
            selectPayMethod('cash');

            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        function selectPayMethod(method) {
            selectedPaymentMethod = method;
            document.querySelectorAll('.pay-method-btn').forEach(btn => {
                btn.className = 'pay-method-btn p-3 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs flex flex-col items-center gap-1.5 transition';
            });
            const active = document.getElementById(`payTab_${method}`);
            if (active) {
                active.className = 'pay-method-btn p-3 rounded-xl border border-orange-600 bg-orange-50 text-orange-800 font-bold text-xs flex flex-col items-center gap-1.5 transition';
            }

            const cashArea = document.getElementById('cashDetailsArea');
            const refArea = document.getElementById('referenceDetailsArea');
            if (method === 'cash') {
                cashArea.classList.remove('hidden');
                refArea.classList.add('hidden');
            } else {
                cashArea.classList.add('hidden');
                refArea.classList.remove('hidden');
            }
        }

        function setTendered(amt) {
            document.getElementById('cashTenderedInput').value = amt;
            calculateChange();
        }

        function setTenderedExact() {
            if (calculationState) {
                document.getElementById('cashTenderedInput').value = calculationState.grand_total;
                calculateChange();
            }
        }

        function calculateChange() {
            if (!calculationState) return;
            const tendered = parseFloat(document.getElementById('cashTenderedInput').value) || 0;
            const due = calculationState.grand_total;
            const diff = Math.max(0, tendered - due);
            document.getElementById('cashChangeDisplay').innerText = `${CURRENCY}${diff.toFixed(2)}`;
        }

        async function submitFinalPayment() {
            const btn = document.getElementById('btnSubmitPayment');
            btn.disabled = true;
            btn.innerText = 'Processing...';

            const payload = {
                bill_id: activeBillId,
                order_type: orderType,
                table_id: currentTableId,
                customer_name: document.getElementById('custName').value,
                customer_phone: document.getElementById('custPhone').value,
                discount_type: document.getElementById('discountType').value,
                discount_value: parseFloat(document.getElementById('discountValue').value) || 0,
                items: cart,
                payments: [
                    {
                        payment_method: selectedPaymentMethod,
                        amount: calculationState.grand_total,
                        reference_number: document.getElementById('payReferenceInput').value || null
                    }
                ]
            };

            try {
                const res = await fetch(
                    `{{ route('pos.pay') }}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    closePaymentModal();
                    // Open thermal print popup
                    window.open(data.print_url, '_blank', 'width=400,height=600');
                    alert(data.message);
                    clearCart(true);
                } else {
                    alert(data.message || 'Payment processing failed.');
                }
            } catch (err) {
                alert('Payment submission error.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-double"></i><span>Complete & Print Bill</span>';
            }
        }

        // 7. ADD-ONS POPOVER
        function openAddonsModal(cartIndex) {
            currentActiveItemCartIndex = cartIndex;
            const item = cart[cartIndex];
            document.getElementById('addonsModalItemTitle').innerText = `Add-ons for: ${item.name}`;
            
            document.querySelectorAll('.addon-checkbox').forEach(cb => {
                cb.checked = item.addons.includes(parseInt(cb.value));
            });

            document.getElementById('addonsModal').classList.remove('hidden');
        }

        function closeAddonsModal() {
            document.getElementById('addonsModal').classList.add('hidden');
        }

        function saveAddonsForCurrentItem() {
            if (currentActiveItemCartIndex === null) return;
            const selectedAddonIds = [];
            document.querySelectorAll('.addon-checkbox:checked').forEach(cb => {
                selectedAddonIds.push(parseInt(cb.value));
            });
            cart[currentActiveItemCartIndex].addons = selectedAddonIds;
            closeAddonsModal();
            renderCart();
            triggerCalculation();
        }

        // 8. HELD BILLS & DRAFTS
        async function openHeldBillsModal() {
            const list = document.getElementById('heldBillsList');
            document.getElementById('heldBillsModal').classList.remove('hidden');
            list.innerHTML = '<p class="text-center py-6 text-xs text-slate-400">Loading held bills...</p>';

            try {
                const res = await fetch(
                    `{{ route('pos.held') }}`
                );
                const data = await res.json();
                if (data.bills.length === 0) {
                    list.innerHTML = '<p class="text-center py-8 text-xs font-semibold text-slate-500">No active held bills.</p>';
                    return;
                }

                let html = '<div class="space-y-3">';
                data.bills.forEach(b => {
                    const tableInfo = b.table ? `Table ${b.table.table_number}` : 'Counter';
                    html += `
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between">
                            <div>
                                <span class="font-mono font-bold text-xs text-slate-900">#${b.invoice_number}</span>
                                <span class="ml-2 px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold uppercase">${tableInfo}</span>
                                <p class="text-xs text-slate-500 mt-1">${b.items.length} items &bull; Created ${new Date(b.created_at).toLocaleTimeString()}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-black text-sm text-slate-900">${CURRENCY}${parseFloat(b.grand_total).toFixed(2)}</span>
                                <button type="button" onclick="resumeHeldBill(${b.id})" class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold shadow-sm">
                                    Resume
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                list.innerHTML = html;
            } catch (err) {
                list.innerHTML = '<p class="text-center text-xs text-rose-500">Failed to load held bills.</p>';
            }
        }

        function closeHeldBillsModal() {
            document.getElementById('heldBillsModal').classList.add('hidden');
        }

        async function resumeHeldBill(billId) {
            closeHeldBillsModal();
            try {
                const resumeUrl = `{{ route('pos.resume', ['bill' => ':id']) }}`.replace(':id', billId);
                const res = await fetch(resumeUrl);
                const data = await res.json();
                if (data.success) {
                    resumeBillData(data.bill);
                }
            } catch (err) {
                alert('Failed to resume held bill.');
            }
        }

        async function openDraftsModal() {
            const list = document.getElementById('draftBillsList');
            document.getElementById('draftBillsModal').classList.remove('hidden');
            list.innerHTML = '<p class="text-center py-6 text-xs text-slate-400">Loading drafts...</p>';

            try {
                const res = await fetch(
                    `{{ route('pos.drafts') }}`
                );
                const data = await res.json();
                if (data.bills.length === 0) {
                    list.innerHTML = '<p class="text-center py-8 text-xs font-semibold text-slate-500">No saved draft bills.</p>';
                    return;
                }

                let html = '<div class="space-y-3">';
                data.bills.forEach(b => {
                    html += `
                        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between">
                            <div>
                                <span class="font-mono font-bold text-xs text-slate-900">#${b.invoice_number}</span>
                                <span class="ml-2 px-2 py-0.5 rounded bg-slate-200 text-slate-700 text-[10px] font-bold uppercase">${b.order_type}</span>
                                <p class="text-xs text-slate-500 mt-1">${b.items.length} items</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-black text-sm text-slate-900">${CURRENCY}${parseFloat(b.grand_total).toFixed(2)}</span>
                                <button type="button" onclick="resumeDraftBill(${b.id})" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-bold">
                                    Open Draft
                                </button>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                list.innerHTML = html;
            } catch (err) {
                list.innerHTML = '<p class="text-center text-xs text-rose-500">Failed to load drafts.</p>';
            }
        }

        function closeDraftsModal() {
            document.getElementById('draftBillsModal').classList.add('hidden');
        }

        async function resumeDraftBill(billId) {
            closeDraftsModal();
            try {
                const resumeUrl = `{{ route('pos.resume', ['bill' => ':id']) }}`.replace(':id', billId);
                const res = await fetch(resumeUrl);
                const data = await res.json();
                if (data.success) {
                    resumeBillData(data.bill);
                }
            } catch (err) {
                alert('Failed to resume draft.');
            }
        }

        function resumeBillData(bill) {
            activeBillId = bill.id;
            activeInvoiceNumber = bill.invoice_number;
            document.getElementById('activeBillIdDisplay').innerText = `#${bill.invoice_number}`;
            setOrderType(bill.order_type);
            
            if (bill.table_id) {
                document.getElementById('selectedTableId').value = bill.table_id;
                currentTableId = bill.table_id;
            }
            updateOrderTargetDisplay();

            document.getElementById('custName').value = bill.customer_name || '';
            document.getElementById('custPhone').value = bill.customer_phone || '';
            document.getElementById('discountType').value = bill.discount_type || 'fixed';
            document.getElementById('discountValue').value = bill.discount_value || 0;

            cart = bill.items.map(item => ({
                item_type: item.item_type,
                item_id: item.item_id,
                code: item.food_code || item.code,
                name: item.item_name || item.name,
                unit_price: parseFloat(item.unit_price),
                quantity: parseFloat(item.quantity),
                notes: item.notes || '',
                addons: item.addons || []
            }));

            renderCart();
            triggerCalculation();
        }
    </script>
</body>
</html>
