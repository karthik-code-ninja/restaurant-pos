@extends('layouts.app')

@section('title', 'Print Barcodes')
@section('page_title', 'Barcode Label Printing Utility (Shortcut: n)')

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #barcodePrintArea, #barcodePrintArea * {
            visibility: visible;
        }
        #barcodePrintArea {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
        }
        .barcode-sticker {
            page-break-inside: avoid;
            margin: 4px !important;
            border: 1px dashed #cbd5e1 !important;
        }
    }
</style>
<!-- JsBarcode for client-side sharp SVG Barcode generation -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
@endpush

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm no-print">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: n</span>
                <h1 class="text-xl font-bold text-slate-800">Barcode Label Printing Utility</h1>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Generate and batch-print scannable EAN/Code128 product barcode stickers</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-black text-white text-xs font-bold rounded-lg shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i>
                <span>Print Barcode Stickers</span>
            </button>
        </div>
    </div>

    <!-- Generator Control Panel -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4 no-print">
        <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b pb-2">Label Configuration</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
            <div>
                <label class="block font-bold text-slate-700 mb-1">Select Item *</label>
                <select id="itemSelect" onchange="loadItemData(this)" class="w-full border border-slate-300 rounded px-2.5 py-1.5">
                    <option value="">-- Choose Item to Print --</option>
                    @foreach($foods as $f)
                        <option value="{{ $f->id }}" data-name="{{ $f->name }}" data-code="{{ $f->code }}" data-barcode="{{ $f->barcode ?: $f->code }}" data-price="{{ $f->price }}">
                            {{ $f->name }} ({{ $f->code }}) - ₹{{ number_format($f->price, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Barcode Code / Number</label>
                <input type="text" id="barcodeValue" placeholder="e.g. 890103001" class="w-full border border-slate-300 rounded px-2.5 py-1.5 font-mono font-bold" oninput="regenerateLabels()">
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Number of Labels (Copies)</label>
                <input type="number" id="labelCopies" min="1" max="100" value="12" class="w-full border border-slate-300 rounded px-2.5 py-1.5 font-bold" oninput="regenerateLabels()">
            </div>

            <div class="flex items-end">
                <button type="button" onclick="regenerateLabels()" class="w-full py-2 bg-red-700 hover:bg-red-800 text-white font-bold rounded shadow transition">
                    Generate Preview
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-6 pt-2 border-t text-xs">
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="showStoreName" checked onchange="regenerateLabels()" class="rounded text-red-600">
                <span>Show Restaurant Name</span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="showPrice" checked onchange="regenerateLabels()" class="rounded text-red-600">
                <span>Show MRP / Price</span>
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="showItemCode" checked onchange="regenerateLabels()" class="rounded text-red-600">
                <span>Show Item Code</span>
            </label>
        </div>
    </div>

    <!-- Print Preview Canvas / Stickers Grid -->
    <div class="bg-slate-100 p-6 rounded-xl border border-slate-200">
        <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 no-print">
            Live Sheet Preview (3 x 4 Grid Standard Sheet)
        </div>

        <div id="barcodePrintArea" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
            <!-- Stickers populated dynamically -->
        </div>
    </div>
</div>

<script>
    const restaurantName = "{{ $restaurantName }}";
    const currency = "{{ $currency }}";

    function loadItemData(select) {
        const opt = select.options[select.selectedIndex];
        if (!opt || !opt.value) return;

        document.getElementById('barcodeValue').value = opt.dataset.barcode || opt.dataset.code;
        regenerateLabels();
    }

    function regenerateLabels() {
        const select = document.getElementById('itemSelect');
        const opt = select.options[select.selectedIndex];
        const container = document.getElementById('barcodePrintArea');
        container.innerHTML = '';

        if (!opt || !opt.value) {
            container.innerHTML = '<div class="col-span-full py-12 text-center text-slate-400 font-medium">Please select an item above to generate barcode labels.</div>';
            return;
        }

        const itemName = opt.dataset.name || 'Sample Item';
        const itemCode = opt.dataset.code || 'CODE';
        const itemPrice = opt.dataset.price || '0.00';
        const barcodeVal = document.getElementById('barcodeValue').value || opt.dataset.code;
        const copies = parseInt(document.getElementById('labelCopies').value) || 12;

        const showStore = document.getElementById('showStoreName').checked;
        const showPrice = document.getElementById('showPrice').checked;
        const showCode = document.getElementById('showItemCode').checked;

        for (let i = 0; i < copies; i++) {
            const sticker = document.createElement('div');
            sticker.className = 'barcode-sticker bg-white border border-slate-300 rounded p-2.5 flex flex-col items-center justify-between text-center select-none shadow-sm';
            sticker.style.width = '100%';
            sticker.style.height = '145px';

            sticker.innerHTML = `
                <div class="w-full">
                    ${showStore ? `<div class="text-[9px] font-extrabold uppercase tracking-wider text-slate-500 truncate">${restaurantName}</div>` : ''}
                    <div class="text-xs font-bold text-slate-800 truncate" title="${itemName}">${itemName}</div>
                </div>
                <div class="py-0.5">
                    <svg class="barcode-svg" id="barcode_svg_${i}"></svg>
                </div>
                <div class="w-full flex justify-between items-center text-[10px] font-mono px-1">
                    ${showCode ? `<span class="text-slate-600">${itemCode}</span>` : '<span></span>'}
                    ${showPrice ? `<span class="font-bold text-slate-900 font-sans">MRP: ${currency} ${Number(itemPrice).toFixed(0)}</span>` : '<span></span>'}
                </div>
            `;

            container.appendChild(sticker);

            // Render barcode into SVG
            try {
                JsBarcode(`#barcode_svg_${i}`, barcodeVal, {
                    format: "CODE128",
                    width: 1.2,
                    height: 35,
                    displayValue: true,
                    fontSize: 10,
                    margin: 2
                });
            } catch (e) {
                console.error("Barcode rendering error: ", e);
            }
        }
    }

    // Auto-select first item on page load
    window.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('itemSelect');
        if (select && select.options.length > 1) {
            select.selectedIndex = 1;
            loadItemData(select);
        }
    });
</script>
@endsection

