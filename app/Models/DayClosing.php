<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_type', // day, shift
        'opened_at',
        'closed_at',
        'opening_cash',
        'cash_sales',
        'cash_expenses',
        'cash_withdrawals',
        'expected_cash',
        'actual_cash',
        'difference',
        'status', // open, closed
        'notes',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'cash_sales' => 'decimal:2',
            'cash_expenses' => 'decimal:2',
            'cash_withdrawals' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'actual_cash' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function openedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
