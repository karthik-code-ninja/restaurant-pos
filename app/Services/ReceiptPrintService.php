<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class ReceiptPrintService
{
    /**
     * Prepare full receipt data and register print/reprint/duplicate action.
     *
     * @param Bill $bill
     * @param string $printType 'print'|'reprint'|'duplicate'
     * @param string $copyType 'customer'|'restaurant'|'both'
     * @return array
     */
    public function prepareReceipt(Bill $bill, string $printType = 'print', string $copyType = 'customer'): array
    {
        $bill->loadMissing(['items.addons', 'payments.receivedByUser', 'table', 'cashier']);

        // Update counters and audit log
        if ($printType === 'reprint') {
            $bill->increment('reprinted_count');
            AuditLog::log(
                action: 'bill_reprinted',
                module: 'pos',
                referenceId: (string) $bill->id,
                description: "Reprinted Bill #{$bill->invoice_number} ({$copyType} copy)"
            );
        } elseif ($printType === 'duplicate') {
            $bill->increment('duplicate_count');
            AuditLog::log(
                action: 'bill_duplicate',
                module: 'pos',
                referenceId: (string) $bill->id,
                description: "Printed Duplicate Bill #{$bill->invoice_number} ({$copyType} copy)"
            );
        } else {
            $bill->increment('printed_count');
            AuditLog::log(
                action: 'bill_printed',
                module: 'pos',
                referenceId: (string) $bill->id,
                description: "Printed Bill #{$bill->invoice_number} ({$copyType} copy)"
            );
        }

        $printerType = Setting::get('printer_type', '80mm'); // 58mm or 80mm
        $currency = Setting::get('currency_symbol', '₹');
        $dateFormat = Setting::get('date_format', 'd/m/Y');
        $timeFormat = Setting::get('time_format', 'h:i A');

        $showQr = (bool) Setting::get('show_qr_on_receipt', false);
        $qrType = Setting::get('qr_code_type', 'dynamic_upi');
        $upiId = trim((string) Setting::get('upi_id', ''));
        $upiPayee = trim((string) Setting::get('upi_payee_name', Setting::get('restaurant_name', '')));
        $qrImage = Setting::get('qr_code_image', '');

        $upiUri = '';
        if ($upiId) {
            $amt = number_format($bill->grand_total, 2, '.', '');
            $upiUri = "upi://pay?pa={$upiId}&pn=" . urlencode($upiPayee) . "&am={$amt}&cu=INR&tn=" . urlencode("Bill " . $bill->invoice_number);
        }

        return [
            'printer_type' => $printerType,
            'print_type' => $printType,
            'copy_type' => $copyType,
            'restaurant' => [
                'name' => Setting::get('restaurant_name', 'Restaurant Name'),
                'address' => Setting::get('restaurant_address', ''),
                'contact' => Setting::get('restaurant_contact', ''),
                'email' => Setting::get('restaurant_email', ''),
                'gstin' => Setting::get('restaurant_gstin', ''),
                'logo' => Setting::get('restaurant_logo', ''),
                'show_logo' => (bool) Setting::get('show_logo_on_receipt', false),
            ],
            'receipt' => [
                'header' => Setting::get('receipt_header', ''),
                'footer' => Setting::get('receipt_footer', ''),
            ],
            'qr' => [
                'show_qr' => $showQr,
                'qr_type' => $qrType,
                'upi_id' => $upiId,
                'upi_payee' => $upiPayee,
                'upi_uri' => $upiUri,
                'image' => $qrImage,
            ],
            'bill' => [
                'id' => $bill->id,
                'invoice_number' => $bill->invoice_number,
                'date' => $bill->created_at->format($dateFormat),
                'time' => $bill->created_at->format($timeFormat),
                'cashier' => $bill->cashier?->name ?? 'Staff',
                'waiter' => $bill->waiter_name ?: ($bill->waiter?->name ?? null),
                'order_type' => ucfirst($bill->order_type),
                'table_number' => $bill->table?->table_number,
                'table_name' => $bill->table?->name,
                'customer_name' => $bill->customer_name,
                'customer_phone' => $bill->customer_phone,
                'subtotal' => $bill->subtotal,
                'discount_amount' => $bill->discount_amount,
                'discount_type' => $bill->discount_type,
                'discount_value' => $bill->discount_value,
                'tax_total' => $bill->tax_total,
                'cgst_total' => $bill->cgst_total,
                'sgst_total' => $bill->sgst_total,
                'igst_total' => $bill->igst_total,
                'rounding_difference' => $bill->rounding_difference,
                'grand_total' => $bill->grand_total,
                'printed_count' => $bill->printed_count,
                'reprinted_count' => $bill->reprinted_count,
                'duplicate_count' => $bill->duplicate_count,
                'status' => $bill->status,
                'currency' => $currency,
            ],
            'items' => $bill->items->map(function ($item) {
                return [
                    'code' => $item->food_code,
                    'hsn_code' => $item->hsn_code,
                    'name' => $item->item_name,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                    'tax_rate' => $item->tax_rate,
                    'tax_amount' => $item->tax_amount,
                    'total' => $item->total,
                    'notes' => $item->notes,
                    'addons' => $item->addons->map(fn ($a) => [
                        'name' => $a->name,
                        'price' => $a->price,
                        'total' => $a->total,
                    ])->toArray(),
                ];
            })->toArray(),
            'payments' => $bill->payments->map(function ($p) {
                return [
                    'method' => strtoupper($p->payment_method),
                    'amount' => $p->amount,
                    'reference' => $p->reference_number,
                ];
            })->toArray(),
        ];
    }
}
