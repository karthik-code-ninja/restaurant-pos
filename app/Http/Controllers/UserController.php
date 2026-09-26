<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('role');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $roles = Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = $request->boolean('status', true);

        $user = User::create($validated);

        AuditLog::log(
            action: 'user_created',
            module: 'users',
            referenceId: (string) $user->id,
            description: "User {$user->name} ({$user->email}) created with role {$user->role?->name}"
        );

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role_id' => ['required', 'exists:roles,id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'boolean'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['status'] = $request->boolean('status');

        $old = $user->only(['name', 'email', 'role_id', 'phone', 'status']);
        $user->update($validated);

        AuditLog::log(
            action: 'user_updated',
            module: 'users',
            referenceId: (string) $user->id,
            oldValues: $old,
            newValues: $validated,
            description: "User {$user->name} ({$user->email}) updated"
        );

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update(['status' => !$user->status]);

        $statusStr = $user->status ? 'activated' : 'deactivated';

        AuditLog::log(
            action: 'user_status_changed',
            module: 'users',
            referenceId: (string) $user->id,
            description: "User {$user->name} was {$statusStr}"
        );

        return back()->with('success', "User {$statusStr} successfully!");
    }

    public function loginHistory(Request $request): View
    {
        $query = LoginLog::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->latest('login_at')->paginate(20)->withQueryString();
        $users = User::orderBy('name')->get();

        return view('users.login_history', compact('logs', 'users'));
    }
}
