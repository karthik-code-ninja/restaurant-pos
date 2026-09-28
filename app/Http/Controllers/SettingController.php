<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        return view('settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Restaurant
            'restaurant_name' => ['required', 'string', 'max:150'],
            'restaurant_address' => ['required', 'string', 'max:500'],
            'restaurant_contact' => ['required', 'string', 'max:50'],
            'restaurant_email' => ['nullable', 'email', 'max:100'],
            'restaurant_gstin' => ['nullable', 'string', 'max:20'],
            'restaurant_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],

            // Invoice
            'invoice_prefix' => ['required', 'string', 'max:10'],
            'invoice_start_number' => ['required', 'integer', 'min:1'],

            // Printer & Receipt
            'printer_type' => ['required', 'in:58mm,80mm'],
            'auto_print' => ['nullable', 'boolean'],
            'receipt_header' => ['nullable', 'string', 'max:500'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'show_logo_on_receipt' => ['nullable', 'boolean'],
            'print_restaurant_copy' => ['nullable', 'boolean'],
            'print_customer_copy' => ['nullable', 'boolean'],

            // Tax
            'tax_type' => ['required', 'in:inclusive,exclusive'],
            'default_gst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'cgst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'sgst_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'igst_rate' => ['required', 'numeric', 'min:0', 'max:100'],

            // QR Code & UPI
            'upi_id' => ['nullable', 'string', 'max:100'],
            'upi_payee_name' => ['nullable', 'string', 'max:150'],
            'qr_code_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'show_qr_on_receipt' => ['nullable', 'boolean'],
            'qr_code_type' => ['nullable', 'in:dynamic_upi,uploaded_image'],

            // Payment Methods
            'enable_cash' => ['nullable', 'boolean'],
            'enable_upi' => ['nullable', 'boolean'],
            'enable_card' => ['nullable', 'boolean'],
            'enable_multimode' => ['nullable', 'boolean'],

            // General
            'currency_symbol' => ['required', 'string', 'max:10'],
            'currency_code' => ['required', 'string', 'max:10'],
            'date_format' => ['required', 'string', 'max:20'],
            'time_format' => ['required', 'string', 'max:20'],
        ]);

        if ($request->hasFile('restaurant_logo')) {
            $path = $request->file('restaurant_logo')->store('logos', 'public');
            Setting::set('restaurant_logo', $path, 'restaurant');
        }

        if ($request->hasFile('qr_code_image')) {
            $path = $request->file('qr_code_image')->store('qr_codes', 'public');
            Setting::set('qr_code_image', $path, 'receipt');
        }

        // Booleans
        $booleans = [
            'auto_print', 'show_logo_on_receipt', 'print_restaurant_copy', 'print_customer_copy',
            'show_qr_on_receipt', 'enable_cash', 'enable_upi', 'enable_card', 'enable_multimode'
        ];

        foreach ($booleans as $b) {
            Setting::set($b, $request->boolean($b) ? '1' : '0', 'general', 'boolean');
        }

        // All other text/numeric values
        foreach ($validated as $key => $val) {
            if ($key !== 'restaurant_logo' && $key !== 'qr_code_image' && !in_array($key, $booleans)) {
                Setting::set($key, (string) ($val ?? ''));
            }
        }

        AuditLog::log(
            action: 'settings_updated',
            module: 'settings',
            description: "System settings updated"
        );

        return back()->with('success', 'Restaurant and application settings updated successfully!');
    }
}
