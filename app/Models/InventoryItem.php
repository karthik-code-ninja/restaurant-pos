<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'opening_stock',
        'current_stock',
        'min_stock_alert',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_stock' => 'decimal:3',
            'current_stock' => 'decimal:3',
            'min_stock_alert' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function foods(): BelongsToMany
    {
        return $this->belongsToMany(Food::class, 'food_ingredients')
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock_alert;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock_alert');
    }
}
