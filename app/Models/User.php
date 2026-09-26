<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'phone',
        'status',
        'last_login_at',
        'last_logout_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_logout_at' => 'datetime',
            'password' => 'hashed',
            'status' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'cashier_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function dayClosings(): HasMany
    {
        return $this->hasMany(DayClosing::class);
    }

    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    public function hasRole(string|array $roles): bool
    {
        if (!$this->relationLoaded('role')) {
            $this->load('role');
        }

        if (!$this->role) {
            if ($this->id === 1 || str_contains(strtolower($this->email ?? ''), 'admin')) {
                return is_array($roles) ? in_array('admin', $roles) : $roles === 'admin';
            }
            return false;
        }

        if (is_array($roles)) {
            return in_array($this->role->slug, $roles);
        }

        return $this->role->slug === $roles;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        // 1. Super Admin (Admin role, user ID 1, or admin email) has all permissions
        if ($this->hasRole('admin') || $this->id === 1 || str_contains(strtolower($this->email ?? ''), 'admin')) {
            return true;
        }

        // 2. Dashboard overview is accessible to all authenticated restaurant staff
        if ($permissionSlug === 'dashboard.view') {
            return true;
        }

        if (!$this->relationLoaded('role')) {
            $this->load('role.permissions');
        } elseif ($this->role && !$this->role->relationLoaded('permissions')) {
            $this->role->load('permissions');
        }

        if (!$this->role) {
            // Default permissions for users without an explicit role assigned
            return in_array($permissionSlug, ['dashboard.view', 'pos.billing', 'table.view']);
        }

        return $this->role->permissions ? $this->role->permissions->contains('slug', $permissionSlug) : false;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
