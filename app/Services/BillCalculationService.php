<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\Combo;
use App\Models\Food;
use App\Models\Setting;

class BillCalculationService
{
    /**
     * Calculate bill totals from raw cart items array.
     *
     * @param array $cartItems Array of items:
     *   [
     *     'item_type' => 'food'|'combo',
     *     'item_id' => int,
     *     'quantity' => float|int,
     *     'notes' => string|null,
     *     'addons' => [addon_id, ...]
     *   ]
     * @param string $discountType 'fixed'|'percentage'
     * @param float $discountValue
     * @return array Calculated breakdown
     */
    public function calculate(array $cartItems, string $discountType = 'fixed', float $discountValue = 0): array
    {
        $taxType = Setting::get('tax_type', 'exclusive'); // exclusive or inclusive
        $defaultGstRate = (float) Setting::get('default_gst_rate', 5.00);
        $cgstShare = (float) Setting::get('cgst_rate', 2.50);
        $sgstShare = (float) Setting::get('sgst_rate', 2.50);

        $processedItems = [];
        $subtotal = 0.0;
        $totalTax = 0.0;
        $totalCgst = 0.0;
        $totalSgst = 0.0;
        $totalIgst = 0.0;

        $totalItemDiscounts = 0.0;

        foreach ($cartItems as $cartItem) {
            $type = $cartItem['item_type'] ?? 'food';
            $itemId = (int) ($cartItem['item_id'] ?? 0);
            $qty = max(1, (float) ($cartItem['quantity'] ?? 1));
            $notes = $cartItem['notes'] ?? null;
            $itemDiscType = ($cartItem['discount_type'] ?? 'fixed') === 'percentage' ? 'percentage' : 'fixed';
            $itemDiscVal = max(0, (float) ($cartItem['discount_value'] ?? 0));

            if ($type === 'combo') {
                $combo = Combo::find($itemId);
                if (!$combo || !$combo->is_active) {
                    continue;
                }
                $name = $combo->name;
                $code = $combo->code;
                $unitPrice = (float) $combo->price;
                $taxRate = (float) ($combo->tax_rate ?? $defaultGstRate);
                $addonsData = [];
            } else {
                $food = Food::find($itemId);
                if (!$food || !$food->is_active) {
                    continue;
                }
                $name = $food->name;
                $code = $food->code;
                $hsnCode = $food->hsn_code;
                $unitPrice = (float) $food->price;
                $taxRate = (float) ($food->tax_rate ?? $defaultGstRate);

                // Process Add-ons
                $addonsData = [];
                if (!empty($cartItem['addons']) && is_array($cartItem['addons'])) {
                    $addons = AddOn::whereIn('id', $cartItem['addons'])->where('is_active', true)->get();
                    foreach ($addons as $addon) {
                        $addonPrice = (float) $addon->price;
                        $addonTaxRate = (float) ($addon->tax_rate ?? $taxRate);
                        $addonSubtotal = round($addonPrice * $qty, 2);

                        if ($taxType === 'inclusive') {
                            $addonTax = round($addonSubtotal - ($addonSubtotal / (1 + ($addonTaxRate / 100))), 2);
                        } else {
                            $addonTax = round($addonSubtotal * ($addonTaxRate / 100), 2);
                        }

                        $addonsData[] = [
                            'add_on_id' => $addon->id,
                            'name' => $addon->name,
                            'price' => $addonPrice,
                            'tax_rate' => $addonTaxRate,
                            'tax_amount' => $addonTax,
                            'total' => $addonSubtotal,
                        ];
                    }
                }
            }

            $itemSubtotal = round($unitPrice * $qty, 2);
            $addonSubtotalSum = array_sum(array_column($addonsData, 'total'));
            $lineGross = round($itemSubtotal + $addonSubtotalSum, 2);

            // Item-level discount calculation
            if ($itemDiscType === 'percentage') {
                $itemDiscAmount = round(($lineGross * min(100, $itemDiscVal)) / 100, 2);
            } else {
                $itemDiscAmount = round(min($lineGross, $itemDiscVal), 2);
            }
            $lineTaxable = max(0, round($lineGross - $itemDiscAmount, 2));
            $totalItemDiscounts += $itemDiscAmount;

            // Item-wise Tax calculation
            if ($taxType === 'inclusive') {
                $itemTax = round($lineTaxable - ($lineTaxable / (1 + ($taxRate / 100))), 2);
                $itemCgst = round($itemTax * ($cgstShare / max(0.01, $taxRate)), 2);
                $itemSgst = round($itemTax - $itemCgst, 2);
                $lineTotal = $lineTaxable;
            } else {
                $itemTax = round($lineTaxable * ($taxRate / 100), 2);
                $itemCgst = round($itemTax * ($cgstShare / max(0.01, $taxRate)), 2);
                $itemSgst = round($itemTax - $itemCgst, 2);
                $lineTotal = round($lineTaxable + $itemTax, 2);
            }

            $subtotal += $lineGross;
            $totalTax += $itemTax;
            $totalCgst += $itemCgst;
            $totalSgst += $itemSgst;

            $processedItems[] = [
                'item_type' => $type,
                'item_id' => $itemId,
                'food_code' => $code,
                'hsn_code' => $hsnCode ?? null,
                'item_name' => $name,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'discount_type' => $itemDiscType,
                'discount_value' => $itemDiscVal,
                'discount_amount' => $itemDiscAmount,
                'subtotal' => $lineGross,
                'tax_rate' => $taxRate,
                'tax_amount' => $itemTax,
                'cgst_amount' => $itemCgst,
                'sgst_amount' => $itemSgst,
                'igst_amount' => 0.00,
                'total' => $lineTotal,
                'notes' => $notes,
                'addons' => $addonsData,
            ];
        }

        // Bill-level Discount calculation
        $billDiscountAmount = 0.0;
        $remainingSubtotal = max(0, $subtotal - $totalItemDiscounts);
        if ($discountType === 'percentage') {
            $pct = max(0, min(100, $discountValue));
            $billDiscountAmount = round(($remainingSubtotal * $pct) / 100, 2);
        } else {
            $billDiscountAmount = round(max(0, min($remainingSubtotal, $discountValue)), 2);
        }

        $totalDiscountAmount = round($totalItemDiscounts + $billDiscountAmount, 2);

        // Apply bill-level discount proportionally to taxes if tax is exclusive
        if ($remainingSubtotal > 0 && $billDiscountAmount > 0 && $taxType === 'exclusive') {
            $discountRatio = ($remainingSubtotal - $billDiscountAmount) / $remainingSubtotal;
            $totalTax = round($totalTax * $discountRatio, 2);
            $totalCgst = round($totalTax * ($cgstShare / max(0.01, $defaultGstRate)), 2);
            $totalSgst = round($totalTax - $totalCgst, 2);
        }

        if ($taxType === 'exclusive') {
            $rawGrandTotal = max(0, ($subtotal - $totalDiscountAmount) + $totalTax);
        } else {
            $rawGrandTotal = max(0, $subtotal - $totalDiscountAmount);
        }

        $roundedGrandTotal = round($rawGrandTotal);
        $roundingDiff = round($roundedGrandTotal - $rawGrandTotal, 2);

        return [
            'items' => $processedItems,
            'items_count' => count($processedItems),
            'total_quantity' => array_sum(array_column($processedItems, 'quantity')),
            'subtotal' => round($subtotal, 2),
            'item_discounts_total' => round($totalItemDiscounts, 2),
            'discount_type' => $discountType,
            'discount_value' => round($discountValue, 2),
            'discount_amount' => $totalDiscountAmount,
            'tax_type' => $taxType,
            'tax_total' => round($totalTax, 2),
            'cgst_total' => round($totalCgst, 2),
            'sgst_total' => round($totalSgst, 2),
            'igst_total' => 0.00,
            'rounding_difference' => $roundingDiff,
            'grand_total' => round($roundedGrandTotal, 2),
        ];
    }
}
