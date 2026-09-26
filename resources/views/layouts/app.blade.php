<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Restaurant Billing & POS') - {{ \App\Models\Setting::get('restaurant_name', 'RestroPOS') }}</title>
    
    <!-- Tailwind CSS (Play CDN for standalone zero-compile reliability) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                        },
                        navy: {
                            800: '#1e293b',
                            900: '#0f172a',
                            950: '#020617',
                        }
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-800 antialiased font-sans">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar Navigation -->
        <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col flex-shrink-0 border-r border-slate-800">
            <!-- Brand / Logo -->
            <div class="h-16 flex items-center px-5 border-b border-slate-800 bg-slate-950">
                <div class="w-10 h-10 rounded-lg bg-orange-600 flex items-center justify-center text-white font-bold text-xl mr-3 shadow-lg shadow-orange-600/30">
                    <i class="fa-solid fa-utensils"></i>
                </div>
                <div class="overflow-hidden">
                    <h1 class="text-white font-bold text-base truncate">{{ \App\Models\Setting::get('restaurant_name', 'RestroPOS') }}</h1>
                    <p class="text-xs text-orange-400 font-medium">Billing & POS</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
                @if(auth()->user()->hasPermission('dashboard.view'))
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-orange-600 text-white shadow-sm' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                    <span>Dashboard</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('pos.billing'))
                <div class="pt-1 pb-1">
                    <a href="{{ route('pos.index') }}" class="flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-semibold bg-gradient-to-r from-orange-600 to-amber-600 text-white shadow-md shadow-orange-600/20 hover:from-orange-500 hover:to-amber-500 transition">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-cash-register w-5 text-center"></i>
                            <span>POS Billing</span>
                        </div>
                        <span class="bg-white/20 text-xs px-2 py-0.5 rounded-full font-bold">Fast</span>
                    </a>
                </div>
                @endif

                @if(auth()->user()->hasPermission('food.view'))
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-3 pt-3 pb-1">Menu Management</div>
                <a href="{{ route('categories.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('categories.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-layer-group w-5 text-center"></i>
                    <span>Categories</span>
                </a>
                <a href="{{ route('foods.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('foods.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-bowl-food w-5 text-center"></i>
                    <span>Food Items</span>
                </a>
                <a href="{{ route('addons.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('addons.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-plus-circle w-5 text-center"></i>
                    <span>Add-ons / Extras</span>
                </a>
                <a href="{{ route('combos.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('combos.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-box-archive w-5 text-center"></i>
                    <span>Combo Packs</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('table.view'))
                <a href="{{ route('tables.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('tables.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-chair w-5 text-center"></i>
                    <span>Tables & Dining</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('inventory.view'))
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-3 pt-3 pb-1">Operations</div>
                <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('inventory.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-boxes-stacked w-5 text-center"></i>
                    <span>Stock & Inventory</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('expense.view'))
                <a href="{{ route('expenses.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('expenses.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-wallet w-5 text-center"></i>
                    <span>Expenses</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('dayclosing.view'))
                <a href="{{ route('dayclosing.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('dayclosing.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-lock w-5 text-center"></i>
                    <span>Cash & Day Closing</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('reports.view'))
                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('reports.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>
                    <span>Reports (12)</span>
                </a>
                @endif

                @if(auth()->user()->hasRole('admin') || auth()->user()->hasPermission('settings.manage'))
                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider px-3 pt-3 pb-1">Administration</div>
                <a href="{{ route('tax.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('tax.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-percent w-5 text-center"></i>
                    <span>Tax & GST</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('users.manage'))
                <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('users.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-users w-5 text-center"></i>
                    <span>Users & Staff</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('settings.manage'))
                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('settings.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-gear w-5 text-center"></i>
                    <span>Settings</span>
                </a>
                @endif

                @if(auth()->user()->hasPermission('backup.manage') || auth()->user()->hasPermission('audit.view'))
                <a href="{{ route('backup.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition {{ request()->routeIs('backup.*') ? 'bg-orange-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-database w-5 text-center"></i>
                    <span>Backup & Audit</span>
                </a>
                @endif
            </nav>

            <!-- User profile footer -->
            <div class="p-3 border-t border-slate-800 bg-slate-950 flex items-center justify-between">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-9 h-9 rounded-full bg-slate-700 flex items-center justify-center text-white font-bold flex-shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                        <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-orange-950 text-orange-400 border border-orange-800/50">
                            {{ auth()->user()->role?->name ?? 'Staff' }}
                        </span>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-400 hover:bg-slate-800 transition">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- Top Navbar -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 flex-shrink-0 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-bold text-slate-800">@yield('page_title', 'Dashboard')</h2>
                </div>

                <div class="flex items-center gap-3">
                    @if(auth()->user()->hasPermission('pos.billing'))
                    <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-sm font-semibold bg-orange-600 text-white hover:bg-orange-700 shadow-sm transition">
                        <i class="fa-solid fa-cash-register"></i>
                        <span>Go to POS</span>
                    </a>
                    @endif

                    @if(auth()->user()->hasPermission('dayclosing.view'))
                    <a href="{{ route('dayclosing.index') }}" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                        <i class="fa-solid fa-lock text-slate-500"></i>
                        <span>Cash Closing</span>
                    </a>
                    @endif

                    <div class="h-6 w-px bg-slate-200 mx-1"></div>

                    <div class="text-xs text-slate-500 font-medium">
                        <i class="fa-regular fa-clock mr-1"></i>
                        {{ date(\App\Models\Setting::get('date_format', 'd/m/Y')) }}
                    </div>
                </div>
            </header>

            <!-- Alerts / Toast Flash Messages -->
            <div class="px-6 pt-4">
                @if(session('success'))
                <div class="p-3.5 mb-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900"><i class="fa-solid fa-xmark"></i></button>
                </div>
                @endif

                @if(session('error'))
                <div class="p-3.5 mb-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600 text-base"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900"><i class="fa-solid fa-xmark"></i></button>
                </div>
                @endif

                @if($errors->any())
                <div class="p-3.5 mb-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-sm">
                    <div class="font-semibold mb-1 flex items-center gap-2">
                        <i class="fa-solid fa-circle-xmark text-rose-600"></i>
                        <span>Please correct the errors below:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs space-y-0.5 pl-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            <!-- Page Body -->
            <main class="flex-1 overflow-y-auto p-6 pt-2">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
