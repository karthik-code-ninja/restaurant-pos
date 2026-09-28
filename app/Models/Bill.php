<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bill extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'order_type', // table, counter
        'table_id',
        'cashier_id',
        'waiter_id',
        'waiter_name',
        'status', // draft, held, pending, completed, cancelled
        'customer_name',
        'customer_phone',
        'subtotal',
        'discount_type', // fixed, percentage
        'discount_value',
        'discount_amount',
        'tax_total',
        'cgst_total',
        'sgst_total',
        'igst_total',
        'rounding_difference',
        'grand_total',
        'notes',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'printed_count',
        'reprinted_count',
        'duplicate_count',
        'kot_count',
        'is_stock_deducted',
        'parent_bill_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'cgst_total' => 'decimal:2',
            'sgst_total' => 'decimal:2',
            'igst_total' => 'decimal:2',
            'rounding_difference' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'printed_count' => 'integer',
            'reprinted_count' => 'integer',
            'duplicate_count' => 'integer',
            'kot_count' => 'integer',
            'is_stock_deducted' => 'boolean',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function parentBill(): BelongsTo
    {
        return $this->belongsTo(Bill::class, 'parent_bill_id');
    }

    public function childBills(): HasMany
    {
        return $this->hasMany(Bill::class, 'parent_bill_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function remainingBalance(): float
    {
        return max(0, (float) $this->grand_total - $this->paidAmount());
    }

    public function isPaid(): bool
    {
        return $this->remainingBalance() <= 0.001;
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeHeld($query)
    {
        return $query->where('status', 'held');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
