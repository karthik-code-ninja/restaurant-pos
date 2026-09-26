<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ \App\Models\Setting::get('restaurant_name', 'Restaurant Billing & POS') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo / Brand Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-gradient-to-tr from-orange-600 to-amber-500 rounded-2xl flex items-center justify-center text-white text-3xl mx-auto shadow-xl shadow-orange-600/30 mb-4">
                <i class="fa-solid fa-utensils"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-white">{{ \App\Models\Setting::get('restaurant_name', 'Restro Restaurant') }}</h1>
            <p class="text-sm text-slate-400 mt-1">Restaurant Billing & POS System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border border-slate-100">
            <h2 class="text-xl font-bold text-slate-800 mb-1">Welcome Back</h2>
            <p class="text-xs text-slate-500 mb-6">Enter your cashier or admin credentials to continue</p>

            @if(session('success'))
            <div class="p-3 mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
            @endif

            @if($errors->any())
            <div class="p-3 mb-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                @foreach($errors->all() as $err)
                <p>{{ $err }}</p>
                @endforeach
            </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Email Address</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-solid fa-envelope text-sm"></i>
                        </span>
                        <input type="email" name="email" id="email" value="{{ old('email', 'admin@restaurant.com') }}" required autofocus
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-solid fa-lock text-sm"></i>
                        </span>
                        <input type="password" name="password" id="password" value="password123" required
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <span>Remember me</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-500 hover:to-amber-500 text-white font-bold text-sm shadow-lg shadow-orange-600/30 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Sign In to POS</span>
                </button>
            </form>

            <!-- Quick Login Switcher for Testing -->
            <div class="mt-6 pt-5 border-t border-slate-100">
                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2 text-center">Quick Switch Demo Accounts</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" onclick="fillCreds('admin@restaurant.com', 'password123')" class="p-2 bg-slate-50 hover:bg-orange-50 hover:border-orange-300 border border-slate-200 rounded-lg text-left transition">
                        <span class="font-bold text-slate-800 block">Admin</span>
                        <span class="text-[10px] text-slate-500">Full System</span>
                    </button>
                    <button type="button" onclick="fillCreds('cashier@restaurant.com', 'password123')" class="p-2 bg-slate-50 hover:bg-orange-50 hover:border-orange-300 border border-slate-200 rounded-lg text-left transition">
                        <span class="font-bold text-slate-800 block">Cashier</span>
                        <span class="text-[10px] text-slate-500">POS & Billing</span>
                    </button>
                    <button type="button" onclick="fillCreds('manager@restaurant.com', 'password123')" class="p-2 bg-slate-50 hover:bg-orange-50 hover:border-orange-300 border border-slate-200 rounded-lg text-left transition">
                        <span class="font-bold text-slate-800 block">Manager</span>
                        <span class="text-[10px] text-slate-500">Reports & Menu</span>
                    </button>
                    <button type="button" onclick="fillCreds('staff@restaurant.com', 'password123')" class="p-2 bg-slate-50 hover:bg-orange-50 hover:border-orange-300 border border-slate-200 rounded-lg text-left transition">
                        <span class="font-bold text-slate-800 block">Staff</span>
                        <span class="text-[10px] text-slate-500">Floor & Tables</span>
                    </button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">Restro Technologies &copy; {{ date('Y') }}. All rights reserved.</p>
    </div>

    <script>
        function fillCreds(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
