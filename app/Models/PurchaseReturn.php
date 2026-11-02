<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_no',
        'return_date',
        'purchase_id',
        'supplier_id',
        'supplier_name',
        'total_amount',
        'refund_status',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateReturnNo(): string
    {
        $year = date('Y');
        $last = self::whereYear('created_at', $year)->latest('id')->first();
        $nextNumber = $last ? (((int) substr($last->return_no, -4)) + 1) : 1;

        return sprintf('PR-%s-%04d', $year, $nextNumber);
    }
}
