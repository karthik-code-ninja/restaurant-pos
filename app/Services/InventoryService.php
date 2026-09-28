<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Combo;
use App\Models\Food;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Deduct stock for a completed bill based on food ingredients.
     */
    public function deductStockForBill(Bill $bill): bool
    {
        if ($bill->is_stock_deducted) {
            return true; // Already deducted
        }

        if (in_array($bill->status, ['draft', 'held'])) {
            return false; // Do not deduct for draft or held
        }

        return DB::transaction(function () use ($bill) {
            $bill->loadMissing('items');
            $bill->unsetRelation('items');
            $bill->load('items');

            foreach ($bill->items as $item) {
                if ($item->item_type === 'combo') {
                    $combo = Combo::with('foods.ingredients')->find($item->item_id);
                    if ($combo) {
                        foreach ($combo->foods as $food) {
                            $comboFoodQty = (float) ($food->pivot->quantity ?? 1) * (float) $item->quantity;
                            $this->deductFoodIngredients($food, $comboFoodQty, $bill);
                        }
                    }
                } else {
                    $food = Food::with('ingredients')->find($item->item_id);
                    if ($food) {
                        $this->deductFoodIngredients($food, (float) $item->quantity, $bill);
                    }
                }
            }

            $bill->update(['is_stock_deducted' => true]);

            AuditLog::log(
                action: 'inventory_deducted',
                module: 'inventory',
                referenceId: (string) $bill->id,
                description: "Stock automatically deducted for Bill #{$bill->invoice_number}"
            );

            return true;
        });
    }

    /**
     * Reverse stock deduction when a bill is cancelled.
     */
    public function reverseStockForBill(Bill $bill, ?string $reason = null): bool
    {
        if (!$bill->is_stock_deducted) {
            return true; // Nothing to reverse
        }

        return DB::transaction(function () use ($bill, $reason) {
            $bill->loadMissing('items');
            $bill->unsetRelation('items');
            $bill->load('items');

            foreach ($bill->items as $item) {
                if ($item->item_type === 'combo') {
                    $combo = Combo::with('foods.ingredients')->find($item->item_id);
                    if ($combo) {
                        foreach ($combo->foods as $food) {
                            $comboFoodQty = (float) ($food->pivot->quantity ?? 1) * (float) $item->quantity;
                            $this->restoreFoodIngredients($food, $comboFoodQty, $bill, $reason);
                        }
                    }
                } else {
                    $food = Food::with('ingredients')->find($item->item_id);
                    if ($food) {
                        $this->restoreFoodIngredients($food, (float) $item->quantity, $bill, $reason);
                    }
                }
            }

            $bill->update(['is_stock_deducted' => false]);

            AuditLog::log(
                action: 'inventory_reversed',
                module: 'inventory',
                referenceId: (string) $bill->id,
                description: "Stock reversed for cancelled Bill #{$bill->invoice_number}. Reason: {$reason}"
            );

            return true;
        });
    }

    /**
     * Helper to deduct raw materials for a food item.
     */
    protected function deductFoodIngredients(Food $food, float $orderQuantity, Bill $bill): void
    {
        foreach ($food->ingredients as $ingredient) {
            $unitQty = (float) $ingredient->pivot->quantity;
            $totalDeduct = round($unitQty * $orderQuantity, 3);

            $item = InventoryItem::lockForUpdate()->find($ingredient->id);
            if ($item) {
                $prevStock = (float) $item->current_stock;
                $newStock = $prevStock - $totalDeduct;

                $item->update(['current_stock' => $newStock]);

                InventoryMovement::create([
                    'inventory_item_id' => $item->id,
                    'type' => 'sale_deduction',
                    'quantity' => -$totalDeduct,
                    'previous_stock' => $prevStock,
                    'current_stock' => $newStock,
                    'reference_id' => $bill->id,
                    'reference_type' => Bill::class,
                    'notes' => "Sale deduction for {$orderQuantity}x {$food->name} (Bill #{$bill->invoice_number})",
                    'user_id' => Auth::id() ?? $bill->cashier_id,
                ]);
            }
        }
    }

    /**
     * Helper to restore raw materials for a food item upon bill cancellation.
     */
    protected function restoreFoodIngredients(Food $food, float $orderQuantity, Bill $bill, ?string $reason): void
    {
        foreach ($food->ingredients as $ingredient) {
            $unitQty = (float) $ingredient->pivot->quantity;
            $totalRestore = round($unitQty * $orderQuantity, 3);

            $item = InventoryItem::lockForUpdate()->find($ingredient->id);
            if ($item) {
                $prevStock = (float) $item->current_stock;
                $newStock = $prevStock + $totalRestore;

                $item->update(['current_stock' => $newStock]);

                InventoryMovement::create([
                    'inventory_item_id' => $item->id,
                    'type' => 'cancellation_reversal',
                    'quantity' => $totalRestore,
                    'previous_stock' => $prevStock,
                    'current_stock' => $newStock,
                    'reference_id' => $bill->id,
                    'reference_type' => Bill::class,
                    'notes' => "Reversal from cancelled Bill #{$bill->invoice_number}. Reason: {$reason}",
                    'user_id' => Auth::id(),
                ]);
            }
        }
    }

    /**
     * Perform manual stock adjustment.
     */
    public function adjustStock(int $itemId, float $adjustmentQty, string $type, ?string $notes = null): InventoryItem
    {
        return DB::transaction(function () use ($itemId, $adjustmentQty, $type, $notes) {
            $item = InventoryItem::lockForUpdate()->findOrFail($itemId);
            $prevStock = (float) $item->current_stock;

            if ($type === 'adjustment_add') {
                $newStock = $prevStock + abs($adjustmentQty);
                $delta = abs($adjustmentQty);
            } else {
                $newStock = max(0, $prevStock - abs($adjustmentQty));
                $delta = -abs($adjustmentQty);
            }

            $item->update(['current_stock' => $newStock]);

            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => $type,
                'quantity' => $delta,
                'previous_stock' => $prevStock,
                'current_stock' => $newStock,
                'notes' => $notes,
                'user_id' => Auth::id(),
            ]);

            AuditLog::log(
                action: 'stock_adjusted',
                module: 'inventory',
                referenceId: (string) $item->id,
                oldValues: ['current_stock' => $prevStock],
                newValues: ['current_stock' => $newStock],
                description: "Manual stock adjustment: {$type} {$delta} {$item->unit} for {$item->name}"
            );

            return $item;
        });
    }
}
