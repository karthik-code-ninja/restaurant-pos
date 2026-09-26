@extends('layouts.app')

@section('title', 'System Settings')
@section('page_title', 'Restaurant & System Configuration')

@section('content')
<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6 max-w-5xl">
    @csrf

    <!-- Form Submit Top Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-slate-800">Application Configuration</h2>
            <p class="text-xs text-slate-500">Manage billing rules, thermal receipt format, taxes, and profile</p>
        </div>
        <button type="submit" class="px-5 py-2 bg-slate-900 text-white rounded-lg text-xs font-bold hover:bg-slate-800 flex items-center gap-2 shadow-sm transition">
            <i class="fa-solid fa-floppy-disk"></i> Save Settings
        </button>
    </div>

    <!-- 1. Restaurant Profile -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-store"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-800">Restaurant Identity</h3>
                <p class="text-[11px] text-slate-400">Used on POS receipts, customer invoices, and reports</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Restaurant / Shop Name *</label>
                <input type="text" name="restaurant_name" value="{{ old('restaurant_name', $settings['restaurant_name'] ?? '') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Contact Phone / Mobile *</label>
                <input type="text" name="restaurant_contact" value="{{ old('restaurant_contact', $settings['restaurant_contact'] ?? '') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                <input type="email" name="restaurant_email" value="{{ old('restaurant_email', $settings['restaurant_email'] ?? '') }}" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">GSTIN / Tax ID Number</label>
                <input type="text" name="restaurant_gstin" value="{{ old('restaurant_gstin', $settings['restaurant_gstin'] ?? '') }}" placeholder="e.g. 29ABCDE1234F1Z5" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-mono focus:ring-2 focus:ring-slate-900">
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Physical Address *</label>
                <textarea name="restaurant_address" rows="2" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">{{ old('restaurant_address', $settings['restaurant_address'] ?? '') }}</textarea>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Restaurant Logo</label>
                <input type="file" name="restaurant_logo" accept="image/png,image/jpeg,image/webp" class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                @if(!empty($settings['restaurant_logo']))
                    <p class="text-[11px] text-slate-500 mt-1">Current logo: <span class="font-mono text-slate-700">{{ $settings['restaurant_logo'] }}</span></p>
                @endif
            </div>
        </div>
    </div>

    <!-- 2. Invoice & Numbering -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-800">Invoice Numbering Sequence</h3>
                <p class="text-[11px] text-slate-400">Controls automated bill invoice prefix and start numbering</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Invoice Prefix *</label>
                <input type="text" name="invoice_prefix" value="{{ old('invoice_prefix', $settings['invoice_prefix'] ?? 'INV') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-mono uppercase focus:ring-2 focus:ring-slate-900">
                <p class="text-[10px] text-slate-400 mt-1">Preview: {{ $settings['invoice_prefix'] ?? 'INV' }}-{{ date('Ymd') }}-0001</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Sequence Start Number *</label>
                <input type="number" name="invoice_start_number" value="{{ old('invoice_start_number', $settings['invoice_start_number'] ?? '1') }}" min="1" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>
        </div>
    </div>

    <!-- 3. Thermal Printer & POS Receipt -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-print"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-800">Thermal Printer & Receipt Template</h3>
                <p class="text-[11px] text-slate-400">Hardware paper width, automatic printing, and receipt headers</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt Paper Roll Width *</label>
                <select name="printer_type" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
                    <option value="80mm" {{ ($settings['printer_type'] ?? '') === '80mm' ? 'selected' : '' }}>80mm Standard POS Thermal Paper</option>
                    <option value="58mm" {{ ($settings['printer_type'] ?? '') === '58mm' ? 'selected' : '' }}>58mm Compact / Mobile Thermal Paper</option>
                </select>
            </div>

            <div class="space-y-2 pt-5">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="auto_print" value="1" {{ !empty($settings['auto_print']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span class="text-xs font-medium text-slate-700">Auto-prompt print receipt on bill settlement</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="show_logo_on_receipt" value="1" {{ !empty($settings['show_logo_on_receipt']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span class="text-xs font-medium text-slate-700">Display logo on printed receipt header</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Custom Receipt Header Message</label>
                <textarea name="receipt_header" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900" placeholder="e.g. Welcome to Royal Dine Family Restaurant">{{ old('receipt_header', $settings['receipt_header'] ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Custom Receipt Footer Message</label>
                <textarea name="receipt_footer" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900" placeholder="e.g. Thank you for dining with us! Please visit again.">{{ old('receipt_footer', $settings['receipt_footer'] ?? '') }}</textarea>
            </div>

            <div class="md:col-span-2 flex flex-wrap gap-6 pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="print_restaurant_copy" value="1" {{ !empty($settings['print_restaurant_copy']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span class="text-xs font-medium text-slate-700">Allow Kitchen / Restaurant Copy generation</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="print_customer_copy" value="1" {{ !empty($settings['print_customer_copy']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span class="text-xs font-medium text-slate-700">Allow Customer Copy generation</span>
                </label>
            </div>
        </div>
    </div>

    <!-- 4. Tax & GST Rules -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-percent"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">GST & Tax Calculation Rules</h3>
                    <p class="text-[11px] text-slate-400">Configure intra-state (CGST + SGST) or inter-state (IGST) tax rates</p>
                </div>
            </div>
            <a href="{{ route('tax.index') }}" class="text-xs font-semibold text-sky-600 hover:text-sky-800 flex items-center gap-1">
                Tax Breakdown <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Pricing Tax Mode *</label>
                <select name="tax_type" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
                    <option value="exclusive" {{ ($settings['tax_type'] ?? '') === 'exclusive' ? 'selected' : '' }}>Exclusive (Tax added on top of food price)</option>
                    <option value="inclusive" {{ ($settings['tax_type'] ?? '') === 'inclusive' ? 'selected' : '' }}>Inclusive (Menu price already includes tax)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Default Total GST Rate (%) *</label>
                <input type="number" step="0.01" name="default_gst_rate" value="{{ old('default_gst_rate', $settings['default_gst_rate'] ?? '5') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">CGST Rate (%) *</label>
                <input type="number" step="0.01" name="cgst_rate" value="{{ old('cgst_rate', $settings['cgst_rate'] ?? '2.5') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">SGST Rate (%) *</label>
                <input type="number" step="0.01" name="sgst_rate" value="{{ old('sgst_rate', $settings['sgst_rate'] ?? '2.5') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">IGST Rate (%) *</label>
                <input type="number" step="0.01" name="igst_rate" value="{{ old('igst_rate', $settings['igst_rate'] ?? '5') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
            </div>
        </div>
    </div>

    <!-- 5. Payment Methods & Formats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Payment Options -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Supported Payment Modes</h3>
                    <p class="text-[11px] text-slate-400">Available payment options on POS terminal</p>
                </div>
            </div>

            <div class="space-y-3 pt-2">
                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="enable_cash" value="1" {{ !empty($settings['enable_cash']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <div>
                        <p class="text-xs font-bold text-slate-800">Cash Payment</p>
                        <p class="text-[10px] text-slate-400">Cash tendered & change return calculator</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="enable_upi" value="1" {{ !empty($settings['enable_upi']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <div>
                        <p class="text-xs font-bold text-slate-800">UPI / QR Code</p>
                        <p class="text-[10px] text-slate-400">GPay, PhonePe, Paytm, BHIM</p>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="enable_card" value="1" {{ !empty($settings['enable_card']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <div>
                        <p class="text-xs font-bold text-slate-800">Debit / Credit Card</p>
                        <p class="text-[10px] text-slate-400">Swipe / POS EDC terminal transactions</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Localization & Formats -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-globe"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Currency & Formatting</h3>
                    <p class="text-[11px] text-slate-400">Symbols and date formats</p>
                </div>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Currency Symbol *</label>
                        <input type="text" name="currency_symbol" value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '₹') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Currency Code *</label>
                        <input type="text" name="currency_code" value="{{ old('currency_code', $settings['currency_code'] ?? 'INR') }}" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs uppercase focus:ring-2 focus:ring-slate-900">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Date Format *</label>
                        <select name="date_format" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
                            <option value="d-m-Y" {{ ($settings['date_format'] ?? '') === 'd-m-Y' ? 'selected' : '' }}>DD-MM-YYYY</option>
                            <option value="Y-m-d" {{ ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                            <option value="d/m/Y" {{ ($settings['date_format'] ?? '') === 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Time Format *</label>
                        <select name="time_format" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
                            <option value="12h" {{ ($settings['time_format'] ?? '') === '12h' ? 'selected' : '' }}>12-Hour (02:30 PM)</option>
                            <option value="24h" {{ ($settings['time_format'] ?? '') === '24h' ? 'selected' : '' }}>24-Hour (14:30)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Save button -->
    <div class="flex justify-end pt-2">
        <button type="submit" class="px-6 py-2.5 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 flex items-center gap-2 shadow-sm transition">
            <i class="fa-solid fa-floppy-disk"></i> Save All Settings
        </button>
    </div>
</form>
@endsection
