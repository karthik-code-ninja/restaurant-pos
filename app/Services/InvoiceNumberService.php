<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    /**
     * Generate unique sequential invoice number.
     */
    public function generate(): string
    {
        $prefix = Setting::get('invoice_prefix', 'INV-');
        $startNum = (int) Setting::get('invoice_start_number', 1001);
        $dateStr = date('Ymd');

        return DB::transaction(function () use ($prefix, $startNum, $dateStr) {
            $lastBill = Bill::withTrashed()
                ->where('invoice_number', 'like', "{$prefix}{$dateStr}-%")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastBill) {
                // Extract last number
                $parts = explode('-', $lastBill->invoice_number);
                $lastSequence = (int) end($parts);
                $nextSequence = $lastSequence + 1;
            } else {
                $countToday = Bill::withTrashed()->whereDate('created_at', today())->count();
                $nextSequence = max($startNum, $countToday + 1);
            }

            $formattedNum = str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
            $candidate = "{$prefix}{$dateStr}-{$formattedNum}";

            // Guarantee uniqueness
            $exists = Bill::withTrashed()->where('invoice_number', $candidate)->exists();
            if ($exists) {
                $maxId = (int) Bill::withTrashed()->max('id') + 1;
                $candidate = "{$prefix}{$dateStr}-" . str_pad((string) ($startNum + $maxId), 4, '0', STR_PAD_LEFT);
            }

            return $candidate;
        });
    }
}
