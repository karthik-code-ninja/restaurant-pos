<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->status) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => 'Account is inactive. Please contact administrator.'], 403);
            }
            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Please contact administrator.']);
        }

        if (!$user->hasPermission($permission)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized action. You do not have the required permission: ' . $permission
                ], 403);
            }

            // If user attempted to access dashboard but lacks permission, route to POS
            if ($permission === 'dashboard.view') {
                return redirect()->route('pos.index')->with('warning', 'Redirected to POS billing terminal.');
            }

            // If previous page is available and safe, redirect back with error message
            $previous = url()->previous();
            if ($previous && $previous !== $request->fullUrl() && !str_contains($previous, '/login')) {
                return redirect()->back()->with('error', 'You do not have permission to perform that action (' . $permission . ').');
            }

            return redirect()->route('pos.index')->with('error', 'You do not have permission to access that section.');
        }

        return $next($request);
    }
}
