<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Terminal - {{ \App\Models\Setting::get('restaurant_name', 'RestroPOS') }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .touch-action-manipulation { touch-action: manipulation; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .smooth-scroll { -webkit-overflow-scrolling: touch; }
        * { -webkit-tap-highlight-color: transparent; }
        @keyframes linkedPopupPop {
            0% { opacity: 0; transform: translateY(4px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        .linked-popup-animate {
            animation: linkedPopupPop 0.12s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 h-screen flex flex-col overflow-hidden select-none">

    <!-- Top POS Header: Responsive Multi-Flex Layout -->
    <header class="relative bg-slate-900 text-white flex flex-col lg:flex-row lg:items-center justify-between px-3 sm:px-4 py-2 lg:py-0 lg:h-14 flex-shrink-0 z-50 shadow-md gap-2 lg:gap-3">
        <!-- Top Row on Mobile / Left Section on Desktop -->
        <div class="flex items-center justify-between lg:justify-start gap-2 w-full lg:w-auto">
            <div class="flex items-center gap-1.5 sm:gap-2">
                <a href="{{ route('dashboard') }}" class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-300 hover:text-white transition shrink-0 active:scale-95" title="Back to Dashboard">
                    <i class="fa-solid fa-arrow-left text-xs sm:text-sm"></i>
                </a>
                
                <div class="flex items-center gap-1.5 shrink-0">
                    <span class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-orange-600 flex items-center justify-center text-white font-bold shrink-0">
                        <i class="fa-solid fa-utensils text-xs sm:text-sm"></i>
                    </span>
                    <span class="font-bold text-xs sm:text-sm hidden xl:inline">{{ \App\Models\Setting::get('restaurant_name', 'RestroPOS') }}</span>
                </div>
                
                <!-- Order Type Switcher -->
                <div class="flex items-center bg-slate-800 p-0.5 rounded-lg text-[11px] sm:text-xs font-semibold ml-1 shrink-0">
                    <button type="button" id="btnTypeTable" onclick="setOrderType('table')" class="px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-md transition bg-orange-600 text-white shadow-sm flex items-center gap-1">
                        <i class="fa-solid fa-chair text-[10px] sm:text-xs"></i> <span>Table</span>
                    </button>
                    <button type="button" id="btnTypeCounter" onclick="setOrderType('counter')" class="px-2.5 sm:px-3 py-1 sm:py-1.5 rounded-md transition text-slate-300 hover:text-white flex items-center gap-1">
                        <i class="fa-solid fa-store text-[10px] sm:text-xs"></i> <span>Counter</span>
                    </button>
                </div>

                <!-- Table Selector (Visible if Table mode) -->
                <div id="tableSelectWrapper" class="flex items-center shrink-0">
                    <select id="selectedTableId" onchange="onTableSelected()" class="bg-slate-800 text-white text-[11px] sm:text-xs font-bold px-2 sm:px-3 py-1 sm:py-1.5 rounded-lg border border-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500 max-w-[105px] sm:max-w-none">
                        <option value="">Table...</option>
                        @foreach($tables as $tbl)
                        <option value="{{ $tbl->id }}" data-number="{{ $tbl->table_number }}" {{ ((isset($activeBill) && $activeBill->table_id == $tbl->id) || request('table_id') == $tbl->id) ? 'selected' : '' }}>
                            {{ $tbl->table_number }} ({{ $tbl->status }})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Mobile-Only Action Icons in Top Row -->
            <div class="flex items-center gap-1.5 lg:hidden shrink-0">
                <button type="button" onclick="openHeldBillsModal()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 flex items-center justify-center text-xs active:scale-95 transition" title="Held Bills">
                    <i class="fa-solid fa-pause text-amber-400"></i>
                </button>
                <button type="button" onclick="openDraftsModal()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 flex items-center justify-center text-xs active:scale-95 transition" title="Draft Orders">
                    <i class="fa-solid fa-file-pen text-blue-400"></i>
                </button>
                <button type="button" onclick="toggleMobileCart()" class="px-2.5 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-black flex items-center gap-1.5 shadow-sm active:scale-95 transition">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span id="mobileCartHeaderCount" class="bg-white text-orange-600 px-1.5 py-0.2 rounded-full text-[10px] font-black">0</span>
                </button>
            </div>
        </div>

        <!-- Search Bar + Waiter Selector: Full-width on mobile, center on desktop -->
        <div class="w-full lg:flex-1 lg:max-w-md lg:mx-2 flex items-center gap-2">
            <div class="relative flex-1" id="searchContainer">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 pointer-events-none">
                    <i class="fa-solid fa-barcode text-xs"></i>
                </span>
                <input type="text" id="foodSearchInput" oninput="onSearchInput()" onkeydown="onSearchKeyDown(event)" onfocus="onSearchFocus()" autocomplete="off" placeholder="Search food by name or code (FD101)..."
                    class="w-full pl-9 pr-8 py-1.5 sm:py-2 lg:py-1.5 rounded-xl lg:rounded-lg bg-slate-800 text-white text-xs border border-slate-700 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-orange-500">
                <button type="button" onclick="clearSearch()" id="clearSearchBtn" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-white hidden">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>

                <!-- Floating Search Results Dropdown -->
                <div id="searchDropdown" class="absolute top-full left-0 sm:right-auto w-[96vw] sm:w-[560px] lg:w-[580px] max-w-[calc(100vw-24px)] mt-1.5 bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden hidden z-[100] transition-all duration-150">
                    <!-- Dropdown Header Bar -->
                    <div class="px-3.5 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[11px]">
                        <span id="searchDropdownCount" class="font-bold text-slate-600">0 products found</span>
                        <div class="hidden sm:flex items-center gap-2 text-[10px] text-slate-400 font-medium">
                            <span><kbd class="px-1 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-sans font-bold">↑↓</kbd> navigate</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-sans font-bold">↵</kbd> add to cart</span>
                            <span><kbd class="px-1 py-0.5 bg-white border border-slate-200 rounded text-[9px] font-sans font-bold">esc</kbd> close</span>
                        </div>
                    </div>

                    <!-- Dropdown List Items -->
                    <div id="searchDropdownList" class="max-h-72 sm:max-h-96 overflow-y-auto">
                        <!-- Populated dynamically via JS -->
                    </div>
                </div>

                <!-- Windows Billing Software Style Linked Product Detail Popup -->
                <div id="searchProductLinkedPopup"
                    class="fixed z-[110] w-[340px] sm:w-[370px] bg-white rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-900/10 overflow-hidden hidden transition-all duration-150 pointer-events-auto select-none">
                    
                    <!-- Top Gradient Header -->
                    <div class="px-4 py-2.5 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            <span id="linkedPopupTypeBadge"></span>
                            <span id="linkedPopupCategoryBadge" class="text-[10px] font-bold px-2 py-0.5 rounded bg-white/10 text-slate-200 uppercase tracking-wider"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-mono text-orange-400 font-bold" id="linkedPopupCode"></span>
                            <button type="button" onclick="hideSearchProductPreview()" class="w-5 h-5 rounded-full bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white flex items-center justify-center text-xs transition" title="Close Preview">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="p-3.5 sm:p-4 space-y-3">
                        <!-- Product Title & Identity -->
                        <div class="flex items-start gap-3">
                            <div id="linkedPopupImageContainer" class="w-14 h-14 rounded-xl bg-slate-100 border border-slate-200 flex-shrink-0 flex items-center justify-center overflow-hidden shadow-inner">
                                <!-- Dynamic thumbnail/icon -->
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 id="linkedPopupName" class="text-sm font-extrabold text-slate-900 leading-snug line-clamp-2"></h4>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1.5">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 font-mono text-[10px] font-bold">
                                        <i class="fa-solid fa-barcode text-[10px] text-slate-400"></i>
                                        <span id="linkedPopupBarcodeText"></span>
                                    </span>
                                    <span id="linkedPopupHsnBadge" class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-mono text-[10px] font-medium"></span>
                                    <span id="linkedPopupGstBadge" class="inline-flex items-center px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[10px]"></span>
                                </div>
                            </div>
                        </div>

                        <!-- 4 Core Windows Billing Metric Tiles: QTY / MRP / SELLING / COST -->
                        <div class="grid grid-cols-2 gap-2">
                            <!-- Metric 1: Product Qty (Stock) -->
                            <div id="linkedPopupStockBox" class="p-2.5 rounded-xl border flex flex-col justify-between transition">
                                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">
                                    <span>Stock Qty</span>
                                    <i class="fa-solid fa-boxes-stacked text-[11px] text-slate-400"></i>
                                </div>
                                <div class="flex items-baseline gap-1">
                                    <span id="linkedPopupStockVal" class="text-lg sm:text-xl font-black text-slate-900">0</span>
                                    <span class="text-[10px] font-semibold text-slate-400">units</span>
                                </div>
                                <div id="linkedPopupStockBadge" class="mt-1"></div>
                            </div>

                            <!-- Metric 2: MRP Rate -->
                            <div class="p-2.5 rounded-xl bg-purple-50/70 border border-purple-200 flex flex-col justify-between">
                                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-purple-700 mb-0.5">
                                    <span>MRP Rate</span>
                                    <i class="fa-solid fa-certificate text-[11px] text-purple-500"></i>
                                </div>
                                <div class="flex items-baseline gap-0.5">
                                    <span class="text-xs font-bold text-purple-500">{{ $currency }}</span>
                                    <span id="linkedPopupMrpVal" class="text-lg sm:text-xl font-black text-purple-700">0.00</span>
                                </div>
                                <span class="text-[9px] text-purple-600/80 font-medium">Max Retail Price</span>
                            </div>

                            <!-- Metric 3: Selling Price -->
                            <div class="p-2.5 rounded-xl bg-orange-50/80 border border-orange-200 flex flex-col justify-between">
                                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-orange-600 mb-0.5">
                                    <span>Selling Price</span>
                                    <i class="fa-solid fa-tag text-[11px] text-orange-500"></i>
                                </div>
                                <div class="flex items-baseline gap-0.5">
                                    <span class="text-xs font-bold text-orange-500">{{ $currency }}</span>
                                    <span id="linkedPopupPriceVal" class="text-lg sm:text-xl font-black text-orange-600">0.00</span>
                                </div>
                                <span class="text-[9px] text-orange-600/90 font-bold">POS Billing Rate</span>
                            </div>

                            <!-- Metric 4: Cost Price -->
                            <div class="p-2.5 rounded-xl bg-slate-50/90 border border-slate-200 flex flex-col justify-between">
                                <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">
                                    <span>Cost Price</span>
                                    <i class="fa-solid fa-receipt text-[11px] text-slate-400"></i>
                                </div>
                                <div class="flex items-baseline gap-0.5">
                                    <span class="text-xs font-bold text-slate-400">{{ $currency }}</span>
                                    <span id="linkedPopupCostVal" class="text-lg sm:text-xl font-black text-slate-700">0.00</span>
                                </div>
                                <span class="text-[9px] text-slate-400 font-medium">Purchase cost</span>
                            </div>
                        </div>

                        <!-- Profit Margin & MRP Discount Bar -->
                        <div id="linkedPopupMarginStrip" class="p-2 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">
                                    <i class="fa-solid fa-chart-line"></i>
                                </span>
                                <div>
                                    <div class="text-[10px] uppercase font-bold text-emerald-800">Profit Margin</div>
                                    <div class="text-xs font-black text-emerald-700" id="linkedPopupProfitVal">+₹0.00 (0% margin)</div>
                                </div>
                            </div>
                            <div id="linkedPopupDiscountBadge" class="text-right"></div>
                        </div>

                        <!-- Description Note (if available) -->
                        <div id="linkedPopupDescWrapper" class="hidden">
                            <p id="linkedPopupDescText" class="text-[11px] text-slate-500 italic bg-slate-50 p-2 rounded-lg border border-slate-100 line-clamp-2"></p>
                        </div>

                        <!-- Instant Action Bar -->
                        <div class="pt-1 border-t border-slate-100 flex items-center gap-2">
                            <button type="button" id="linkedPopupAddBtn" onclick="onTitleCardAddClick()"
                                class="flex-1 py-2 px-3 bg-orange-600 hover:bg-orange-700 active:scale-98 text-white rounded-xl text-xs font-black shadow-md shadow-orange-600/20 flex items-center justify-center gap-2 transition cursor-pointer">
                                <i class="fa-solid fa-plus"></i>
                                <span>Add to Bill</span>
                                <kbd class="px-1.5 py-0.5 rounded bg-orange-700 text-[10px] font-mono font-bold tracking-tight">↵ Enter</kbd>
                            </button>
                            <span class="text-[10px] text-slate-400 font-semibold px-1">↑↓ Move</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Waiter / Staff Selector -->
            @php
                $currentUser = auth()->user();
                $isAdmin = $currentUser && ($currentUser->hasRole(['admin', 'manager']) || $currentUser->id === 1);
                $initialWaiterId = null;
                if (isset($activeBill) && $activeBill->waiter_id) {
                    $initialWaiterId = $activeBill->waiter_id;
                } elseif (!$isAdmin && $currentUser) {
                    $initialWaiterId = $currentUser->id;
                }
            @endphp
            <div id="waiterSelectWrapper" class="flex items-center shrink-0">
                <select id="selectedWaiterId" onchange="onWaiterSelected()" class="bg-slate-800 text-white text-[11px] sm:text-xs font-semibold px-2 sm:px-2.5 py-1.5 sm:py-2 lg:py-1.5 rounded-xl lg:rounded-lg border border-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500 max-w-[105px] sm:max-w-[145px] lg:max-w-none">
                    @if($isAdmin)
                        <option value="">Staff...</option>
                        @foreach($waiters as $w)
                        <option value="{{ $w->id }}" data-name="{{ $w->name }}" {{ $initialWaiterId == $w->id ? 'selected' : '' }}>
                            {{ $w->name }} ({{ $w->role?->name ?? 'Staff' }})
                        </option>
                        @endforeach
                    @else
                        @foreach($waiters as $w)
                        <option value="{{ $w->id }}" data-name="{{ $w->name }}" {{ ($initialWaiterId == $w->id || $w->id == $currentUser->id) ? 'selected' : '' }}>
                            {{ $w->name }}{{ $w->id == $currentUser->id ? ' (You)' : '' }}
                        </option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>

        <!-- Desktop-Only Quick Actions -->
        <div class="hidden lg:flex items-center gap-2 shrink-0">
            <button type="button" onclick="openHeldBillsModal()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition">
                <i class="fa-solid fa-pause text-amber-400"></i>
                <span class="hidden sm:inline">Held Bills</span>
            </button>
            
            <button type="button" onclick="openDraftsModal()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold flex items-center gap-1.5 transition">
                <i class="fa-solid fa-file-pen text-blue-400"></i>
                <span class="hidden sm:inline">Drafts</span>
            </button>

            <div class="h-6 w-px bg-slate-800 mx-1"></div>

            <div class="text-right">
                <p class="text-xs font-bold text-white">{{ auth()->user()->name }}</p>
                <p class="text-[10px] text-orange-400 font-medium">Cashier</p>
            </div>
        </div>
    </header>

    <!-- Main 3-Section Split Area -->
    <div class="flex-1 flex overflow-hidden">
        
        <!-- SECTION 1: Categories Left Sidebar (Desktop) -->
        <div class="hidden lg:flex w-44 xl:w-48 bg-white border-r border-slate-200 flex-col flex-shrink-0 z-10">
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
        <div class="flex-1 bg-slate-50 flex flex-col overflow-hidden relative">
            <!-- Mobile Horizontal Category Chips -->
            <div class="lg:hidden bg-white border-b border-slate-200 px-3 py-2 flex items-center gap-1.5 overflow-x-auto whitespace-nowrap z-10 no-scrollbar smooth-scroll" id="mobileCategoriesBar">
                <button type="button" id="btnMobVegFilter" onclick="toggleVegFilter()"
                    class="px-2.5 py-1.5 rounded-xl text-xs font-bold transition shrink-0 border border-slate-200 text-slate-700 hover:bg-slate-100 flex items-center gap-1">
                    <i class="fa-solid fa-leaf text-emerald-600"></i> Veg
                </button>
                <button type="button" onclick="selectCategory('all')" id="mob_cat_all"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition shrink-0 bg-orange-600 text-white shadow-sm mob-category-btn">
                    <i class="fa-solid fa-fire mr-1"></i> All Items
                </button>
                @foreach($categories as $cat)
                <button type="button" onclick="selectCategory({{ $cat->id }})" id="mob_cat_{{ $cat->id }}"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-100 transition shrink-0 border border-slate-200 mob-category-btn">
                    {{ $cat->name }}
                </button>
                @endforeach
            </div>

            <!-- Active Search / Filter Status Banner -->
            <div id="filterBanner" class="px-3.5 sm:px-4 py-2 bg-white border-b border-slate-200 text-xs text-slate-500 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-700 text-xs sm:text-sm" id="currentCategoryTitle">All Items</span>
                    <span class="text-[11px] text-slate-400" id="foodsCountBadge">Loading items...</span>
                </div>
                <div id="vegIndicator" class="hidden text-xs font-bold text-emerald-600 flex items-center gap-1">
                    <i class="fa-solid fa-leaf"></i> Veg Items Only
                </div>
            </div>

            <!-- Food Items Grid (Optimized with pb-28 on mobile so floating bar never covers items) -->
            <div class="flex-1 overflow-y-auto p-2.5 sm:p-4 pb-28 lg:pb-4 smooth-scroll" id="foodsGrid">
                <!-- Food items dynamically rendered by JavaScript -->
            </div>
        </div>

        <!-- Mobile Cart Backdrop -->
        <div id="mobileCartBackdrop" onclick="toggleMobileCart()" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-30 hidden lg:hidden"></div>

        <!-- SECTION 3: Current Bill / Cart Drawer (Responsive on mobile) -->
        <div id="posCartPanel" class="fixed inset-y-0 right-0 w-full sm:w-[480px] lg:w-[490px] xl:w-[540px] z-40 bg-white border-l border-slate-200 flex flex-col shadow-2xl transform translate-x-full lg:translate-x-0 lg:static lg:shadow-lg transition-transform duration-300 ease-in-out">
            <!-- Cart Header -->
            <div class="p-3.5 border-b border-slate-200 bg-slate-50 flex items-center justify-between flex-shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-800">Current Order</h3>
                        <span id="cartOrderTypeBadge" class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-100 text-indigo-700">Table</span>
                        <span id="activeBillIdDisplay" class="text-xs font-mono font-bold text-orange-600"></span>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium mt-0.5" id="orderTargetDisplay">Table: None selected</p>
                </div>

                <div class="flex items-center gap-1.5">
                    <button type="button" onclick="clearCart()" title="Clear Current Cart" class="px-2.5 py-1 text-xs text-rose-600 hover:bg-rose-50 active:scale-95 rounded-lg font-bold transition flex items-center gap-1">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i> Clear
                    </button>
                    <button type="button" onclick="toggleMobileCart()" class="lg:hidden w-8 h-8 flex items-center justify-center text-slate-500 hover:text-slate-800 bg-slate-200/80 hover:bg-slate-300 active:scale-90 rounded-full transition" title="Close Cart">
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Cart Items List (Scrollable Table Area) -->
            <div class="flex-1 overflow-y-auto p-2 smooth-scroll" id="cartItemsList">
                <div id="emptyCartMessage" class="py-16 text-center text-slate-400">
                    <i class="fa-solid fa-cart-shopping text-3xl mb-2 text-slate-300 block"></i>
                    <p class="text-xs font-bold text-slate-600">Cart is Empty</p>
                    <p class="text-[11px] text-slate-400 mt-1">Select items from the menu to build the bill.</p>
                </div>
            </div>

            <!-- Cart Totals & Bill Summary (Bottom) -->
            <div class="p-3.5 sm:p-4 border-t border-slate-200 bg-slate-50 space-y-2 flex-shrink-0">
                <!-- Customer Chooser & Ledger Info -->
                <div class="space-y-1.5">
                    <input type="hidden" id="selectedCustomerId" value="{{ $activeBill?->customer_id ?? '' }}">

                    <!-- Selected Customer Pill (Active Customer with Balance) -->
                    <div id="selectedCustomerPill" class="{{ !empty($activeBill?->customer) ? '' : 'hidden' }} p-2 rounded-xl bg-orange-50 border border-orange-200 flex items-center justify-between text-xs transition">
                        <div class="overflow-hidden pr-1">
                            <div class="flex items-center gap-1.5 font-bold text-slate-900 truncate">
                                <i class="fa-solid fa-user-check text-orange-600 text-xs"></i>
                                <span id="pillCustomerName" class="truncate">{{ $activeBill?->customer?->name ?? 'Customer' }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-[10px] mt-0.5">
                                <span id="pillCustomerPhone" class="text-slate-500 font-mono">{{ $activeBill?->customer?->phone ?? '' }}</span>
                                <span id="pillCustomerBalanceBadge" class="font-black {{ ($activeBill?->customer?->balance ?? 0) > 0.001 ? 'text-rose-700' : 'text-emerald-700' }}">
                                    {{ $activeBill?->customer?->formatted_balance ?? 'Clear: ₹0.00' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" onclick="previewCustomerLedger()" class="px-2 py-1 bg-white hover:bg-slate-100 border border-slate-200 rounded-lg text-[10px] font-bold text-indigo-700 transition" title="Preview Customer Ledger Statement">
                                <i class="fa-solid fa-book"></i> Ledger
                            </button>
                            <button type="button" onclick="clearSelectedCustomer()" class="w-6 h-6 rounded-lg bg-white hover:bg-rose-50 border border-slate-200 text-slate-400 hover:text-rose-600 flex items-center justify-center text-xs transition" title="Clear / Switch Customer">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Customer Search & Inputs (Shown when no customer chosen) -->
                    <div id="customerSearchContainer" class="{{ !empty($activeBill?->customer) ? 'hidden' : '' }} space-y-1.5">
                        <div class="relative">
                            <div class="flex items-center gap-1">
                                <div class="relative flex-1">
                                    <input type="text" id="custSearchInput" placeholder="Search customer (name/phone)..." autocomplete="off" oninput="onCustomerSearchInput(this.value)" onfocus="onCustomerSearchInput(this.value)" class="w-full pl-7 pr-2 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 bg-white">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-2 text-slate-400 text-xs">
                                        <i class="fa-solid fa-address-book text-[11px]"></i>
                                    </span>
                                </div>
                                <button type="button" onclick="openPosQuickCustomerModal()" class="px-2.5 py-1.5 bg-orange-600 hover:bg-orange-700 active:scale-95 text-white rounded-lg text-xs font-bold shrink-0 transition flex items-center gap-1 shadow-xs" title="Add New Customer">
                                    <i class="fa-solid fa-user-plus text-[10px]"></i> +New
                                </button>
                            </div>
                            <!-- Autocomplete Dropdown -->
                            <div id="customerSearchResults" class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-2xl z-40 max-h-52 overflow-y-auto hidden divide-y divide-slate-100">
                                <!-- Dynamic search results -->
                            </div>
                        </div>

                        <!-- Manual Walk-in inputs (Auto-syncs or manual typing) -->
                        <div class="flex items-center gap-2 text-xs">
                            <input type="text" id="custName" value="{{ $activeBill?->customer_name ?? '' }}" placeholder="Name (walk-in guest)" class="w-1/2 px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 bg-white">
                            <input type="text" id="custPhone" value="{{ $activeBill?->customer_phone ?? '' }}" placeholder="Phone Number" class="w-1/2 px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 bg-white">
                        </div>
                    </div>
                </div>

                <!-- Discount Input -->
                <div class="flex items-center justify-between text-xs pt-0.5">
                    <div class="flex items-center gap-1.5">
                        <span class="text-slate-500 font-medium">Discount:</span>
                        <select id="discountType" onchange="triggerCalculation()" class="border border-slate-300 rounded-md px-1.5 py-1 text-xs bg-white">
                            <option value="fixed">Fixed (₹)</option>
                            <option value="percentage">Percent (%)</option>
                        </select>
                        <input type="number" id="discountValue" value="0" min="0" oninput="triggerCalculation()" class="w-16 border border-slate-300 rounded-md px-1.5 py-1 text-xs text-right font-bold bg-white">
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
                        <h4 id="grandTotalDisplay" class="text-xl sm:text-2xl font-black text-white">₹0.00</h4>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-950 text-emerald-400 font-bold border border-emerald-800">Tax Verified</span>
                    </div>
                </div>

                <!-- Bottom POS Action Buttons with KOT -->
                <div class="grid grid-cols-5 gap-1.5 pt-1">
                    <button type="button" onclick="printKot()" id="btnKotPrint" class="py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold text-xs flex flex-col items-center justify-center gap-1 shadow-sm transition" title="Send to Kitchen Printer">
                        <i class="fa-solid fa-fire-burner"></i>
                        <span>KOT</span>
                    </button>

                    <button type="button" onclick="saveAsHeld()" class="py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold text-xs flex flex-col items-center justify-center gap-1 shadow-sm transition" title="Hold Order">
                        <i class="fa-solid fa-pause"></i>
                        <span>Hold</span>
                    </button>

                    <button type="button" onclick="saveAsDraft()" class="py-2.5 rounded-xl bg-slate-700 hover:bg-slate-600 active:scale-95 text-white font-bold text-xs flex flex-col items-center justify-center gap-1 shadow-sm transition" title="Save Draft">
                        <i class="fa-solid fa-file-pen"></i>
                        <span>Draft</span>
                    </button>

                    <button type="button" onclick="openPaymentModal()" class="col-span-2 py-2.5 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-500 hover:to-amber-500 active:scale-95 text-white font-extrabold text-xs sm:text-sm flex items-center justify-center gap-1.5 shadow-lg shadow-orange-600/30 transition">
                        <i class="fa-solid fa-credit-card"></i>
                        <span>Pay & Settle</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Sticky Bottom Floating Cart Bar -->
    <div id="mobileBottomBar" class="lg:hidden fixed bottom-0 inset-x-0 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 text-white px-3.5 py-2.5 flex items-center justify-between z-20 shadow-2xl">
        <div onclick="toggleMobileCart()" class="flex items-center gap-2.5 cursor-pointer flex-1 min-w-0 active:scale-98 transition">
            <div class="w-8 h-8 rounded-lg bg-orange-600 flex items-center justify-center text-white font-bold text-sm shadow-md shrink-0">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div class="truncate">
                <div class="flex items-center gap-1.5">
                    <span id="mobileBottomQty" class="text-xs font-bold leading-none text-slate-100">0 items</span>
                    <span class="text-slate-500">&bull;</span>
                    <span id="mobileBottomTotal" class="font-black text-xs text-amber-400">₹0.00</span>
                </div>
                <p class="text-[10px] text-slate-400 mt-0.5">Tap to review cart</p>
            </div>
        </div>
        <div class="flex items-center gap-1.5 shrink-0">
            <button type="button" onclick="printKot()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-lg text-xs font-bold flex items-center gap-1 shadow-sm transition" title="Print Kitchen Order Ticket">
                <i class="fa-solid fa-fire-burner"></i> KOT
            </button>
            <button type="button" onclick="toggleMobileCart()" class="px-2.5 py-1.5 bg-orange-600 hover:bg-orange-700 active:scale-95 text-white rounded-lg text-xs font-extrabold flex items-center gap-1 shadow-sm transition">
                Cart <i class="fa-solid fa-chevron-right text-[10px]"></i>
            </button>
        </div>
    </div>

    <!-- MODAL 1: Payment Checkout Modal (Responsive Bottom Sheet / Modal) -->
    <div id="paymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-2.5 sm:p-4">
        <div class="bg-white rounded-3xl sm:rounded-2xl shadow-2xl w-full max-w-lg max-h-[94vh] flex flex-col overflow-hidden">
            <div class="px-5 sm:px-6 py-3.5 sm:py-4 bg-slate-900 text-white flex items-center justify-between flex-shrink-0">
                <div>
                    <h3 class="text-sm sm:text-base font-bold">Process Bill Payment</h3>
                    <p class="text-xs text-orange-400" id="payModalInvoiceDisplay">New Bill Settlement</p>
                </div>
                <button type="button" onclick="closePaymentModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition active:scale-90"><i class="fa-solid fa-xmark text-base"></i></button>
            </div>

            <div class="p-4 sm:p-6 space-y-3.5 sm:space-y-4 overflow-y-auto flex-1 smooth-scroll">
                <!-- Amount Due Banner -->
                <div class="p-3.5 sm:p-4 bg-orange-50 border border-orange-200 rounded-2xl flex items-center justify-between">
                    <div>
                        <span class="text-[10px] sm:text-xs font-semibold text-orange-800 uppercase tracking-wider">Total Payable</span>
                        <h3 id="payDueAmountDisplay" class="text-2xl sm:text-3xl font-black text-orange-950">₹0.00</h3>
                    </div>
                    <div class="text-right text-xs text-orange-700 font-medium">
                        <span id="payDueItemsCount">0 items</span>
                    </div>
                </div>

                <!-- Payment Method Tabs -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Select Payment Method</label>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5 sm:gap-2">
                        <button type="button" onclick="selectPayMethod('cash')" id="payTab_cash" class="pay-method-btn p-2 sm:p-2.5 rounded-xl border border-orange-600 bg-orange-50 text-orange-800 font-bold text-xs flex flex-col items-center gap-1 transition active:scale-95 touch-action-manipulation">
                            <i class="fa-solid fa-money-bill-wave text-base"></i>
                            <span>Cash</span>
                        </button>
                        <button type="button" onclick="selectPayMethod('upi')" id="payTab_upi" class="pay-method-btn p-2 sm:p-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs flex flex-col items-center gap-1 transition active:scale-95 touch-action-manipulation">
                            <i class="fa-solid fa-qrcode text-base"></i>
                            <span>UPI / QR</span>
                        </button>
                        <button type="button" onclick="selectPayMethod('card')" id="payTab_card" class="pay-method-btn p-2 sm:p-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs flex flex-col items-center gap-1 transition active:scale-95 touch-action-manipulation">
                            <i class="fa-solid fa-credit-card text-base"></i>
                            <span>Card</span>
                        </button>
                        <button type="button" onclick="selectPayMethod('credit')" id="payTab_credit" class="pay-method-btn p-2 sm:p-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs flex flex-col items-center gap-1 transition active:scale-95 touch-action-manipulation">
                            <i class="fa-solid fa-book-bookmark text-base text-rose-600"></i>
                            <span>Credit (Due)</span>
                        </button>
                        <button type="button" onclick="selectPayMethod('multimode')" id="payTab_multimode" class="pay-method-btn p-2 sm:p-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs flex flex-col items-center gap-1 transition active:scale-95 touch-action-manipulation">
                            <i class="fa-solid fa-arrows-split-up-and-left text-base text-purple-600"></i>
                            <span>Split Pay</span>
                        </button>
                    </div>
                </div>

                <!-- Credit (Customer Ledger) Details Area -->
                <div id="creditDetailsArea" class="hidden space-y-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between text-xs font-bold pb-2 border-b border-slate-200">
                        <span class="text-slate-700 uppercase flex items-center gap-1.5">
                            <i class="fa-solid fa-address-book text-orange-600"></i> Customer Ledger Account
                        </span>
                        <span id="creditCustomerStatusBadge" class="text-xs font-black text-rose-600">Due: ₹0.00</span>
                    </div>

                    <!-- If customer is selected -->
                    <div id="creditSelectedCustomerBox" class="space-y-2">
                        <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-1.5 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Customer Name:</span>
                                <span id="creditCustomerNameDisplay" class="font-bold text-slate-900">-</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Previous Balance:</span>
                                <span id="creditPreviousBalanceDisplay" class="font-mono font-bold text-slate-800">Clear: ₹0.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-500">Bill Amount to Add:</span>
                                <span id="creditBillAmountDisplay" class="font-mono font-bold text-rose-600">+₹0.00</span>
                            </div>
                            <div class="pt-1.5 border-t border-slate-100 flex justify-between items-center font-bold">
                                <span class="text-slate-800">New Projected Balance:</span>
                                <span id="creditNewBalanceDisplay" class="font-mono text-sm font-black text-rose-700">Due: ₹0.00</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 uppercase mb-1">Credit Reference / Note (Optional)</label>
                            <input type="text" id="creditRefInput" placeholder="Optional PO / approval reference note" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 bg-white">
                        </div>
                    </div>

                    <!-- If NO customer is selected -->
                    <div id="creditNoCustomerBox" class="hidden p-4 bg-amber-50 border border-amber-200 rounded-xl text-center space-y-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500 text-2xl"></i>
                        <p class="text-xs font-bold text-amber-900">Customer Required for Credit Billing</p>
                        <p class="text-[11px] text-amber-700">To maintain credit in customer ledger, please select an existing customer or register a new one.</p>
                        <div class="flex items-center justify-center gap-2 pt-1">
                            <button type="button" onclick="closePaymentModal(); focusCustomerSearch();" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-bold transition">
                                <i class="fa-solid fa-magnifying-glass mr-1"></i> Choose Customer
                            </button>
                            <button type="button" onclick="openPosQuickCustomerModal();" class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold transition">
                                <i class="fa-solid fa-user-plus mr-1"></i> + New Customer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Multimode (Split Pay) Area -->
                <div id="multimodeDetailsArea" class="hidden space-y-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between text-xs font-bold pb-2 border-b border-slate-200">
                        <span class="text-slate-700 uppercase">Split Tender (Cash + UPI + Credit)</span>
                        <span id="multiDueBadge" class="text-orange-600 font-mono">Due: ₹0.00</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase mb-1">Cash Portion (₹)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-2 font-bold text-slate-400 text-xs">₹</span>
                                <input type="number" step="0.01" min="0" id="multiCashInput" oninput="calculateMultiModeSplit()" placeholder="0.00" class="w-full pl-5 pr-2 py-1.5 rounded-xl border border-slate-300 font-black text-sm text-slate-900 focus:ring-2 focus:ring-orange-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase mb-1">UPI Portion (₹)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-2 font-bold text-slate-400 text-xs">₹</span>
                                <input type="number" step="0.01" min="0" id="multiUpiInput" oninput="calculateMultiModeSplit()" placeholder="0.00" class="w-full pl-5 pr-2 py-1.5 rounded-xl border border-slate-300 font-black text-sm text-slate-900 focus:ring-2 focus:ring-orange-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-700 uppercase mb-1">Credit / Due (₹)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-2 font-bold text-slate-400 text-xs">₹</span>
                                <input type="number" step="0.01" min="0" id="multiCreditInput" oninput="calculateMultiModeSplit()" placeholder="0.00" class="w-full pl-5 pr-2 py-1.5 rounded-xl border border-slate-300 font-black text-sm text-rose-700 focus:ring-2 focus:ring-orange-500">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-1.5 pt-0.5">
                        <button type="button" onclick="autoFillMultiCash()" class="flex-1 py-1 px-1.5 bg-white border border-slate-200 hover:bg-slate-100 active:scale-95 rounded-lg text-[10px] font-bold text-slate-700 transition text-center shadow-xs">
                            Bal to Cash
                        </button>
                        <button type="button" onclick="autoFillMultiUpi()" class="flex-1 py-1 px-1.5 bg-white border border-slate-200 hover:bg-slate-100 active:scale-95 rounded-lg text-[10px] font-bold text-slate-700 transition text-center shadow-xs">
                            Bal to UPI
                        </button>
                        <button type="button" onclick="autoFillMultiCredit()" class="flex-1 py-1 px-1.5 bg-white border border-slate-200 hover:bg-slate-100 active:scale-95 rounded-lg text-[10px] font-bold text-rose-700 transition text-center shadow-xs">
                            Bal to Credit
                        </button>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-0.5">UPI Reference / Note</label>
                        <input type="text" id="multiUpiRefInput" placeholder="Optional transaction note / reference" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                    </div>

                    <div id="multiStatusBox" class="p-2 rounded-xl text-xs font-bold flex items-center justify-between bg-amber-50 text-amber-800 border border-amber-200">
                        <span>Total Entered: <span id="multiEnteredSum">₹0.00</span></span>
                        <span id="multiBalanceStatus">Balance: ₹0.00</span>
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
                    <div class="flex items-center flex-wrap gap-1.5 sm:gap-2 mt-2">
                        <button type="button" onclick="setTenderedExact()" class="flex-1 py-1.5 px-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-800 rounded-lg text-xs font-bold transition text-center">Exact</button>
                        <button type="button" onclick="setTendered(100)" class="flex-1 py-1.5 px-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-800 rounded-lg text-xs font-bold transition text-center">₹100</button>
                        <button type="button" onclick="setTendered(200)" class="flex-1 py-1.5 px-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-800 rounded-lg text-xs font-bold transition text-center">₹200</button>
                        <button type="button" onclick="setTendered(500)" class="flex-1 py-1.5 px-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-800 rounded-lg text-xs font-bold transition text-center">₹500</button>
                        <button type="button" onclick="setTendered(2000)" class="flex-1 py-1.5 px-2 bg-slate-100 hover:bg-slate-200 active:scale-95 text-slate-800 rounded-lg text-xs font-bold transition text-center">₹2000</button>
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
            </div>

            <!-- Modal Footer (Sticky) -->
            <div class="p-3.5 sm:p-5 border-t border-slate-200 flex items-center justify-between flex-shrink-0 bg-slate-50">
                <button type="button" onclick="closePaymentModal()" class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 active:scale-95 transition">Cancel</button>
                
                <button type="button" id="btnSubmitPayment" onclick="submitFinalPayment()" class="px-5 sm:px-6 py-2.5 sm:py-3 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl text-xs sm:text-sm font-extrabold shadow-lg shadow-emerald-600/30 transition flex items-center gap-2">
                    <i class="fa-solid fa-check-double"></i>
                    <span>Complete & Print Bill</span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: POS Quick Add Customer -->
    <div id="posQuickCustomerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl sm:rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col max-h-[92vh]">
            <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-orange-500"></i> Quick Add Customer
                </h3>
                <button type="button" onclick="closePosQuickCustomerModal()" class="w-7 h-7 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="posQuickCustomerForm" onsubmit="submitPosQuickCustomer(event)" class="p-5 space-y-3.5 overflow-y-auto flex-1 text-xs">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Customer Name *</label>
                    <input type="text" id="quickCustName" required placeholder="Customer / Business Name" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Phone Number</label>
                        <input type="text" id="quickCustPhone" placeholder="Mobile / Phone" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 uppercase mb-1">Credit Limit (₹)</label>
                        <input type="number" step="0.01" min="0" id="quickCustLimit" value="0.00" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Address (Optional)</label>
                    <input type="text" id="quickCustAddress" placeholder="Customer address / locality" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
                <!-- Opening Balance -->
                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                    <span class="block font-bold text-slate-700 uppercase text-[10px]">Opening Balance (Optional)</span>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" step="0.01" min="0" id="quickCustOpenBal" value="0.00" placeholder="0.00" class="px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                        <select id="quickCustOpenBalType" class="px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                            <option value="due">Customer Owes (Due)</option>
                            <option value="advance">Customer Paid (Advance)</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closePosQuickCustomerModal()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold transition">Cancel</button>
                    <button type="submit" id="btnSaveQuickCust" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-bold transition shadow-sm">Save & Select</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: POS Quick Customer Ledger Preview -->
    <div id="customerLedgerPreviewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl sm:rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[92vh]">
            <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
                <div>
                    <h3 class="font-bold text-sm flex items-center gap-2">
                        <i class="fa-solid fa-book-bookmark text-orange-400"></i> <span id="previewLedgerCustName">Customer Ledger</span>
                    </h3>
                    <p class="text-[11px] text-slate-400" id="previewLedgerCustPhone">-</p>
                </div>
                <button type="button" onclick="closeCustomerLedgerPreview()" class="w-7 h-7 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-4 space-y-3 overflow-y-auto flex-1 text-xs">
                <!-- Balance Box -->
                <div class="p-3 rounded-xl flex items-center justify-between bg-slate-100">
                    <span class="font-bold uppercase text-[10px] text-slate-600">Current Outstanding Balance</span>
                    <span id="previewLedgerCurrentBalance" class="text-base font-black font-mono">₹0.00</span>
                </div>
                <!-- Mini Statement Info -->
                <div class="flex items-center justify-between">
                    <span class="font-bold text-slate-700 uppercase text-[10px]">Recent Ledger Entries</span>
                    <a id="previewLedgerFullLink" href="#" target="_blank" class="text-[11px] font-bold text-indigo-600 hover:underline">
                        Open Full Statement <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                    </a>
                </div>
                <div id="previewLedgerEntriesContainer" class="border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100 max-h-60 overflow-y-auto">
                    <div class="p-4 text-center text-slate-400">Loading ledger...</div>
                </div>
            </div>
            <div class="p-3 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button type="button" onclick="closeCustomerLedgerPreview()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl text-xs font-bold transition">Close</button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Add-ons Selector Popover Modal -->
    <div id="addonsModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl sm:rounded-2xl shadow-xl w-full max-w-sm max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-5 py-3.5 bg-slate-900 text-white flex items-center justify-between flex-shrink-0">
                <h4 class="text-xs font-bold" id="addonsModalItemTitle">Select Add-ons</h4>
                <button type="button" onclick="closeAddonsModal()" class="w-7 h-7 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white transition active:scale-90"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-4 space-y-2 overflow-y-auto flex-1 smooth-scroll" id="addonsListContainer">
                @foreach($addons as $addon)
                <label class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs active:bg-slate-100 transition">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" value="{{ $addon->id }}" data-price="{{ $addon->price }}" data-name="{{ $addon->name }}" class="addon-checkbox rounded border-slate-300 text-orange-600 focus:ring-orange-500 w-4 h-4">
                        <span class="font-bold text-slate-800">{{ $addon->name }}</span>
                    </div>
                    <span class="font-mono text-slate-600 font-bold">+₹{{ number_format($addon->price, 2) }}</span>
                </label>
                @endforeach
            </div>
            <div class="p-3 border-t border-slate-100 flex justify-end flex-shrink-0 bg-slate-50">
                <button type="button" onclick="saveAddonsForCurrentItem()" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-sm transition">Apply Add-ons</button>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Held Bills Modal -->
    <div id="heldBillsModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl sm:rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-5 sm:px-6 py-4 bg-amber-900 text-white flex items-center justify-between flex-shrink-0">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <i class="fa-solid fa-pause"></i>
                    <span>Held Bills Queue</span>
                </h3>
                <button type="button" onclick="closeHeldBillsModal()" class="w-8 h-8 rounded-full bg-amber-950 flex items-center justify-center text-amber-200 hover:text-white transition active:scale-90"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 smooth-scroll" id="heldBillsList">
                <div class="text-center py-8 text-slate-400 text-xs">Loading held bills...</div>
            </div>
        </div>
    </div>

    <!-- MODAL 4: Draft Bills Modal -->
    <div id="draftBillsModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl sm:rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-5 sm:px-6 py-4 bg-slate-900 text-white flex items-center justify-between flex-shrink-0">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <i class="fa-solid fa-file-pen"></i>
                    <span>Saved Draft Orders</span>
                </h3>
                <button type="button" onclick="closeDraftsModal()" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white transition active:scale-90"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 smooth-scroll" id="draftBillsList">
                <div class="text-center py-8 text-slate-400 text-xs">Loading drafts...</div>
            </div>
        </div>
    </div>

    <!-- MODAL 5: Cancel Bill Confirmation Modal -->
    <div id="cancelModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
        <div class="bg-white rounded-3xl sm:rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-5 sm:px-6 py-4 bg-rose-900 text-white flex items-center justify-between flex-shrink-0">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Cancel Active Bill</span>
                </h3>
                <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')" class="w-8 h-8 rounded-full bg-rose-950 flex items-center justify-center text-rose-200 hover:text-white transition active:scale-90"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 smooth-scroll">
                <p class="text-xs text-slate-600">Are you sure you want to cancel this bill? This will restore any deducted inventory and release the dining table.</p>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Cancellation Reason *</label>
                    <textarea id="cancelReasonInput" rows="3" required placeholder="Reason for cancellation..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-1 focus:ring-rose-500"></textarea>
                </div>
                <div class="pt-2 flex items-center justify-end gap-2">
                    <button type="button" onclick="document.getElementById('cancelModal').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 active:scale-95">No, Keep Bill</button>
                    <button type="button" onclick="confirmCancelBill()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 active:scale-95 text-white rounded-xl text-xs font-bold transition">Yes, Cancel Bill</button>
                </div>
            </div>
        </div>
    </div>

    @php
        $initialCustomerData = null;
        if (! empty($activeBill?->customer)) {
            $cust = $activeBill->customer;
            $custBal = (float) $cust->balance;
            $initialCustomerData = [
                'id' => $cust->id,
                'name' => $cust->name,
                'phone' => $cust->phone ?: '',
                'email' => $cust->email ?: '',
                'balance' => $custBal,
                'credit_limit' => (float) $cust->credit_limit,
                'is_due' => $custBal > 0.001,
                'is_advance' => $custBal < -0.001,
                'formatted_balance' => $cust->formatted_balance,
            ];
        }
    @endphp

    <!-- Script Application Logic -->
    <script>
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const CURRENCY = "{{ $currency }}";
        const TAX_TYPE = "{{ $taxType }}";
        const AVAILABLE_ADDONS = @json($addons);
        const INITIAL_ACTIVE_BILL = @json($activeBill);
        const KITCHEN_PRINTERS = @json($kitchenPrinters ?? []);
        const COUNTER_PRINTERS = @json($counterPrinters ?? []);

        // State
        let orderType = 'table'; // 'table' or 'counter'
        let currentTableId = null;
        let currentWaiterId = null;
        let currentWaiterName = null;
        let activeBillId = null;
        let activeInvoiceNumber = null;
        let cart = []; // [ { id, item_type: 'food'|'combo', item_id, code, name, unit_price, quantity, notes, addons: [] } ]
        let calculationState = null;
        let selectedCategory = 'all';
        let vegFilterOnly = false;
        let currentActiveItemCartIndex = null;
        let selectedPaymentMethod = 'cash';
        let selectedCustomerState = @json($activeCustomerPayload ?? $initialCustomerData);
        const LOGGED_USER_IS_ADMIN = {{ $isAdmin ? 'true' : 'false' }};
        const LOGGED_STAFF_ID = {{ (!$isAdmin && $currentUser) ? $currentUser->id : 'null' }};
        const LOGGED_STAFF_NAME = "{{ (!$isAdmin && $currentUser) ? addslashes($currentUser->name) : '' }}";

        // SweetAlert2 Toast & Alert Configuration
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            fetchFoods();

            // Initialize waiter if selected in dropdown or auto-select staff
            const waiterSel = document.getElementById('selectedWaiterId');
            if (waiterSel && waiterSel.value) {
                currentWaiterId = parseInt(waiterSel.value);
                currentWaiterName = waiterSel.options[waiterSel.selectedIndex].getAttribute('data-name') || waiterSel.options[waiterSel.selectedIndex].text.trim();
            } else if (LOGGED_STAFF_ID) {
                currentWaiterId = LOGGED_STAFF_ID;
                currentWaiterName = LOGGED_STAFF_NAME;
                if (waiterSel) waiterSel.value = LOGGED_STAFF_ID;
            }

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

        // 1. ORDER TYPE, TABLE & WAITER LOGIC
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

        function onWaiterSelected() {
            const sel = document.getElementById('selectedWaiterId');
            if (sel && sel.selectedIndex >= 0 && sel.value) {
                currentWaiterId = parseInt(sel.value);
                currentWaiterName = sel.options[sel.selectedIndex].getAttribute('data-name') || sel.options[sel.selectedIndex].text.trim();
            } else {
                currentWaiterId = null;
                currentWaiterName = null;
            }
        }

        function toggleMobileCart() {
            const panel = document.getElementById('posCartPanel');
            const backdrop = document.getElementById('mobileCartBackdrop');
            if (!panel || !backdrop) return;
            const isOpen = !panel.classList.contains('translate-x-full');
            if (isOpen) {
                panel.classList.add('translate-x-full');
                backdrop.classList.add('hidden');
            } else {
                panel.classList.remove('translate-x-full');
                backdrop.classList.remove('hidden');
            }
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

        // 1B. CUSTOMER & LEDGER MANAGEMENT LOGIC
        let customerSearchDebounce = null;

        function setSelectedCustomer(customer) {
            if (!customer) {
                clearSelectedCustomer();
                return;
            }

            selectedCustomerState = customer;
            const hiddenId = document.getElementById('selectedCustomerId');
            if (hiddenId) hiddenId.value = customer.id;

            const nameInput = document.getElementById('custName');
            if (nameInput) nameInput.value = customer.name || '';
            const phoneInput = document.getElementById('custPhone');
            if (phoneInput) phoneInput.value = customer.phone || '';

            // Update Selected Customer Pill
            const pill = document.getElementById('selectedCustomerPill');
            const searchContainer = document.getElementById('customerSearchContainer');
            const pillName = document.getElementById('pillCustomerName');
            const pillPhone = document.getElementById('pillCustomerPhone');
            const pillBalBadge = document.getElementById('pillCustomerBalanceBadge');

            if (pillName) pillName.innerText = customer.name;
            if (pillPhone) pillPhone.innerText = customer.phone || 'No phone';

            const bal = parseFloat(customer.balance) || 0;
            if (pillBalBadge) {
                if (customer.formatted_balance) {
                    pillBalBadge.innerText = customer.formatted_balance;
                } else if (bal > 0.001) {
                    pillBalBadge.innerText = `Due: ${CURRENCY}${bal.toFixed(2)}`;
                } else if (bal < -0.001) {
                    pillBalBadge.innerText = `Adv: ${CURRENCY}${Math.abs(bal).toFixed(2)}`;
                } else {
                    pillBalBadge.innerText = 'Clear: ₹0.00';
                }
                pillBalBadge.className = `font-black ${bal > 0.001 ? 'text-rose-700' : (bal < -0.001 ? 'text-sky-700' : 'text-emerald-700')}`;
            }

            if (searchContainer) searchContainer.classList.add('hidden');
            if (pill) pill.classList.remove('hidden');

            const searchResults = document.getElementById('customerSearchResults');
            if (searchResults) searchResults.classList.add('hidden');

            if (selectedPaymentMethod === 'credit' || selectedPaymentMethod === 'multimode') {
                updateCreditPaymentPreview();
            }
        }

        function clearSelectedCustomer() {
            selectedCustomerState = null;
            const hiddenId = document.getElementById('selectedCustomerId');
            if (hiddenId) hiddenId.value = '';

            const searchInput = document.getElementById('custSearchInput');
            if (searchInput) searchInput.value = '';

            const pill = document.getElementById('selectedCustomerPill');
            const searchContainer = document.getElementById('customerSearchContainer');

            if (pill) pill.classList.add('hidden');
            if (searchContainer) searchContainer.classList.remove('hidden');

            const searchResults = document.getElementById('customerSearchResults');
            if (searchResults) {
                searchResults.innerHTML = '';
                searchResults.classList.add('hidden');
            }

            if (selectedPaymentMethod === 'credit' || selectedPaymentMethod === 'multimode') {
                updateCreditPaymentPreview();
            }
        }

        function focusCustomerSearch() {
            clearSelectedCustomer();
            const input = document.getElementById('custSearchInput');
            if (input) {
                input.focus();
            }
        }

        async function onCustomerSearchInput(val) {
            clearTimeout(customerSearchDebounce);
            const dropdown = document.getElementById('customerSearchResults');
            if (!dropdown) return;

            const q = (val || '').trim();
            if (q.length === 0) {
                dropdown.innerHTML = '';
                dropdown.classList.add('hidden');
                return;
            }

            customerSearchDebounce = setTimeout(async () => {
                try {
                    const res = await fetch(`{{ route('pos.customers.search') }}?query=${encodeURIComponent(q)}`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await res.json();
                    if (!data.customers || data.customers.length === 0) {
                        dropdown.innerHTML = `
                            <div class="p-3 text-center text-xs text-slate-500">
                                <div>No customer found for "<span class="font-bold text-slate-700">${q}</span>"</div>
                                <button type="button" onclick="openPosQuickCustomerModal('${q.replace(/'/g, "\\'")}')" class="mt-2 px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-700 font-bold rounded-lg transition inline-flex items-center gap-1">
                                    <i class="fa-solid fa-user-plus text-[10px]"></i> Register "${q}"
                                </button>
                            </div>
                        `;
                        dropdown.classList.remove('hidden');
                        return;
                    }

                    let html = '';
                    data.customers.forEach(c => {
                        const bal = parseFloat(c.balance) || 0;
                        const balColor = bal > 0.001 ? 'text-rose-600' : (bal < -0.001 ? 'text-sky-600' : 'text-emerald-600');
                        const balLabel = c.formatted_balance || (bal > 0.001 ? `Due: ${CURRENCY}${bal.toFixed(2)}` : (bal < -0.001 ? `Adv: ${CURRENCY}${Math.abs(bal).toFixed(2)}` : 'Clear: ₹0.00'));
                        const escapedJson = JSON.stringify(c).replace(/"/g, '&quot;');

                        html += `
                            <div onclick="selectCustomerFromSearch(${escapedJson})" class="p-2.5 hover:bg-orange-50 cursor-pointer flex items-center justify-between transition text-xs">
                                <div>
                                    <div class="font-bold text-slate-900">${c.name}</div>
                                    <div class="text-[10px] text-slate-500 font-mono">${c.phone || 'No phone'}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold font-mono text-xs ${balColor}">${balLabel}</div>
                                    ${c.credit_limit > 0 ? `<div class="text-[9px] text-slate-400">Limit: ${CURRENCY}${parseFloat(c.credit_limit).toFixed(0)}</div>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    dropdown.innerHTML = html;
                    dropdown.classList.remove('hidden');
                } catch (err) {
                    console.error('Customer search error:', err);
                }
            }, 250);
        }

        function selectCustomerFromSearch(customer) {
            setSelectedCustomer(customer);
        }

        // Close customer search dropdown on outside click
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('customerSearchResults');
            const searchInput = document.getElementById('custSearchInput');
            if (dropdown && !dropdown.classList.contains('hidden')) {
                if (!dropdown.contains(e.target) && e.target !== searchInput) {
                    dropdown.classList.add('hidden');
                }
            }
        });

        function openPosQuickCustomerModal(presetName = '') {
            const modal = document.getElementById('posQuickCustomerModal');
            if (!modal) return;
            const nameInput = document.getElementById('quickCustName');
            const searchInput = document.getElementById('custSearchInput');
            const formCustName = document.getElementById('custName');

            if (nameInput) {
                nameInput.value = presetName || (searchInput ? searchInput.value : '') || (formCustName ? formCustName.value : '');
            }
            const phoneInput = document.getElementById('quickCustPhone');
            const formCustPhone = document.getElementById('custPhone');
            if (phoneInput && !phoneInput.value && formCustPhone) {
                phoneInput.value = formCustPhone.value || '';
            }

            modal.classList.remove('hidden');
            setTimeout(() => nameInput?.focus(), 50);
        }

        function closePosQuickCustomerModal() {
            const modal = document.getElementById('posQuickCustomerModal');
            if (modal) modal.classList.add('hidden');
        }

        async function submitPosQuickCustomer(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSaveQuickCust');
            btn.disabled = true;
            btn.innerText = 'Saving...';

            const payload = {
                name: document.getElementById('quickCustName')?.value?.trim(),
                phone: document.getElementById('quickCustPhone')?.value?.trim() || null,
                address: document.getElementById('quickCustAddress')?.value?.trim() || null,
                credit_limit: parseFloat(document.getElementById('quickCustLimit')?.value) || 0,
                opening_balance: parseFloat(document.getElementById('quickCustOpenBal')?.value) || 0,
                opening_balance_type: document.getElementById('quickCustOpenBalType')?.value || 'due'
            };

            try {
                const res = await fetch(`{{ route('pos.customers.quick-store') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success && data.customer) {
                    Toast.fire({ icon: 'success', title: data.message || 'Customer registered & selected.' });
                    setSelectedCustomer(data.customer);
                    closePosQuickCustomerModal();
                    document.getElementById('posQuickCustomerForm')?.reset();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Failed to register customer.')
                    });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Failed to create customer.' });
            } finally {
                btn.disabled = false;
                btn.innerText = 'Save & Select';
            }
        }

        async function previewCustomerLedger() {
            if (!selectedCustomerState || !selectedCustomerState.id) {
                Toast.fire({ icon: 'warning', title: 'No customer selected.' });
                return;
            }

            const modal = document.getElementById('customerLedgerPreviewModal');
            const nameEl = document.getElementById('previewLedgerCustName');
            const phoneEl = document.getElementById('previewLedgerCustPhone');
            const balEl = document.getElementById('previewLedgerCurrentBalance');
            const linkEl = document.getElementById('previewLedgerFullLink');
            const container = document.getElementById('previewLedgerEntriesContainer');

            if (nameEl) nameEl.innerText = `${selectedCustomerState.name}'s Ledger`;
            if (phoneEl) phoneEl.innerText = selectedCustomerState.phone || 'No phone recorded';
            if (linkEl) linkEl.href = `{{ url('customers') }}/${selectedCustomerState.id}/ledger`;

            const curBal = parseFloat(selectedCustomerState.balance) || 0;
            if (balEl) {
                balEl.innerText = selectedCustomerState.formatted_balance || (curBal > 0.001 ? `Due: ${CURRENCY}${curBal.toFixed(2)}` : (curBal < -0.001 ? `Adv: ${CURRENCY}${Math.abs(curBal).toFixed(2)}` : 'Clear: ₹0.00'));
                balEl.className = `text-base font-black font-mono ${curBal > 0.001 ? 'text-rose-600' : (curBal < -0.001 ? 'text-sky-600' : 'text-emerald-600')}`;
            }

            container.innerHTML = '<div class="p-4 text-center text-xs text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Loading ledger transactions...</div>';
            modal.classList.remove('hidden');

            try {
                const res = await fetch(`{{ url('pos/customers') }}/${selectedCustomerState.id}/ledger-json`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success && data.entries) {
                    if (data.entries.length === 0) {
                        container.innerHTML = '<div class="p-4 text-center text-xs text-slate-500 font-semibold">No ledger entries recorded yet.</div>';
                        return;
                    }

                    let html = '';
                    data.entries.forEach(e => {
                        const isDebit = e.type === 'debit';
                        const typeBadge = isDebit
                            ? '<span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-rose-100 text-rose-700">Debit (+)</span>'
                            : '<span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-emerald-100 text-emerald-700">Credit (-)</span>';

                        html += `
                            <div class="p-2.5 flex items-center justify-between text-xs hover:bg-slate-50 transition">
                                <div class="space-y-0.5 pr-2">
                                    <div class="flex items-center gap-1.5">
                                        ${typeBadge}
                                        <span class="font-bold text-slate-800">${e.description || 'Transaction'}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 font-mono">${e.entry_date} ${e.payment_mode ? `• ${e.payment_mode}` : ''}</div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-mono font-black ${isDebit ? 'text-rose-600' : 'text-emerald-600'}">
                                        ${isDebit ? '+' : '-'}${CURRENCY}${parseFloat(e.amount).toFixed(2)}
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-mono">Bal: ${CURRENCY}${parseFloat(e.balance_after).toFixed(2)}</div>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="p-4 text-center text-xs text-rose-500">Failed to load ledger transactions.</div>';
                }
            } catch (err) {
                container.innerHTML = '<div class="p-4 text-center text-xs text-rose-500">Network error fetching ledger.</div>';
            }
        }

        function closeCustomerLedgerPreview() {
            const modal = document.getElementById('customerLedgerPreviewModal');
            if (modal) modal.classList.add('hidden');
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

            html += `<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2.5 sm:gap-3">`;

            allItems.forEach(item => {
                const isCombo = item.type === 'combo';
                const vegDot = isCombo ? '' : (item.is_veg 
                    ? '<span class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-200" title="Veg"></span>'
                    : '<span class="w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-rose-200" title="Non-Veg"></span>');

                html += `
                    <button type="button" onclick="addToCart('${item.type}', ${item.id}, '${escapeQuotes(item.code)}', '${escapeQuotes(item.name)}', ${item.price}, ${item.tax_rate || 5})"
                        class="bg-white p-2.5 sm:p-3 rounded-2xl border border-slate-200 hover:border-orange-500 hover:shadow-md transition text-left flex flex-col justify-between active:scale-95 touch-action-manipulation group shadow-xs">
                        
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

                        <div class="mt-2.5 sm:mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="font-black text-slate-900 text-xs sm:text-sm">${CURRENCY}${item.price.toFixed(2)}</span>
                            <span class="w-7 h-7 sm:w-6 sm:h-6 rounded-xl bg-orange-50 text-orange-600 group-hover:bg-orange-600 group-hover:text-white flex items-center justify-center text-xs font-bold transition shadow-xs">
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

            document.querySelectorAll('.mob-category-btn').forEach(btn => {
                btn.className = 'px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-100 transition shrink-0 border border-slate-200 mob-category-btn';
            });
            const activeMobBtn = document.getElementById(`mob_cat_${catId}`);
            if (activeMobBtn) {
                activeMobBtn.className = 'px-3 py-1.5 rounded-xl text-xs font-bold transition shrink-0 bg-orange-600 text-white shadow-sm mob-category-btn';
            }

            document.getElementById('currentCategoryTitle').innerText = activeBtn ? activeBtn.querySelector('span').innerText : (activeMobBtn ? activeMobBtn.innerText.trim() : 'All Items');
            fetchFoods();
        }

        function toggleVegFilter() {
            vegFilterOnly = !vegFilterOnly;
            const btn = document.getElementById('btnVegFilter');
            const ind = document.getElementById('vegIndicator');
            const mobBtn = document.getElementById('btnMobVegFilter');
            if (vegFilterOnly) {
                if (btn) btn.className = 'text-[10px] px-2 py-0.5 rounded-full bg-emerald-600 text-white font-bold';
                if (mobBtn) mobBtn.className = 'px-2.5 py-1.5 rounded-xl text-xs font-bold transition shrink-0 bg-emerald-600 text-white shadow-sm flex items-center gap-1';
                if (ind) ind.classList.remove('hidden');
            } else {
                if (btn) btn.className = 'text-[10px] px-2 py-0.5 rounded-full border border-slate-200 text-slate-600 font-bold hover:bg-slate-50';
                if (mobBtn) mobBtn.className = 'px-2.5 py-1.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-100 transition shrink-0 border border-slate-200 flex items-center gap-1';
                if (ind) ind.classList.add('hidden');
            }
            fetchFoods();
        }

        // 2.1 PRODUCT SEARCH DROPDOWN & INSTANT ADD TO CART
        let searchDropdownItems = [];
        let searchHighlightedIndex = -1;
        let searchDebounce = null;
        let isSearchDropdownOpen = false;

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function onSearchInput() {
            const input = document.getElementById('foodSearchInput');
            const query = input ? input.value.trim() : '';
            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) clearBtn.classList.toggle('hidden', query.length === 0);

            clearTimeout(searchDebounce);

            if (query.length === 0) {
                hideSearchDropdown();
                fetchFoods();
                return;
            }

            showSearchDropdownLoading(query);

            searchDebounce = setTimeout(() => {
                performSearch(query);
            }, 180);
        }

        async function performSearch(query) {
            if (!query) return;

            const params = new URLSearchParams({
                category_id: selectedCategory,
                query: query,
                global_search: '1',
                veg_only: vegFilterOnly ? '1' : '0'
            });

            try {
                const res = await fetch(`{{ route('pos.search') }}?${params.toString()}`);
                const data = await res.json();
                if (data.success) {
                    const allItems = [...(data.combos || []), ...(data.foods || [])];
                    searchDropdownItems = allItems;
                    searchHighlightedIndex = -1;
                    renderSearchDropdown(allItems, query);

                    // Sync background food grid with filtered results
                    renderFoodsGrid(data.foods, data.combos);
                }
            } catch (err) {
                console.error("Search fetch error:", err);
            }
        }

        function showSearchDropdownLoading(query) {
            const dropdown = document.getElementById('searchDropdown');
            const list = document.getElementById('searchDropdownList');
            const countBadge = document.getElementById('searchDropdownCount');
            if (!dropdown || !list) return;

            dropdown.classList.remove('hidden');
            isSearchDropdownOpen = true;
            if (countBadge) countBadge.innerText = 'Searching menu...';

            list.innerHTML = `
                <div class="px-4 py-8 text-center text-slate-400">
                    <i class="fa-solid fa-circle-notch fa-spin text-orange-500 text-xl mb-2 block"></i>
                    <p class="text-xs font-semibold text-slate-600">Searching products for "<span class="text-orange-600 font-bold">${escapeHtml(query)}</span>"...</p>
                </div>
            `;
        }

        function renderSearchDropdown(items, query) {
            const dropdown = document.getElementById('searchDropdown');
            const list = document.getElementById('searchDropdownList');
            const countBadge = document.getElementById('searchDropdownCount');
            if (!dropdown || !list) return;

            dropdown.classList.remove('hidden');
            isSearchDropdownOpen = true;

            if (items.length === 0) {
                if (countBadge) countBadge.innerText = '0 products found';
                list.innerHTML = `
                    <div class="px-4 py-8 text-center text-slate-400">
                        <i class="fa-solid fa-bowl-food text-2xl mb-2 text-slate-300 block"></i>
                        <p class="text-xs font-semibold text-slate-700">No products found matching "<span class="text-orange-600 font-bold">${escapeHtml(query)}</span>"</p>
                        <p class="text-[11px] text-slate-400 mt-1">Try searching by barcode, food code, or dish name</p>
                    </div>
                `;
                hideSearchProductPreview();
                return;
            }

            if (countBadge) {
                countBadge.innerHTML = `<span class="text-orange-600 font-black">${items.length}</span> ${items.length === 1 ? 'product' : 'products'} found`;
            }

            let html = `
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px] sticky top-0 z-10 border-b border-slate-200">
                            <tr>
                                <th class="py-2.5 px-3 w-32">Barcode / Code</th>
                                <th class="py-2.5 px-3">Item Name</th>
                                <th class="py-2.5 px-2.5 text-center w-20">Stock</th>
                                <th class="py-2.5 px-3 text-right w-24">Sale Rate</th>
                                <th class="py-2.5 px-3 text-right w-24">MRP Rate</th>
                                <th class="py-2.5 px-2 text-center w-12">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
            `;

            items.forEach((item, index) => {
                const isCombo = item.type === 'combo';
                const vegDot = isCombo 
                    ? '<span class="px-1 py-0.2 rounded text-[9px] font-black bg-amber-100 text-amber-800 shrink-0">COMBO</span>'
                    : (item.is_veg 
                        ? '<span class="w-2 h-2 rounded-full bg-emerald-500 ring-2 ring-emerald-200 shrink-0" title="Veg"></span>'
                        : '<span class="w-2 h-2 rounded-full bg-rose-500 ring-2 ring-rose-200 shrink-0" title="Non-Veg"></span>');

                const barcodeText = escapeHtml(item.barcode || item.code);
                const stockVal = parseFloat(item.stock || 0);
                const stockBadge = stockVal > 0
                    ? `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">${stockVal}</span>`
                    : `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">0</span>`;

                html += `
                    <tr id="searchDropdownItem_${index}"
                        onclick="onSelectDropdownItem(${index})"
                        onmouseenter="highlightSearchItem(${index})"
                        class="search-dropdown-item hover:bg-orange-50 cursor-pointer transition select-none group border-l-4 border-transparent"
                        data-index="${index}">
                        <td class="py-2.5 px-3 font-mono font-bold text-slate-800">
                            <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-mono font-bold">${barcodeText}</span>
                        </td>
                        <td class="py-2.5 px-3">
                            <div class="flex items-center gap-1.5">
                                ${vegDot}
                                <span class="font-bold text-slate-800 text-xs sm:text-[13px] group-hover:text-orange-600 transition truncate max-w-[200px] sm:max-w-[260px] inline-block" title="${escapeHtml(item.name)}">
                                    ${escapeHtml(item.name)}
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-400 block">${escapeHtml(item.category || '')}</span>
                        </td>
                        <td class="py-2.5 px-2.5 text-center">
                            ${stockBadge}
                        </td>
                        <td class="py-2.5 px-3 text-right font-black text-slate-900 group-hover:text-orange-600 transition">
                            ${CURRENCY}${parseFloat(item.price).toFixed(2)}
                        </td>
                        <td class="py-2.5 px-3 text-right font-semibold text-slate-500 text-xs">
                            ${CURRENCY}${parseFloat(item.mrp || item.price).toFixed(2)}
                        </td>
                        <td class="py-2.5 px-2 text-center">
                            <div class="w-6 h-6 rounded-lg bg-orange-100 text-orange-600 group-hover:bg-orange-600 group-hover:text-white flex items-center justify-center text-xs font-bold transition mx-auto shadow-2xs">
                                <i class="fa-solid fa-plus"></i>
                            </div>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            list.innerHTML = html;

            // Automatically highlight first result and show linked popup
            if (items.length > 0) {
                highlightSearchItem(0);
            }
        }

        let activePreviewItemIndex = -1;

        function highlightSearchItem(index) {
            searchHighlightedIndex = index;
            document.querySelectorAll('.search-dropdown-item').forEach((el, idx) => {
                if (idx === index) {
                    el.classList.add('bg-orange-100/90', 'border-orange-500');
                    el.classList.remove('border-transparent');
                } else {
                    el.classList.remove('bg-orange-100/90', 'border-orange-500');
                    el.classList.add('border-transparent');
                }
            });
            showSearchProductPreview(index);
        }

        function updateHighlightedSearchItem() {
            document.querySelectorAll('.search-dropdown-item').forEach((el, idx) => {
                if (idx === searchHighlightedIndex) {
                    el.classList.add('bg-orange-100/90', 'border-orange-500');
                    el.classList.remove('border-transparent');
                    el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                } else {
                    el.classList.remove('bg-orange-100/90', 'border-orange-500');
                    el.classList.add('border-transparent');
                }
            });
            showSearchProductPreview(searchHighlightedIndex);
        }

        function showSearchProductPreview(index) {
            if (index < 0 || !searchDropdownItems || !searchDropdownItems[index]) {
                hideSearchProductPreview();
                return;
            }

            activePreviewItemIndex = index;
            const item = searchDropdownItems[index];
            const card = document.getElementById('searchProductLinkedPopup');
            const dropdown = document.getElementById('searchDropdown');
            const row = document.getElementById('searchDropdownItem_' + index);

            if (!card || !dropdown) return;

            // 1. Calculate values
            const isCombo = item.type === 'combo';
            const price = parseFloat(item.price || 0);
            const cost = parseFloat(item.cost_price || 0);
            const mrp = parseFloat(item.mrp || item.price || 0);
            const stock = parseFloat(item.stock || 0);
            const tax = parseFloat(item.tax_rate !== undefined ? item.tax_rate : 5);

            // Veg/Non-Veg/Combo Badge
            const typeBadge = document.getElementById('linkedPopupTypeBadge');
            if (typeBadge) {
                if (isCombo) {
                    typeBadge.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-400 text-amber-950 flex items-center gap-1"><i class="fa-solid fa-layer-group text-[10px]"></i> Combo Pack</span>`;
                } else if (item.is_veg) {
                    typeBadge.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-500 text-white flex items-center gap-1"><i class="fa-solid fa-leaf text-[10px]"></i> Pure Veg</span>`;
                } else {
                    typeBadge.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-rose-500 text-white flex items-center gap-1"><i class="fa-solid fa-drumstick-bite text-[10px]"></i> Non-Veg</span>`;
                }
            }

            // Category & Code
            const catBadge = document.getElementById('linkedPopupCategoryBadge');
            if (catBadge) catBadge.innerText = item.category || 'General';

            const codeBadge = document.getElementById('linkedPopupCode');
            if (codeBadge) codeBadge.innerText = item.code || '';

            // Name
            const nameEl = document.getElementById('linkedPopupName');
            if (nameEl) nameEl.innerText = item.name || '';

            // Barcode
            const barcodeText = document.getElementById('linkedPopupBarcodeText');
            if (barcodeText) barcodeText.innerText = item.barcode || item.code || '-';

            // HSN Code
            const hsnBadge = document.getElementById('linkedPopupHsnBadge');
            if (hsnBadge) {
                if (item.hsn_code) {
                    hsnBadge.innerText = `HSN: ${item.hsn_code}`;
                    hsnBadge.classList.remove('hidden');
                } else {
                    hsnBadge.classList.add('hidden');
                }
            }

            // GST %
            const gstBadge = document.getElementById('linkedPopupGstBadge');
            if (gstBadge) gstBadge.innerText = `${tax.toFixed(1)}% GST`;

            // Image Thumbnail / Food Icon
            const imgContainer = document.getElementById('linkedPopupImageContainer');
            if (imgContainer) {
                if (item.image) {
                    imgContainer.innerHTML = `<img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="w-full h-full object-cover">`;
                } else {
                    const iconClass = isCombo ? 'fa-layer-group text-amber-500' : (item.is_veg ? 'fa-leaf text-emerald-500' : 'fa-utensils text-orange-500');
                    imgContainer.innerHTML = `<div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100"><i class="fa-solid ${iconClass} text-2xl"></i></div>`;
                }
            }

            // Stock Metric Box & Badge
            const stockVal = document.getElementById('linkedPopupStockVal');
            const stockBadge = document.getElementById('linkedPopupStockBadge');
            const stockBox = document.getElementById('linkedPopupStockBox');
            if (stockVal) stockVal.innerText = isCombo ? 'Available' : stock.toFixed(stock % 1 === 0 ? 0 : 2);
            if (stockBadge && stockBox) {
                if (isCombo) {
                    stockBox.className = 'p-2.5 rounded-xl bg-amber-50/70 border border-amber-200 flex flex-col justify-between transition';
                    stockBadge.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-amber-100 text-amber-800">Combo Special</span>`;
                } else if (stock > 10) {
                    stockBox.className = 'p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex flex-col justify-between transition';
                    stockBadge.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-emerald-100 text-emerald-800"><i class="fa-solid fa-circle-check mr-1 text-[8px]"></i>In Stock</span>`;
                } else if (stock > 0) {
                    stockBox.className = 'p-2.5 rounded-xl bg-amber-50/70 border border-amber-200 flex flex-col justify-between transition';
                    stockBadge.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-amber-100 text-amber-800"><i class="fa-solid fa-triangle-exclamation mr-1 text-[8px]"></i>Low Stock</span>`;
                } else {
                    stockBox.className = 'p-2.5 rounded-xl bg-rose-50/70 border border-rose-200 flex flex-col justify-between transition';
                    stockBadge.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-rose-100 text-rose-800"><i class="fa-solid fa-circle-xmark mr-1 text-[8px]"></i>Out of Stock</span>`;
                }
            }

            // 4 Prices: MRP, Selling, Cost
            const mrpVal = document.getElementById('linkedPopupMrpVal');
            if (mrpVal) mrpVal.innerText = mrp.toFixed(2);

            const priceVal = document.getElementById('linkedPopupPriceVal');
            if (priceVal) priceVal.innerText = price.toFixed(2);

            const costVal = document.getElementById('linkedPopupCostVal');
            if (costVal) costVal.innerText = cost.toFixed(2);

            // Margin & Profit Calculation
            const profitVal = document.getElementById('linkedPopupProfitVal');
            const discountBadge = document.getElementById('linkedPopupDiscountBadge');
            const profitAmount = price - cost;
            const marginPct = price > 0 ? ((profitAmount / price) * 100) : 0;

            if (profitVal) {
                if (cost > 0) {
                    const sign = profitAmount >= 0 ? '+' : '';
                    profitVal.innerText = `${sign}${CURRENCY}${profitAmount.toFixed(2)} (${sign}${marginPct.toFixed(1)}% margin)`;
                    profitVal.className = profitAmount >= 0 ? 'text-xs font-black text-emerald-700' : 'text-xs font-black text-rose-700';
                } else {
                    profitVal.innerText = `Full Margin (Cost ${CURRENCY}0.00)`;
                    profitVal.className = 'text-xs font-black text-slate-600';
                }
            }

            if (discountBadge) {
                if (mrp > price) {
                    const disc = mrp - price;
                    const discPct = Math.round((disc / mrp) * 100);
                    discountBadge.innerHTML = `<span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-purple-100 text-purple-800">Save ${CURRENCY}${disc.toFixed(2)} (${discPct}% OFF)</span>`;
                } else {
                    discountBadge.innerHTML = `<span class="text-[10px] text-slate-400 font-semibold">Standard MRP</span>`;
                }
            }

            // Description / Kitchen Notes
            const descWrapper = document.getElementById('linkedPopupDescWrapper');
            const descText = document.getElementById('linkedPopupDescText');
            if (descWrapper && descText) {
                if (item.description && item.description.trim().length > 0) {
                    descText.innerText = item.description;
                    descWrapper.classList.remove('hidden');
                } else {
                    descWrapper.classList.add('hidden');
                }
            }

            // 2. Intelligent Dynamic Positioning relative to highlighted row
            const wasHidden = card.classList.contains('hidden');
            card.classList.remove('hidden');
            if (wasHidden) {
                card.classList.remove('linked-popup-animate');
                void card.offsetWidth;
                card.classList.add('linked-popup-animate');
            }
            positionTitleCard(card, dropdown, row);
        }

        function positionTitleCard(card, dropdown, row) {
            const dropdownRect = dropdown.getBoundingClientRect();
            const cardWidth = card.offsetWidth || 350;
            const cardHeight = card.offsetHeight || 370;
            const windowWidth = window.innerWidth;
            const windowHeight = window.innerHeight;

            // Mobile screens (<768px): Dock as floating title card at the bottom
            if (windowWidth < 768) {
                card.style.position = 'fixed';
                card.style.left = '10px';
                card.style.right = '10px';
                card.style.width = 'auto';
                card.style.bottom = '10px';
                card.style.top = 'auto';
                return;
            }

            // Desktop / Tablet screens: Anchor adjacent to dropdown & track row
            card.style.position = 'fixed';
            card.style.right = 'auto';
            card.style.bottom = 'auto';
            card.style.width = '350px';

            const rowRect = row ? row.getBoundingClientRect() : dropdownRect;
            const spaceRight = windowWidth - dropdownRect.right;
            const spaceLeft = dropdownRect.left;

            if (spaceRight >= (cardWidth + 14)) {
                // Position to the right of the search dropdown
                card.style.left = `${dropdownRect.right + 10}px`;
            } else if (spaceLeft >= (cardWidth + 14)) {
                // Position to the left of the search dropdown
                card.style.left = `${dropdownRect.left - cardWidth - 10}px`;
            } else {
                // Fallback to right margin of viewport
                card.style.left = `${Math.max(10, windowWidth - cardWidth - 14)}px`;
            }

            // Vertical position smoothly tracks the highlighted row
            let targetTop = row ? (rowRect.top - 12) : dropdownRect.top;
            if (targetTop + cardHeight > windowHeight - 14) {
                targetTop = windowHeight - cardHeight - 14;
            }
            if (targetTop < 14) {
                targetTop = 14;
            }

            card.style.top = `${targetTop}px`;
        }

        function hideSearchProductPreview() {
            const card = document.getElementById('searchProductLinkedPopup');
            if (card) card.classList.add('hidden');
            activePreviewItemIndex = -1;
        }

        function onTitleCardAddClick() {
            if (activePreviewItemIndex >= 0 && searchDropdownItems && searchDropdownItems[activePreviewItemIndex]) {
                onSelectDropdownItem(activePreviewItemIndex);
            }
        }

        // Keep preview card dynamically positioned when dropdown table scrolls
        const dropdownListEl = document.getElementById('searchDropdownList');
        if (dropdownListEl) {
            dropdownListEl.addEventListener('scroll', () => {
                if (activePreviewItemIndex >= 0) {
                    const card = document.getElementById('searchProductLinkedPopup');
                    const dropdown = document.getElementById('searchDropdown');
                    const row = document.getElementById('searchDropdownItem_' + activePreviewItemIndex);
                    if (card && dropdown && !card.classList.contains('hidden')) {
                        positionTitleCard(card, dropdown, row);
                    }
                }
            });
        }

        window.addEventListener('resize', () => {
            if (activePreviewItemIndex >= 0) {
                const card = document.getElementById('searchProductLinkedPopup');
                const dropdown = document.getElementById('searchDropdown');
                const row = document.getElementById('searchDropdownItem_' + activePreviewItemIndex);
                if (card && dropdown && !card.classList.contains('hidden')) {
                    positionTitleCard(card, dropdown, row);
                }
            }
        });

        async function onSearchKeyDown(e) {
            if (e.key === 'ArrowDown') {
                if (!isSearchDropdownOpen || searchDropdownItems.length === 0) return;
                e.preventDefault();
                searchHighlightedIndex++;
                if (searchHighlightedIndex >= searchDropdownItems.length) {
                    searchHighlightedIndex = 0;
                }
                updateHighlightedSearchItem();
            } else if (e.key === 'ArrowUp') {
                if (!isSearchDropdownOpen || searchDropdownItems.length === 0) return;
                e.preventDefault();
                searchHighlightedIndex--;
                if (searchHighlightedIndex < 0) {
                    searchHighlightedIndex = searchDropdownItems.length - 1;
                }
                updateHighlightedSearchItem();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const input = document.getElementById('foodSearchInput');
                const query = input ? input.value.trim() : '';

                // If items already rendered in dropdown, select highlighted or first item
                if (searchDropdownItems.length > 0) {
                    const targetIndex = (searchHighlightedIndex >= 0 && searchHighlightedIndex < searchDropdownItems.length)
                        ? searchHighlightedIndex
                        : 0;
                    onSelectDropdownItem(targetIndex);
                    return;
                }

                // If fast barcode scan or instant Enter before debounce: fetch & add immediately
                if (query.length > 0) {
                    clearTimeout(searchDebounce);
                    showSearchDropdownLoading(query);
                    try {
                        const params = new URLSearchParams({
                            category_id: 'all',
                            query: query,
                            global_search: '1',
                            veg_only: vegFilterOnly ? '1' : '0'
                        });
                        const res = await fetch(`{{ route('pos.search') }}?${params.toString()}`);
                        const data = await res.json();
                        if (data.success) {
                            const allItems = [...(data.combos || []), ...(data.foods || [])];
                            if (allItems.length > 0) {
                                searchDropdownItems = allItems;
                                const exactMatchIdx = allItems.findIndex(i => ((i.barcode || '').toLowerCase() === query.toLowerCase()) || ((i.code || '').toLowerCase() === query.toLowerCase()));
                                const pickIdx = exactMatchIdx > -1 ? exactMatchIdx : 0;
                                onSelectDropdownItem(pickIdx);
                            } else {
                                renderSearchDropdown([], query);
                            }
                        }
                    } catch (err) {
                        console.error("Barcode scan enter error:", err);
                    }
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                hideSearchDropdown();
            }
        }

        function onSearchFocus() {
            const input = document.getElementById('foodSearchInput');
            if (input && input.value.trim().length > 0 && searchDropdownItems.length > 0) {
                const dropdown = document.getElementById('searchDropdown');
                if (dropdown) dropdown.classList.remove('hidden');
                isSearchDropdownOpen = true;
                if (searchHighlightedIndex >= 0) {
                    showSearchProductPreview(searchHighlightedIndex);
                } else if (searchDropdownItems.length > 0) {
                    highlightSearchItem(0);
                }
            }
        }

        function hideSearchDropdown() {
            const dropdown = document.getElementById('searchDropdown');
            if (dropdown) dropdown.classList.add('hidden');
            isSearchDropdownOpen = false;
            searchHighlightedIndex = -1;
            hideSearchProductPreview();
        }

        function onSelectDropdownItem(index) {
            if (!searchDropdownItems || !searchDropdownItems[index]) return;
            const item = searchDropdownItems[index];

            // 1. Add directly to cart with tax_rate
            addToCart(item.type, item.id, item.code, item.name, item.price, item.tax_rate);

            // 2. Immediate feedback toast
            Toast.fire({
                icon: 'success',
                title: `Added "${item.name}" to cart`
            });

            // 3. Clear search box and refocus for next scan / search
            const input = document.getElementById('foodSearchInput');
            if (input) {
                input.value = '';
                input.focus();
            }
            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) clearBtn.classList.add('hidden');

            // 4. Hide dropdown & linked popup and reset search state
            hideSearchDropdown();
            searchDropdownItems = [];

            // 5. Restore background food grid
            fetchFoods();
        }

        function clearSearch() {
            const input = document.getElementById('foodSearchInput');
            if (input) {
                input.value = '';
                input.focus();
            }
            const clearBtn = document.getElementById('clearSearchBtn');
            if (clearBtn) clearBtn.classList.add('hidden');
            hideSearchDropdown();
            searchDropdownItems = [];
            fetchFoods();
        }

        // Close dropdown when clicking outside (allowing clicks inside searchContainer or linkedPopup)
        document.addEventListener('click', (e) => {
            const searchContainer = document.getElementById('searchContainer');
            const previewCard = document.getElementById('searchProductLinkedPopup');
            if (
                searchContainer && !searchContainer.contains(e.target) &&
                (!previewCard || !previewCard.contains(e.target))
            ) {
                hideSearchDropdown();
            }
        });

        // 3. CART OPERATIONS
        function addToCart(type, id, code, name, price, tax_rate) {
            const taxVal = parseFloat(tax_rate !== undefined ? tax_rate : 5);
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
                    discount_type: 'fixed',
                    discount_value: 0,
                    tax_rate: taxVal,
                    notes: '',
                    addons: []
                });
            }

            renderCart();
            triggerCalculation();
        }

        function setCartQty(index, val) {
            if (!cart[index]) return;
            const parsed = parseFloat(val);
            if (isNaN(parsed) || parsed <= 0) {
                cart.splice(index, 1);
            } else {
                cart[index].quantity = parsed;
            }
            renderCart();
            triggerCalculation();
        }

        function updateItemDiscount(index, val) {
            if (!cart[index]) return;
            const parsed = Math.max(0, parseFloat(val) || 0);
            cart[index].discount_value = parsed;
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
                    Toast.fire({ icon: 'info', title: 'Cart is already empty.' });
                    return;
                }

                const confirmMsg = activeBillId
                    ? `Clear order #${activeInvoiceNumber || ''}? This will remove it from the database and set table status to available.`
                    : (currentTableId ? 'Clear table order and set table status to available?' : 'Are you sure you want to clear current order?');

                const confirmResult = await Swal.fire({
                    title: 'Clear Current Order?',
                    text: confirmMsg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: '<i class="fa-solid fa-trash mr-1"></i> Yes, Clear Order',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                });

                if (!confirmResult.isConfirmed) return;

                if (activeBillId || currentTableId) {
                    try {
                        const res = await fetch(`{{ route('pos.clear') }}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF_TOKEN,
                                'Accept': 'application/json'
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

                        Toast.fire({
                            icon: 'success',
                            title: data.message || 'Order cleared and table available.'
                        });
                    } catch (err) {
                        console.error('Error clearing bill in database:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to clear order from server.'
                        });
                        return;
                    }
                } else {
                    Toast.fire({
                        icon: 'success',
                        title: 'Cart cleared.'
                    });
                }
            }

            cart = [];
            activeBillId = null;
            activeInvoiceNumber = null;
            const billDisplay = document.getElementById('activeBillIdDisplay');
            if (billDisplay) billDisplay.innerText = '';

            const sel = document.getElementById('selectedTableId');
            if (sel) {
                sel.value = '';
            }
            currentTableId = null;
            updateOrderTargetDisplay();

            clearSelectedCustomer();
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

        function renderCart(syncTotals = true) {
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
                if (totalQtySpan) totalQtySpan.innerText = '0';
                const mobHeaderCount = document.getElementById('mobileCartHeaderCount');
                if (mobHeaderCount) mobHeaderCount.innerText = '0';
                const mobBottomQty = document.getElementById('mobileBottomQty');
                if (mobBottomQty) mobBottomQty.innerText = '0 items';
                return;
            }

            let totalQty = 0;
            let html = `
                <div class="overflow-x-auto -mx-1">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-100 text-slate-600 font-bold uppercase text-[10px] sticky top-0 z-10 border-b border-slate-200">
                            <tr>
                                <th class="py-2 px-1 text-center w-14">Code</th>
                                <th class="py-2 px-1">Item</th>
                                <th class="py-2 px-1 text-center w-20">Qty</th>
                                <th class="py-2 px-1 text-right w-14">Rate</th>
                                <th class="py-2 px-1 text-center w-14">Disc</th>
                                <th class="py-2 px-1 text-center w-12">GST</th>
                                <th class="py-2 px-1 text-right w-16">Total</th>
                                <th class="py-2 px-0.5 text-center w-6"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
            `;

            cart.forEach((item, index) => {
                const qty = parseFloat(item.quantity) || 1;
                totalQty += qty;

                // Check server calculated item total if available
                let itemTotal = 0;
                let itemTaxRate = parseFloat(item.tax_rate !== undefined ? item.tax_rate : 5);
                if (calculationState && calculationState.items && calculationState.items[index]) {
                    itemTotal = calculationState.items[index].total;
                    itemTaxRate = calculationState.items[index].tax_rate;
                } else {
                    const lineGross = (item.unit_price * qty);
                    const lineDisc = parseFloat(item.discount_value || 0);
                    const lineTaxable = Math.max(0, lineGross - lineDisc);
                    const lineTax = (TAX_TYPE === 'exclusive') ? (lineTaxable * itemTaxRate / 100) : 0;
                    itemTotal = lineTaxable + lineTax;
                }

                // Addons list string
                let addonsHtml = '';
                if (item.addons && item.addons.length > 0) {
                    const addonNames = AVAILABLE_ADDONS.filter(a => item.addons.includes(a.id)).map(a => a.name).join(', ');
                    addonsHtml = `<p class="text-[9px] text-orange-600 font-medium leading-none mt-0.5">+ ${addonNames}</p>`;
                }

                html += `
                    <tr class="hover:bg-slate-50/70 transition group">
                        <!-- 1. Code -->
                        <td class="py-2 px-1 text-center font-mono font-bold text-slate-700 align-top">
                            <span class="px-1 py-0.5 rounded bg-slate-100 text-[10px] block truncate" title="${escapeHtml(item.code)}">${escapeHtml(item.code)}</span>
                        </td>

                        <!-- 2. Item Name -->
                        <td class="py-2 px-1 align-top">
                            <div class="font-bold text-slate-800 text-[11px] leading-tight truncate max-w-[95px] sm:max-w-[130px]" title="${escapeHtml(item.name)}">
                                ${escapeHtml(item.name)}
                            </div>
                            ${addonsHtml}
                            <div class="mt-0.5 flex items-center gap-1 text-[9px] text-slate-400">
                                <button type="button" onclick="openAddonsModal(${index})" class="hover:text-orange-600 font-semibold text-[9px]" title="Extra Add-ons">
                                    <i class="fa-solid fa-circle-plus text-orange-500"></i> Extra
                                </button>
                                <span>&bull;</span>
                                <input type="text" value="${escapeHtml(item.notes || '')}" onchange="cart[${index}].notes = this.value" placeholder="Note..." class="text-[9px] border-b border-transparent hover:border-slate-200 focus:border-orange-500 px-0.5 py-0 text-slate-500 max-w-[70px] focus:outline-none bg-transparent">
                            </div>
                        </td>

                        <!-- 3. Quantity -->
                        <td class="py-2 px-1 align-top text-center">
                            <div class="flex items-center justify-center border border-slate-200 rounded-lg overflow-hidden bg-white shadow-2xs">
                                <button type="button" onclick="updateCartQty(${index}, -1)" class="w-4 h-6 text-slate-600 hover:bg-slate-100 flex items-center justify-center font-black text-xs active:scale-90 transition touch-action-manipulation">-</button>
                                <input type="number" min="0.01" step="1" value="${item.quantity}" onchange="setCartQty(${index}, this.value)" class="w-8 h-6 text-center text-[11px] font-black border-x border-slate-200 focus:outline-none p-0">
                                <button type="button" onclick="updateCartQty(${index}, 1)" class="w-4 h-6 text-orange-600 hover:bg-slate-100 flex items-center justify-center font-black text-xs active:scale-90 transition touch-action-manipulation">+</button>
                            </div>
                        </td>

                        <!-- 4. Rate -->
                        <td class="py-2 px-1 align-top text-right font-medium text-slate-700 text-[11px]">
                            ${CURRENCY}${parseFloat(item.unit_price).toFixed(2)}
                        </td>

                        <!-- 5. Discount Column (per-item discount input) -->
                        <td class="py-2 px-1 align-top text-center">
                            <input type="number" min="0" step="1" value="${item.discount_value || 0}"
                                onchange="updateItemDiscount(${index}, this.value)"
                                onfocus="this.select()"
                                title="Discount in ${CURRENCY}"
                                placeholder="0"
                                class="w-12 h-6 px-1 text-[11px] text-right font-bold text-rose-600 border border-slate-200 rounded focus:border-orange-500 focus:outline-none bg-rose-50/50">
                        </td>

                        <!-- 6. GST Column (% display) -->
                        <td class="py-2 px-1 align-top text-center">
                            <span class="px-1 py-0.5 rounded text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200 whitespace-nowrap">
                                ${parseFloat(itemTaxRate).toFixed(0)}%
                            </span>
                        </td>

                        <!-- 7. Total Amount -->
                        <td class="py-2 px-1 align-top text-right font-black text-slate-900 text-xs">
                            ${CURRENCY}${parseFloat(itemTotal).toFixed(2)}
                        </td>

                        <!-- Action / Remove -->
                        <td class="py-2 px-0.5 align-top text-center">
                            <button type="button" onclick="removeCartItem(${index})" class="text-slate-300 hover:text-rose-500 active:scale-90 p-1" title="Remove Item">
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = html;
            if (totalQtySpan) totalQtySpan.innerText = totalQty;
            const mobHeaderCount = document.getElementById('mobileCartHeaderCount');
            if (mobHeaderCount) mobHeaderCount.innerText = totalQty;
            const mobBottomQty = document.getElementById('mobileBottomQty');
            if (mobBottomQty) mobBottomQty.innerText = `${totalQty} item${totalQty === 1 ? '' : 's'}`;
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
                const mobBottomTotal = document.getElementById('mobileBottomTotal');
                if (mobBottomTotal) mobBottomTotal.innerText = `${CURRENCY}0.00`;
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
                    renderCart(false);
                    document.getElementById('subtotalDisplay').innerText = `${CURRENCY}${calculationState.subtotal.toFixed(2)}`;
                    document.getElementById('taxTotalDisplay').innerText = `${CURRENCY}${calculationState.tax_total.toFixed(2)}`;
                    document.getElementById('cgstSgstDisplay').innerText = `${CURRENCY}${calculationState.cgst_total.toFixed(2)} + ${CURRENCY}${calculationState.sgst_total.toFixed(2)}`;
                    document.getElementById('discountAmountDisplay').innerText = `-${CURRENCY}${calculationState.discount_amount.toFixed(2)}`;
                    document.getElementById('roundingDisplay').innerText = `${CURRENCY}${calculationState.rounding_difference.toFixed(2)}`;
                    document.getElementById('grandTotalDisplay').innerText = `${CURRENCY}${calculationState.grand_total.toFixed(2)}`;
                    const mobBottomTotal = document.getElementById('mobileBottomTotal');
                    if (mobBottomTotal) mobBottomTotal.innerText = `${CURRENCY}${calculationState.grand_total.toFixed(2)}`;
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
                Toast.fire({ icon: 'warning', title: 'Cart is empty. Please add items to hold/save bill.' });
                return;
            }

            if (orderType === 'table' && !currentTableId) {
                Toast.fire({ icon: 'warning', title: 'Please select a dining table for table-based orders.' });
                return;
            }

            const payload = {
                bill_id: activeBillId,
                order_type: orderType,
                table_id: currentTableId,
                waiter_id: currentWaiterId,
                waiter_name: currentWaiterName,
                status: status,
                customer_id: document.getElementById('selectedCustomerId')?.value || null,
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
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    Toast.fire({
                        icon: 'success',
                        title: data.message
                    });
                    clearCart(true);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Save Failed',
                        text: data.message || 'Failed to save bill.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Network error while saving bill.'
                });
            }
        }

        // 5.1 KOT (KITCHEN ORDER TICKET) PRINTING
        async function printKot() {
            if (cart.length === 0) {
                Toast.fire({ icon: 'warning', title: 'Cart is empty. Add items to print KOT.' });
                return;
            }

            if (orderType === 'table' && !currentTableId) {
                Toast.fire({ icon: 'warning', title: 'Please select a dining table for kitchen KOT.' });
                return;
            }

            const btn = document.getElementById('btnKotPrint');
            const originalHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Printing...</span>';
            }

            try {
                const res = await fetch(`{{ route('pos.kot') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        table_id: currentTableId,
                        bill_id: activeBillId,
                        waiter_id: currentWaiterId,
                        waiter_name: currentWaiterName,
                        notes: document.getElementById('custName')?.value ? `Cust: ${document.getElementById('custName').value}` : null,
                        items: cart
                    })
                });

                const data = await res.json();

                if (data.bill_id) {
                    activeBillId = data.bill_id;
                    activeInvoiceNumber = data.invoice_number;
                    const billDisplay = document.getElementById('activeBillIdDisplay');
                    if (billDisplay) billDisplay.innerText = `#${data.invoice_number}`;
                }

                if (data.success) {
                    const hasNetworkDirect = data.direct_prints && data.direct_prints.some(p => p.success);
                    const hasBrowserPrint = !data.kitchen_printers || data.kitchen_printers.some(p => p.connection_type === 'browser') || !hasNetworkDirect;

                    if (hasNetworkDirect) {
                        const directNames = data.direct_prints.filter(p => p.success).map(p => p.printer).join(', ');
                        Toast.fire({
                            icon: 'success',
                            title: `KOT sent to Kitchen: ${directNames}`
                        });
                    }

                    if (hasBrowserPrint && data.kot_print_url) {
                        window.open(data.kot_print_url, '_blank', 'width=380,height=550');
                        if (!hasNetworkDirect) {
                            Toast.fire({
                                icon: 'success',
                                title: data.message
                            });
                        }
                    }
                } else if (data.is_all_printed) {
                    const reprintConfirm = await Swal.fire({
                        title: 'All Items Already Printed',
                        text: data.message + '\nWould you like to reprint the existing KOT ticket?',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#4f46e5',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: '<i class="fa-solid fa-print mr-1"></i> Reprint KOT',
                        cancelButtonText: 'Close'
                    });

                    if (reprintConfirm.isConfirmed && data.kot_print_url) {
                        window.open(data.kot_print_url, '_blank', 'width=380,height=550');
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'KOT Error',
                        text: data.message || 'Failed to generate KOT.'
                    });
                }
            } catch (err) {
                console.error('KOT error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Failed to communicate with kitchen server.'
                });
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            }
        }

        // 6. PAYMENT MODAL & COMPLETION
        function openPaymentModal() {
            if (cart.length === 0 || !calculationState) {
                Toast.fire({ icon: 'warning', title: 'Cart is empty! Add items before initiating payment.' });
                return;
            }

            if (orderType === 'table' && !currentTableId) {
                Toast.fire({ icon: 'warning', title: 'Please select a dining table before settling.' });
                return;
            }

            const grandTotal = calculationState.grand_total || 0;
            document.getElementById('payDueAmountDisplay').innerText = `${CURRENCY}${grandTotal.toFixed(2)}`;
            document.getElementById('payDueItemsCount').innerText = `${calculationState.total_quantity} items`;
            document.getElementById('cashTenderedInput').value = grandTotal;

            // Multimode initialization
            const multiCash = document.getElementById('multiCashInput');
            const multiUpi = document.getElementById('multiUpiInput');
            const multiCredit = document.getElementById('multiCreditInput');
            if (multiCash) multiCash.value = grandTotal.toFixed(2);
            if (multiUpi) multiUpi.value = '0';
            if (multiCredit) multiCredit.value = '0';
            const multiRef = document.getElementById('multiUpiRefInput');
            if (multiRef) multiRef.value = '';
            calculateMultiModeSplit();

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
                btn.className = 'pay-method-btn p-2 sm:p-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 font-bold text-xs flex flex-col items-center gap-1 transition active:scale-95 touch-action-manipulation';
            });
            const active = document.getElementById(`payTab_${method}`);
            if (active) {
                active.className = 'pay-method-btn p-2 sm:p-2.5 rounded-xl border border-orange-600 bg-orange-50 text-orange-800 font-bold text-xs flex flex-col items-center gap-1 transition active:scale-95 touch-action-manipulation';
            }

            const cashArea = document.getElementById('cashDetailsArea');
            const refArea = document.getElementById('referenceDetailsArea');
            const multiArea = document.getElementById('multimodeDetailsArea');
            const creditArea = document.getElementById('creditDetailsArea');

            if (method === 'cash') {
                cashArea?.classList.remove('hidden');
                refArea?.classList.add('hidden');
                multiArea?.classList.add('hidden');
                creditArea?.classList.add('hidden');
            } else if (method === 'multimode') {
                cashArea?.classList.add('hidden');
                refArea?.classList.add('hidden');
                multiArea?.classList.remove('hidden');
                creditArea?.classList.add('hidden');
                calculateMultiModeSplit();
            } else if (method === 'credit') {
                cashArea?.classList.add('hidden');
                refArea?.classList.add('hidden');
                multiArea?.classList.add('hidden');
                creditArea?.classList.remove('hidden');
                updateCreditPaymentPreview();
            } else {
                cashArea?.classList.add('hidden');
                refArea?.classList.remove('hidden');
                multiArea?.classList.add('hidden');
                creditArea?.classList.add('hidden');
            }
        }

        function updateCreditPaymentPreview() {
            const hasCust = selectedCustomerState && selectedCustomerState.id;
            const noCustBox = document.getElementById('creditNoCustomerBox');
            const selCustBox = document.getElementById('creditSelectedCustomerBox');
            const statusBadge = document.getElementById('creditCustomerStatusBadge');

            if (!hasCust) {
                noCustBox?.classList.remove('hidden');
                selCustBox?.classList.add('hidden');
                if (statusBadge) statusBadge.innerText = 'No Customer Selected';
                return;
            }

            noCustBox?.classList.add('hidden');
            selCustBox?.classList.remove('hidden');

            const currentBal = parseFloat(selectedCustomerState.balance) || 0;
            const grandTotal = parseFloat(calculationState?.grand_total) || 0;
            const projectedBal = +(currentBal + grandTotal).toFixed(2);

            const nameEl = document.getElementById('creditCustomerNameDisplay');
            const prevBalEl = document.getElementById('creditPreviousBalanceDisplay');
            const billAmtEl = document.getElementById('creditBillAmountDisplay');
            const newBalEl = document.getElementById('creditNewBalanceDisplay');

            if (nameEl) nameEl.innerText = selectedCustomerState.name;
            if (prevBalEl) {
                prevBalEl.innerText = selectedCustomerState.formatted_balance || (currentBal > 0.001 ? `Due: ${CURRENCY}${currentBal.toFixed(2)}` : (currentBal < -0.001 ? `Adv: ${CURRENCY}${Math.abs(currentBal).toFixed(2)}` : 'Clear: ₹0.00'));
                prevBalEl.className = `font-mono font-bold ${currentBal > 0.001 ? 'text-rose-600' : (currentBal < -0.001 ? 'text-sky-600' : 'text-slate-800')}`;
            }
            if (billAmtEl) {
                billAmtEl.innerText = `+${CURRENCY}${grandTotal.toFixed(2)}`;
            }
            if (newBalEl) {
                newBalEl.innerText = projectedBal > 0.001 ? `Due: ${CURRENCY}${projectedBal.toFixed(2)}` : (projectedBal < -0.001 ? `Adv: ${CURRENCY}${Math.abs(projectedBal).toFixed(2)}` : 'Clear: ₹0.00');
                newBalEl.className = `font-mono text-sm font-black ${projectedBal > 0.001 ? 'text-rose-700' : (projectedBal < -0.001 ? 'text-sky-700' : 'text-emerald-700')}`;
            }
            if (statusBadge) {
                statusBadge.innerText = `New Due: ${CURRENCY}${projectedBal.toFixed(2)}`;
            }
        }

        function calculateMultiModeSplit() {
            if (!calculationState) return;
            const grandTotal = parseFloat(calculationState.grand_total) || 0;
            const cashVal = parseFloat(document.getElementById('multiCashInput')?.value) || 0;
            const upiVal = parseFloat(document.getElementById('multiUpiInput')?.value) || 0;
            const creditVal = parseFloat(document.getElementById('multiCreditInput')?.value) || 0;
            const totalEntered = +(cashVal + upiVal + creditVal).toFixed(2);
            const diff = +(grandTotal - totalEntered).toFixed(2);

            const enteredSpan = document.getElementById('multiEnteredSum');
            const balanceSpan = document.getElementById('multiBalanceStatus');
            const statusBox = document.getElementById('multiStatusBox');
            const dueBadge = document.getElementById('multiDueBadge');

            if (dueBadge) dueBadge.innerText = `Due: ${CURRENCY}${grandTotal.toFixed(2)}`;
            if (enteredSpan) enteredSpan.innerText = `${CURRENCY}${totalEntered.toFixed(2)}`;

            if (Math.abs(diff) < 0.01) {
                if (balanceSpan) balanceSpan.innerText = 'Matched (Exact)';
                if (statusBox) statusBox.className = 'p-2 rounded-xl text-xs font-bold flex items-center justify-between bg-emerald-50 text-emerald-800 border border-emerald-200';
            } else if (diff > 0) {
                if (balanceSpan) balanceSpan.innerText = `Short: ${CURRENCY}${diff.toFixed(2)}`;
                if (statusBox) statusBox.className = 'p-2 rounded-xl text-xs font-bold flex items-center justify-between bg-amber-50 text-amber-800 border border-amber-200';
            } else {
                if (balanceSpan) balanceSpan.innerText = `Excess: ${CURRENCY}${Math.abs(diff).toFixed(2)}`;
                if (statusBox) statusBox.className = 'p-2 rounded-xl text-xs font-bold flex items-center justify-between bg-rose-50 text-rose-800 border border-rose-200';
            }
        }

        function autoFillMultiCash() {
            if (!calculationState) return;
            const grandTotal = parseFloat(calculationState.grand_total) || 0;
            const upiVal = parseFloat(document.getElementById('multiUpiInput')?.value) || 0;
            const creditVal = parseFloat(document.getElementById('multiCreditInput')?.value) || 0;
            const rem = Math.max(0, +(grandTotal - upiVal - creditVal).toFixed(2));
            const cashInput = document.getElementById('multiCashInput');
            if (cashInput) cashInput.value = rem > 0 ? rem.toFixed(2) : '0';
            calculateMultiModeSplit();
        }

        function autoFillMultiUpi() {
            if (!calculationState) return;
            const grandTotal = parseFloat(calculationState.grand_total) || 0;
            const cashVal = parseFloat(document.getElementById('multiCashInput')?.value) || 0;
            const creditVal = parseFloat(document.getElementById('multiCreditInput')?.value) || 0;
            const rem = Math.max(0, +(grandTotal - cashVal - creditVal).toFixed(2));
            const upiInput = document.getElementById('multiUpiInput');
            if (upiInput) upiInput.value = rem > 0 ? rem.toFixed(2) : '0';
            calculateMultiModeSplit();
        }

        function autoFillMultiCredit() {
            if (!calculationState) return;
            const grandTotal = parseFloat(calculationState.grand_total) || 0;
            const cashVal = parseFloat(document.getElementById('multiCashInput')?.value) || 0;
            const upiVal = parseFloat(document.getElementById('multiUpiInput')?.value) || 0;
            const rem = Math.max(0, +(grandTotal - cashVal - upiVal).toFixed(2));
            const creditInput = document.getElementById('multiCreditInput');
            if (creditInput) creditInput.value = rem > 0 ? rem.toFixed(2) : '0';
            calculateMultiModeSplit();
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

            const customerId = document.getElementById('selectedCustomerId')?.value || null;

            if (selectedPaymentMethod === 'credit' && !customerId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Customer Required',
                    text: 'Credit (Due) billing requires a registered customer so the amount can be tracked in their ledger. Please select or register a customer.'
                });
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-double"></i><span>Complete & Print Bill</span>';
                return;
            }

            let payments = [];
            if (selectedPaymentMethod === 'multimode') {
                const cashAmt = parseFloat(document.getElementById('multiCashInput')?.value) || 0;
                const upiAmt = parseFloat(document.getElementById('multiUpiInput')?.value) || 0;
                const creditAmt = parseFloat(document.getElementById('multiCreditInput')?.value) || 0;
                const multiRef = document.getElementById('multiUpiRefInput')?.value?.trim() || null;

                if (cashAmt <= 0 && upiAmt <= 0 && creditAmt <= 0) {
                    Toast.fire({ icon: 'warning', title: 'Please enter Cash, UPI, and/or Credit amounts for Split payment.' });
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check-double"></i><span>Complete & Print Bill</span>';
                    return;
                }

                if (creditAmt > 0 && !customerId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Customer Required for Credit Portion',
                        text: 'A registered customer is required to record the credit portion into their ledger.'
                    });
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check-double"></i><span>Complete & Print Bill</span>';
                    return;
                }

                const totalPaid = +(cashAmt + upiAmt + creditAmt).toFixed(2);
                const grandTotal = +(calculationState.grand_total).toFixed(2);
                if (Math.abs(totalPaid - grandTotal) > 0.05) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Amount Mismatch',
                        text: `Entered total (${CURRENCY}${totalPaid.toFixed(2)}) must equal bill grand total (${CURRENCY}${grandTotal.toFixed(2)}).`
                    });
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-check-double"></i><span>Complete & Print Bill</span>';
                    return;
                }

                if (cashAmt > 0) {
                    payments.push({
                        payment_method: 'cash',
                        amount: cashAmt,
                        reference_number: null
                    });
                }
                if (upiAmt > 0) {
                    payments.push({
                        payment_method: 'upi',
                        amount: upiAmt,
                        reference_number: multiRef
                    });
                }
                if (creditAmt > 0) {
                    payments.push({
                        payment_method: 'credit',
                        amount: creditAmt,
                        reference_number: multiRef
                    });
                }
            } else if (selectedPaymentMethod === 'credit') {
                payments.push({
                    payment_method: 'credit',
                    amount: calculationState.grand_total,
                    reference_number: document.getElementById('creditRefInput')?.value?.trim() || null
                });
            } else {
                payments.push({
                    payment_method: selectedPaymentMethod,
                    amount: calculationState.grand_total,
                    reference_number: document.getElementById('payReferenceInput').value || null
                });
            }

            const payload = {
                bill_id: activeBillId,
                customer_id: customerId,
                order_type: orderType,
                table_id: currentTableId,
                waiter_id: currentWaiterId,
                waiter_name: currentWaiterName,
                customer_name: document.getElementById('custName').value,
                customer_phone: document.getElementById('custPhone').value,
                discount_type: document.getElementById('discountType').value,
                discount_value: parseFloat(document.getElementById('discountValue').value) || 0,
                items: cart,
                payments: payments
            };

            try {
                const res = await fetch(
                    `{{ route('pos.pay') }}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    closePaymentModal();
                    
                    const hasNetworkDirect = data.direct_prints && data.direct_prints.some(p => p.success);
                    const hasBrowserPrint = !data.counter_printers || data.counter_printers.some(p => p.connection_type === 'browser') || !hasNetworkDirect;

                    if (hasBrowserPrint && data.print_url) {
                        window.open(data.print_url, '_blank', 'width=400,height=600');
                    }

                    let extraInfo = '';
                    if (hasNetworkDirect) {
                        const directNames = data.direct_prints.filter(p => p.success).map(p => p.printer).join(', ');
                        extraInfo = `\n(Printed & cash drawer triggered on: ${directNames})`;
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Payment Completed',
                        text: (data.message || 'Payment completed successfully!') + extraInfo,
                        confirmButtonColor: '#ea580c',
                        timer: 2500,
                        showConfirmButton: true
                    });
                    clearCart(true);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Payment Failed',
                        text: data.message || 'Payment processing failed.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Payment Error',
                    text: 'Payment submission error.'
                });
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
                Toast.fire({ icon: 'error', title: 'Failed to resume held bill.' });
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
                Toast.fire({ icon: 'error', title: 'Failed to resume draft.' });
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

            if (bill.waiter_id) {
                currentWaiterId = bill.waiter_id;
                currentWaiterName = bill.waiter_name || null;
                const waiterSel = document.getElementById('selectedWaiterId');
                if (waiterSel) waiterSel.value = bill.waiter_id;
            } else if (bill.waiter_name) {
                currentWaiterName = bill.waiter_name;
            } else if (LOGGED_STAFF_ID) {
                currentWaiterId = LOGGED_STAFF_ID;
                currentWaiterName = LOGGED_STAFF_NAME;
                const waiterSel = document.getElementById('selectedWaiterId');
                if (waiterSel) waiterSel.value = LOGGED_STAFF_ID;
            }

            if (bill.customer) {
                setSelectedCustomer(bill.customer);
            } else if (bill.customer_id) {
                setSelectedCustomer({
                    id: bill.customer_id,
                    name: bill.customer_name || 'Customer',
                    phone: bill.customer_phone || '',
                    balance: 0,
                    formatted_balance: 'Clear: ₹0.00'
                });
            } else {
                clearSelectedCustomer();
                document.getElementById('custName').value = bill.customer_name || '';
                document.getElementById('custPhone').value = bill.customer_phone || '';
            }
            document.getElementById('discountType').value = bill.discount_type || 'fixed';
            document.getElementById('discountValue').value = bill.discount_value || 0;

            cart = bill.items.map(item => ({
                item_type: item.item_type,
                item_id: item.item_id,
                code: item.food_code || item.code,
                name: item.item_name || item.name,
                unit_price: parseFloat(item.unit_price),
                quantity: parseFloat(item.quantity),
                discount_type: item.discount_type || 'fixed',
                discount_value: parseFloat(item.discount_value || 0),
                tax_rate: parseFloat(item.tax_rate !== undefined ? item.tax_rate : 5),
                notes: item.notes || '',
                addons: item.addons || []
            }));

            renderCart();
            triggerCalculation();
        }

        async function confirmCancelBill() {
            const reasonInput = document.getElementById('cancelReasonInput');
            const reason = reasonInput ? reasonInput.value.trim() : '';
            if (!reason) {
                Toast.fire({ icon: 'warning', title: 'Please provide a cancellation reason.' });
                return;
            }
            if (!activeBillId) {
                document.getElementById('cancelModal').classList.add('hidden');
                clearCart();
                return;
            }
            try {
                const cancelUrl = `{{ route('pos.cancel', ['bill' => ':id']) }}`.replace(':id', activeBillId);
                const res = await fetch(cancelUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                    body: JSON.stringify({ reason: reason })
                });
                const data = await res.json();
                document.getElementById('cancelModal').classList.add('hidden');
                if (data.success) {
                    Toast.fire({ icon: 'success', title: data.message });
                    clearCart(true);
                } else {
                    Swal.fire({ icon: 'error', title: 'Cancellation Failed', text: data.message });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Failed to cancel bill.' });
            }
        }
    </script>
</body>
</html>
