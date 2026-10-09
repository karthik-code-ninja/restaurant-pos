<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'gstin',
        'credit_limit',
        'balance', // Positive = Outstanding Due, Negative = Advance Payment
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'balance' => 'decimal:2',
            'status' => 'boolean',
        ];
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(CustomerLedger::class)->orderBy('entry_date', 'asc')->orderBy('id', 'asc');
    }

    public function latestLedgers(): HasMany
    {
        return $this->hasMany(CustomerLedger::class)->latest('entry_date')->latest('id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeDue($query)
    {
        return $query->where('balance', '>', 0.001);
    }

    public function scopeAdvance($query)
    {
        return $query->where('balance', '<', -0.001);
    }

    public function scopeZeroBalance($query)
    {
        return $query->whereBetween('balance', [-0.001, 0.001]);
    }

    public function isDue(): bool
    {
        return (float) $this->balance > 0.001;
    }

    public function isAdvance(): bool
    {
        return (float) $this->balance < -0.001;
    }

    public function getFormattedBalanceAttribute(): string
    {
        $bal = (float) $this->balance;
        if ($bal > 0.001) {
            return 'Due: ₹'.number_format($bal, 2);
        } elseif ($bal < -0.001) {
            return 'Advance: ₹'.number_format(abs($bal), 2);
        }

        return 'Clear: ₹0.00';
    }
}
