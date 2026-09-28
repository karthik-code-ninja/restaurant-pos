<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'item_type', // food, combo
        'item_id',
        'food_code',
        'hsn_code',
        'item_name',
        'unit_price',
        'quantity',
        'kot_printed_qty',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'decimal:2',
            'kot_printed_qty' => 'decimal:3',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class, 'item_id');
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class, 'item_id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BillItemAddon::class);
    }
}
