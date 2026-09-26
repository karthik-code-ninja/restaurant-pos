<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->authenticatedRedirect(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->status) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Your account is deactivated. Please contact the administrator.',
                ]);
            }

            $request->session()->regenerate();

            // Track login
            $user->update(['last_login_at' => now()]);

            LoginLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'login_at' => now(),
            ]);

            AuditLog::log(
                action: 'user_login',
                module: 'auth',
                referenceId: (string) $user->id,
                description: "User {$user->name} ({$user->email}) logged in successfully"
            );

            return $this->authenticatedRedirect($user);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            $user->update(['last_logout_at' => now()]);

            // Update latest login log
            $latestLog = LoginLog::where('user_id', $user->id)->whereNull('logout_at')->latest()->first();
            if ($latestLog) {
                $latestLog->update(['logout_at' => now()]);
            }

            AuditLog::log(
                action: 'user_logout',
                module: 'auth',
                referenceId: (string) $user->id,
                description: "User {$user->name} logged out"
            );
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }

    protected function authenticatedRedirect(User $user): RedirectResponse
    {
        if ($user->hasRole('cashier')) {
            return redirect()->intended(route('pos.index'));
        }

        if ($user->hasPermission('dashboard.view')) {
            return redirect()->intended(route('dashboard'));
        }

        return redirect()->intended(route('pos.index'));
    }
}
