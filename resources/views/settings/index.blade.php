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

    <!-- 3. Hardware Printer Profiles & Purpose Routing -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
        <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-print"></i>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Hardware Printer Profiles & Purpose Routing</h3>
                    <p class="text-[11px] text-slate-400">Connect multiple physical thermal printers and set purpose: Kitchen (KOT), Cashier Counter, or Bar</p>
                </div>
            </div>
            <button type="button" onclick="openAddPrinterModal()" class="px-3.5 py-1.5 bg-orange-600 hover:bg-orange-700 active:scale-95 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 shadow-sm transition">
                <i class="fa-solid fa-plus"></i>
                <span>Add Printer Profile</span>
            </button>
        </div>

        <!-- Profiles List Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="printerProfilesContainer">
            @forelse($printerProfiles as $prof)
            <div class="border {{ !empty($prof['is_active']) ? 'border-slate-200 bg-white' : 'border-slate-200/60 bg-slate-50/50 opacity-70' }} rounded-xl p-4 flex flex-col justify-between shadow-xs hover:border-orange-300 transition">
                <div>
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm flex items-center gap-1.5">
                                <i class="fa-solid fa-receipt text-slate-400 text-xs"></i>
                                {{ $prof['name'] }}
                            </h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $prof['notes'] ?: 'No location notes' }}</p>
                        </div>

                        <!-- Purpose Badge -->
                        @if(($prof['purpose'] ?? '') === 'kitchen')
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 flex items-center gap-1 shrink-0">
                                <i class="fa-solid fa-utensils"></i> Kitchen KOT
                            </span>
                        @elseif(($prof['purpose'] ?? '') === 'counter')
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center gap-1 shrink-0">
                                <i class="fa-solid fa-cash-register"></i> Billing Counter
                            </span>
                        @elseif(($prof['purpose'] ?? '') === 'bar')
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200 flex items-center gap-1 shrink-0">
                                <i class="fa-solid fa-wine-glass"></i> Bar Station
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200 flex items-center gap-1 shrink-0">
                                <i class="fa-solid fa-layer-group"></i> All-in-One
                            </span>
                        @endif
                    </div>

                    <!-- Specs details -->
                    <div class="grid grid-cols-2 gap-2 text-xs py-2 border-t border-b border-slate-100 my-2">
                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Connection Mode</span>
                            @if(($prof['connection_type'] ?? '') === 'network')
                                <span class="font-bold text-indigo-700 flex items-center gap-1">
                                    <i class="fa-solid fa-network-wired text-[10px]"></i>
                                    {{ $prof['ip_address'] ?? '127.0.0.1' }}:{{ $prof['port'] ?? 9100 }}
                                </span>
                            @elseif(($prof['connection_type'] ?? '') === 'driver')
                                <span class="font-bold text-sky-700 flex items-center gap-1">
                                    <i class="fa-solid fa-desktop text-[10px]"></i>
                                    {{ $prof['driver_name'] ?? 'OS Printer' }}
                                </span>
                            @else
                                <span class="font-bold text-slate-700 flex items-center gap-1">
                                    <i class="fa-solid fa-window-maximize text-[10px]"></i>
                                    Browser Pop-up
                                </span>
                            @endif
                        </div>

                        <div>
                            <span class="text-slate-400 block text-[10px] uppercase font-semibold">Paper & Copies</span>
                            <span class="font-bold text-slate-800">
                                {{ $prof['paper_width'] ?? '80mm' }} &bull; {{ $prof['copies'] ?? 1 }} copy
                            </span>
                        </div>
                    </div>

                    <!-- Features Pills -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        @if(!empty($prof['auto_cut']))
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-bold"><i class="fa-solid fa-scissors mr-0.5"></i> Auto-Cut</span>
                        @endif
                        @if(!empty($prof['open_cash_drawer']))
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 font-bold border border-emerald-200"><i class="fa-solid fa-vault mr-0.5"></i> Drawer Kick</span>
                        @endif
                        @if(!empty($prof['is_active']))
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-green-100 text-green-800 font-bold"><i class="fa-solid fa-circle-check mr-0.5"></i> Active</span>
                        @else
                            <span class="text-[9px] px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 font-bold">Disabled</span>
                        @endif
                    </div>
                </div>

                <!-- Card Actions -->
                <div class="flex items-center justify-between pt-3 mt-2 border-t border-slate-100">
                    <button type="button" onclick="testPrinterProfile('{{ $prof['id'] }}')" class="px-2.5 py-1 rounded-lg text-xs font-bold text-indigo-600 hover:bg-indigo-50 active:scale-95 transition flex items-center gap-1">
                        <i class="fa-solid fa-bolt"></i> Test Print
                    </button>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick='editPrinterProfile(@json($prof))' class="px-2.5 py-1 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-100 active:scale-95 transition flex items-center gap-1">
                            <i class="fa-solid fa-pen-to-square"></i> Edit
                        </button>
                        <button type="button" onclick="deletePrinterProfile('{{ $prof['id'] }}', '{{ addslashes($prof['name']) }}')" class="px-2.5 py-1 rounded-lg text-xs font-bold text-rose-600 hover:bg-rose-50 active:scale-95 transition flex items-center gap-1">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-2 py-8 text-center text-slate-400">
                <i class="fa-solid fa-print text-2xl mb-1 text-slate-300"></i>
                <p class="text-xs font-bold">No hardware printer profiles created yet.</p>
                <p class="text-[11px] text-slate-400">Click "+ Add Printer Profile" above to configure your Kitchen and Counter printers.</p>
            </div>
            @endforelse
        </div>

        <!-- Receipt Header, Footer, and Copy Options -->
        <div class="pt-4 border-t border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Default Receipt Paper Roll Width *</label>
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

    <!-- 4. UPI Payment & Bill Print QR Code -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
        <div class="border-b border-slate-100 pb-3 flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center font-bold text-sm">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-800">UPI Payment & Bill Print QR Code</h3>
                <p class="text-[11px] text-slate-400">Configure UPI QR code to print at the end of the customer receipt with auto bill amount</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">UPI ID (VPA) for Direct Customer Payments</label>
                <input type="text" name="upi_id" value="{{ old('upi_id', $settings['upi_id'] ?? '') }}" placeholder="e.g. 9876543210@paytm or merchant@okhdfcbank" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs font-mono focus:ring-2 focus:ring-slate-900">
                <p class="text-[10px] text-slate-400 mt-1">Generates dynamic UPI QR code so scanning reads the exact bill amount</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">UPI Payee / Business Name</label>
                <input type="text" name="upi_payee_name" value="{{ old('upi_payee_name', $settings['upi_payee_name'] ?? '') }}" placeholder="e.g. Royal Dine Restaurant" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
                <p class="text-[10px] text-slate-400 mt-1">Displayed in Google Pay / PhonePe when customer scans the bill</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Receipt QR Format Mode</label>
                <select name="qr_code_type" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-2 focus:ring-slate-900">
                    <option value="dynamic_upi" {{ ($settings['qr_code_type'] ?? '') !== 'uploaded_image' ? 'selected' : '' }}>Dynamic UPI QR (Auto-encodes exact bill amount)</option>
                    <option value="uploaded_image" {{ ($settings['qr_code_type'] ?? '') === 'uploaded_image' ? 'selected' : '' }}>Uploaded Custom QR Code Image</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Upload QR Code Image</label>
                <input type="file" name="qr_code_image" accept="image/png,image/jpeg,image/webp" class="text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                @if(!empty($settings['qr_code_image']))
                    <p class="text-[11px] text-slate-500 mt-1">Current uploaded image: <span class="font-mono text-slate-700">{{ $settings['qr_code_image'] }}</span></p>
                @endif
            </div>

            <div class="md:col-span-2 pt-2">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="show_qr_on_receipt" value="1" {{ !empty($settings['show_qr_on_receipt']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <span class="text-xs font-bold text-slate-800">Print UPI QR Code at the bottom / end of bill receipt</span>
                </label>
                <p class="text-[11px] text-slate-400 ml-6">When customer scans the printed QR at the end of the bill with GPay, PhonePe, Paytm, or BHIM, it reads the bill amount directly for instant payment.</p>
            </div>
        </div>
    </div>

    <!-- 5. Tax & GST Rules -->
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

                <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 cursor-pointer">
                    <input type="checkbox" name="enable_multimode" value="1" {{ !empty($settings['enable_multimode']) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                    <div>
                        <p class="text-xs font-bold text-slate-800">Multimode / Split Payment</p>
                        <p class="text-[10px] text-slate-400">Split tender between Cash and UPI</p>
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

<!-- MODAL: Add / Edit Printer Profile -->
<div id="printerProfileModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-3xl sm:rounded-2xl shadow-2xl w-full max-w-lg max-h-[92vh] flex flex-col overflow-hidden">
        <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between flex-shrink-0">
            <div>
                <h3 class="text-sm font-bold flex items-center gap-2" id="printerModalTitle">
                    <i class="fa-solid fa-print text-orange-400"></i>
                    <span>Configure Printer Profile</span>
                </h3>
                <p class="text-[11px] text-slate-400">Assign hardware device and routing purpose</p>
            </div>
            <button type="button" onclick="closePrinterModal()" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white transition active:scale-90"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="printerProfileForm" onsubmit="savePrinterProfileAjax(event)" class="p-5 space-y-3.5 overflow-y-auto flex-1 text-xs">
            <input type="hidden" id="profId" name="id">

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Profile / Printer Name *</label>
                <input type="text" id="profName" name="name" required placeholder="e.g. Kitchen Thermal 80mm or Cashier Counter POS" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-orange-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Assigned Purpose *</label>
                    <select id="profPurpose" name="purpose" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-orange-500">
                        <option value="kitchen">Kitchen (KOT - Order Ticket)</option>
                        <option value="counter">Counter (Customer Bill & Drawer)</option>
                        <option value="bar">Bar (Drinks & Beverage Station)</option>
                        <option value="both">Both (All-in-One KOT + Bill)</option>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">Determines whether KOT or Pay & Settle prints here</p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Connection Method *</label>
                    <select id="profConnection" name="connection_type" onchange="toggleConnectionFields()" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-orange-500">
                        <option value="browser">Browser Print Window (Standard)</option>
                        <option value="network">Direct Network (LAN / Wi-Fi Socket)</option>
                        <option value="driver">Local Driver / QZ Tray (Silent USB)</option>
                    </select>
                </div>
            </div>

            <!-- Network Specific Fields -->
            <div id="networkFieldsGroup" class="hidden p-3 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-2">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-indigo-900">Network Printer Settings (ESC/POS)</span>
                <div class="grid grid-cols-3 gap-2">
                    <div class="col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-0.5">Printer IP Address</label>
                        <input type="text" id="profIp" name="ip_address" placeholder="192.168.1.200" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-0.5">Port</label>
                        <input type="number" id="profPort" name="port" value="9100" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs font-mono">
                    </div>
                </div>
                <p class="text-[10px] text-indigo-700">Enter the local static IP assigned to your Ethernet/Wi-Fi thermal printer (Default port: 9100).</p>
            </div>

            <!-- Driver Specific Fields -->
            <div id="driverFieldsGroup" class="hidden p-3 bg-sky-50/70 border border-sky-200 rounded-xl space-y-2">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-sky-900">OS Driver / QZ Tray Settings</span>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-0.5">Installed Windows/OS Printer Name</label>
                    <input type="text" id="profDriverName" name="driver_name" placeholder="e.g. Kitchen_Printer or EPSON TM-T82" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-300 text-xs">
                </div>
                <p class="text-[10px] text-sky-700">Exact name of the printer as shown in Windows Control Panel / Printers & Scanners.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Paper Roll Width</label>
                    <select id="profPaper" name="paper_width" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium">
                        <option value="80mm">80mm Standard POS</option>
                        <option value="58mm">58mm Compact / Mobile</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Copies to Print</label>
                    <input type="number" id="profCopies" name="copies" min="1" max="5" value="1" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold">
                </div>
            </div>

            <div class="space-y-2 pt-2 border-t border-slate-100">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="profAutoCut" name="auto_cut" value="1" checked class="w-4 h-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                    <span class="font-semibold text-slate-700">Auto-Cut paper after printing ticket</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="profCashDrawer" name="open_cash_drawer" value="1" class="w-4 h-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                    <span class="font-semibold text-slate-700">Kick / Open Cash Drawer (typically for Counter printer)</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" id="profActive" name="is_active" value="1" checked class="w-4 h-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                    <span class="font-semibold text-slate-700">Enable this printer profile</span>
                </label>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Notes / Physical Location</label>
                <input type="text" id="profNotes" name="notes" placeholder="e.g. Inside main hot kitchen above prep counter" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="closePrinterModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition">Cancel</button>
                <button type="submit" id="btnSavePrinterProf" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-md transition">Save Profile</button>
            </div>
        </form>
    </div>
</div>

<script>
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function openAddPrinterModal() {
        document.getElementById('printerModalTitle').innerHTML = '<i class="fa-solid fa-plus text-orange-400"></i><span>Add New Printer Profile</span>';
        document.getElementById('profId').value = '';
        document.getElementById('profName').value = '';
        document.getElementById('profPurpose').value = 'kitchen';
        document.getElementById('profConnection').value = 'network';
        document.getElementById('profIp').value = '192.168.1.200';
        document.getElementById('profPort').value = '9100';
        document.getElementById('profDriverName').value = '';
        document.getElementById('profPaper').value = '80mm';
        document.getElementById('profCopies').value = '1';
        document.getElementById('profAutoCut').checked = true;
        document.getElementById('profCashDrawer').checked = false;
        document.getElementById('profActive').checked = true;
        document.getElementById('profNotes').value = '';

        toggleConnectionFields();
        document.getElementById('printerProfileModal').classList.remove('hidden');
    }

    function editPrinterProfile(prof) {
        document.getElementById('printerModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-orange-400"></i><span>Edit Printer Profile</span>';
        document.getElementById('profId').value = prof.id || '';
        document.getElementById('profName').value = prof.name || '';
        document.getElementById('profPurpose').value = prof.purpose || 'kitchen';
        document.getElementById('profConnection').value = prof.connection_type || 'browser';
        document.getElementById('profIp').value = prof.ip_address || '192.168.1.100';
        document.getElementById('profPort').value = prof.port || 9100;
        document.getElementById('profDriverName').value = prof.driver_name || '';
        document.getElementById('profPaper').value = prof.paper_width || '80mm';
        document.getElementById('profCopies').value = prof.copies || 1;
        document.getElementById('profAutoCut').checked = !!prof.auto_cut;
        document.getElementById('profCashDrawer').checked = !!prof.open_cash_drawer;
        document.getElementById('profActive').checked = prof.is_active !== false && prof.is_active !== 0;
        document.getElementById('profNotes').value = prof.notes || '';

        toggleConnectionFields();
        document.getElementById('printerProfileModal').classList.remove('hidden');
    }

    function closePrinterModal() {
        document.getElementById('printerProfileModal').classList.add('hidden');
    }

    function toggleConnectionFields() {
        const conn = document.getElementById('profConnection').value;
        const netGroup = document.getElementById('networkFieldsGroup');
        const drvGroup = document.getElementById('driverFieldsGroup');

        if (conn === 'network') {
            netGroup.classList.remove('hidden');
            drvGroup.classList.add('hidden');
        } else if (conn === 'driver') {
            netGroup.classList.add('hidden');
            drvGroup.classList.remove('hidden');
        } else {
            netGroup.classList.add('hidden');
            drvGroup.classList.add('hidden');
        }
    }

    async function savePrinterProfileAjax(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSavePrinterProf');
        btn.disabled = true;
        btn.innerText = 'Saving...';

        const payload = {
            id: document.getElementById('profId').value || null,
            name: document.getElementById('profName').value,
            purpose: document.getElementById('profPurpose').value,
            connection_type: document.getElementById('profConnection').value,
            ip_address: document.getElementById('profIp').value,
            port: parseInt(document.getElementById('profPort').value) || 9100,
            driver_name: document.getElementById('profDriverName').value,
            paper_width: document.getElementById('profPaper').value,
            copies: parseInt(document.getElementById('profCopies').value) || 1,
            auto_cut: document.getElementById('profAutoCut').checked ? 1 : 0,
            open_cash_drawer: document.getElementById('profCashDrawer').checked ? 1 : 0,
            is_active: document.getElementById('profActive').checked ? 1 : 0,
            notes: document.getElementById('profNotes').value,
        };

        try {
            const res = await fetch("{{ route('settings.printer-profiles.save') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                closePrinterModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Printer Profile Saved',
                    text: data.message,
                    timer: 1800,
                    showConfirmButton: false
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not save profile.' });
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Failed to communicate with server.' });
        } finally {
            btn.disabled = false;
            btn.innerText = 'Save Profile';
        }
    }

    async function deletePrinterProfile(id, name) {
        const confirm = await Swal.fire({
            title: `Delete '${name}'?`,
            text: 'Are you sure you want to remove this printer profile?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, Delete'
        });

        if (confirm.isConfirmed) {
            try {
                const res = await fetch(`{{ url('settings/printer-profiles') }}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Deleted', text: data.message, timer: 1500, showConfirmButton: false }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete printer profile.' });
            }
        }
    }

    async function testPrinterProfile(id) {
        Swal.fire({
            title: 'Testing Printer...',
            text: 'Sending test print command to target hardware device.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const res = await fetch("{{ route('settings.printer-profiles.test') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ profile_id: id })
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Test Passed!',
                    text: data.message
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Test Failed',
                    text: data.message || 'Printer failed to respond.'
                });
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Test Failed', text: 'Could not connect to printer test endpoint.' });
        }
    }
</script>
@endsection
