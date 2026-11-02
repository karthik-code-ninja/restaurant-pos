@extends('layouts.app')

@section('title', 'New Purchase Entry')
@section('page_title', 'Inward Purchase Invoice (Shortcut: c)')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="flex items-center justify-between bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-bold rounded">Shortcut: c</span>
            <h1 class="text-xl font-bold text-slate-800">Record Inward Purchase Invoice</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quick-access') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded transition">
                <i class="fa-solid fa-gauge-high mr-1"></i> Quick Access
            </a>
            <a href="{{ route('purchases.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded transition">
                <i class="fa-solid fa-list mr-1"></i> Purchase Register
            </a>
        </div>
    </div>

    @if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-xs font-semibold">
        <ul class="list-disc pl-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm" class="space-y-6">
        @csrf

        <!-- Vendor & Invoice Info Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide border-b pb-2">1. Supplier & Invoice Header</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Purchase No</label>
                    <input type="text" name="purchase_no" value="{{ $nextPurchaseNo }}" readonly class="w-full text-xs font-mono font-bold bg-slate-100 border border-slate-300 rounded px-3 py-2 text-slate-600">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Purchase Date *</label>
                    <input type="date" name="purchase_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs border border-slate-300 rounded px-3 py-2 focus:ring-2 focus:ring-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Vendor Bill / Invoice #</label>
                    <input type="text" name="invoice_no" placeholder="e.g. INV-9902" class="w-full text-xs border border-slate-300 rounded px-3 py-2 focus:ring-2 focus:ring-red-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Select Supplier *</label>
                    <select name="supplier_id" id="supplierSelect" onchange="handleSupplierChange(this)" class="w-full text-xs border border-slate-300 rounded px-3 py-2 focus:ring-2 focus:ring-red-500 focus:outline-none">
                        <option value="">-- Choose Existing Supplier --</option>
                        @foreach($suppliers as $supp)
                            <option value="{{ $supp->id }}" data-name="{{ $supp->name }}" data-gst="{{ $supp->gstin }}">{{ $supp->name }} (Bal: ₹{{ number_format($supp->current_balance, 2) }})</option>
                        @endforeach
                        <option value="new">+ Enter New Supplier</option>
                    </select>
                </div>
            </div>

            <div id="newSupplierDiv" class="hidden grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">New Supplier Name *</label>
                    <input type="text" name="supplier_name" id="newSupplierName" placeholder="Supplier Company or Person Name" class="w-full text-xs border border-slate-300 rounded px-3 py-2 focus:ring-2 focus:ring-red-500">
                </div>
            </div>
        </div>

        <!-- Line Items Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b pb-2">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide">2. Purchase Items</h2>
                <button type="button" onclick="addRow()" class="px-3 py-1 bg-red-700 hover:bg-red-800 text-white text-xs font-bold rounded shadow transition">
                    <i class="fa-solid fa-plus mr-1"></i> Add Item Line
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-xs border-collapse" id="itemsTable">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase">
                            <th class="py-2.5 px-3 text-left" style="width: 25%;">Item Name</th>
                            <th class="py-2.5 px-2 text-left" style="width: 15%;">Type</th>
                            <th class="py-2.5 px-2 text-right" style="width: 12%;">Qty</th>
                            <th class="py-2.5 px-2 text-left" style="width: 10%;">Unit</th>
                            <th class="py-2.5 px-2 text-right" style="width: 12%;">Rate (₹)</th>
                            <th class="py-2.5 px-2 text-right" style="width: 10%;">GST %</th>
                            <th class="py-2.5 px-3 text-right" style="width: 13%;">Total (₹)</th>
                            <th class="py-2.5 px-2 text-center" style="width: 3%;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTbody" class="divide-y divide-slate-100">
                        <!-- Default Row 0 -->
                        <tr class="item-row">
                            <td class="py-2 px-3">
                                <input type="text" name="items[0][item_name]" list="presetItemsList" required placeholder="Item description..." class="w-full border border-slate-300 rounded px-2 py-1.5 focus:outline-none focus:border-red-500 item-name-input" onchange="autoFillItem(this, 0)">
                                <input type="hidden" name="items[0][item_id]" class="item-id-input">
                            </td>
                            <td class="py-2 px-2">
                                <select name="items[0][item_type]" class="w-full border border-slate-300 rounded px-2 py-1.5 item-type-select">
                                    <option value="food">Food Item</option>
                                    <option value="inventory">Raw Inventory</option>
                                </select>
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" step="0.01" min="0.01" name="items[0][quantity]" value="1" required class="w-full text-right border border-slate-300 rounded px-2 py-1.5 qty-input" oninput="calculateRow(0)">
                            </td>
                            <td class="py-2 px-2">
                                <input type="text" name="items[0][unit]" value="Pcs" class="w-full border border-slate-300 rounded px-2 py-1.5 unit-input">
                            </td>
                            <td class="py-2 px-2">
                                <input type="number" step="0.01" min="0" name="items[0][unit_price]" value="0.00" required class="w-full text-right border border-slate-300 rounded px-2 py-1.5 price-input" oninput="calculateRow(0)">
                            </td>
                            <td class="py-2 px-2">
                                <select name="items[0][tax_percent]" class="w-full text-right border border-slate-300 rounded px-2 py-1.5 tax-select" onchange="calculateRow(0)">
                                    <option value="0">0%</option>
                                    <option value="5" selected>5%</option>
                                    <option value="12">12%</option>
                                    <option value="18">18%</option>
                                    <option value="28">28%</option>
                                </select>
                            </td>
                            <td class="py-2 px-3 text-right font-mono font-bold text-slate-800 row-total">
                                0.00
                            </td>
                            <td class="py-2 px-2 text-center">
                                <button type="button" onclick="removeRow(this)" class="text-slate-300 hover:text-red-500"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Datalist for fast item suggestions -->
            <datalist id="presetItemsList">
                @foreach($foods as $f)
                    <option value="{{ $f->name }}" data-id="{{ $f->id }}" data-type="food" data-price="{{ $f->cost_price ?: ($f->price * 0.5) }}" data-unit="Pcs">
                @endforeach
                @foreach($inventoryItems as $inv)
                    <option value="{{ $inv->name }}" data-id="{{ $inv->id }}" data-type="inventory" data-price="{{ $inv->cost_price }}" data-unit="{{ $inv->unit }}">
                @endforeach
            </datalist>
        </div>

        <!-- Payment & Settlement Card -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide border-b pb-2">3. Payment Settlement & Totals</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Payment Status *</label>
                            <select name="payment_status" id="paymentStatus" onchange="handlePaymentStatusChange()" class="w-full text-xs border border-slate-300 rounded px-3 py-2">
                                <option value="paid">Fully Paid</option>
                                <option value="partial">Partially Paid</option>
                                <option value="unpaid">Credit (Unpaid)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Payment Method</label>
                            <select name="payment_method" class="w-full text-xs border border-slate-300 rounded px-3 py-2">
                                <option value="cash">Cash in Hand</option>
                                <option value="bank">Bank / NEFT / UPI</option>
                                <option value="cheque">Cheque</option>
                                <option value="credit">Supplier Credit Account</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Notes / Narration</label>
                        <textarea name="notes" rows="2" class="w-full text-xs border border-slate-300 rounded px-3 py-2" placeholder="Optional purchase notes..."></textarea>
                    </div>
                </div>

                <!-- Totals Box -->
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Items Subtotal:</span>
                        <span class="font-mono font-bold" id="lblSubtotal">₹ 0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Input Tax (GST):</span>
                        <span class="font-mono font-bold text-blue-600" id="lblTax">₹ 0.00</span>
                    </div>
                    <div class="flex justify-between items-center text-slate-600">
                        <span>Discount:</span>
                        <input type="number" step="0.01" min="0" name="discount_amount" id="discountInput" value="0.00" oninput="calculateAll()" class="w-24 text-right text-xs border border-slate-300 rounded px-2 py-1 font-mono">
                    </div>
                    <div class="flex justify-between text-slate-900 border-t border-slate-200 pt-2 text-sm font-black">
                        <span>Grand Total:</span>
                        <span class="font-mono text-red-700 text-base" id="lblGrandTotal">₹ 0.00</span>
                    </div>
                    <div class="flex justify-between items-center pt-1">
                        <span class="font-bold text-emerald-800">Amount Paid Now:</span>
                        <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmountInput" value="0.00" oninput="calculateBalanceDue()" class="w-28 text-right text-xs font-bold border border-emerald-500 rounded px-2 py-1 font-mono text-emerald-700">
                    </div>
                    <div class="flex justify-between text-red-700 font-bold border-t border-slate-200 pt-1">
                        <span>Balance Due:</span>
                        <span class="font-mono font-black" id="lblDueAmount">₹ 0.00</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <a href="{{ route('purchases.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition">Cancel</a>
                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-red-700 to-amber-700 hover:from-red-600 hover:to-amber-600 text-white text-xs font-bold rounded-lg shadow-md transition">
                    <i class="fa-solid fa-check mr-1.5"></i> Save & Record Purchase
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    let rowCount = 1;

    function handleSupplierChange(select) {
        const newDiv = document.getElementById('newSupplierDiv');
        const newName = document.getElementById('newSupplierName');
        if (select.value === 'new') {
            newDiv.classList.remove('hidden');
            newName.required = true;
            newName.focus();
        } else {
            newDiv.classList.add('hidden');
            newName.required = false;
        }
    }

    function addRow() {
        const tbody = document.getElementById('itemsTbody');
        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.innerHTML = `
            <td class="py-2 px-3">
                <input type="text" name="items[${rowCount}][item_name]" list="presetItemsList" required placeholder="Item description..." class="w-full border border-slate-300 rounded px-2 py-1.5 focus:outline-none focus:border-red-500 item-name-input" onchange="autoFillItem(this, ${rowCount})">
                <input type="hidden" name="items[${rowCount}][item_id]" class="item-id-input">
            </td>
            <td class="py-2 px-2">
                <select name="items[${rowCount}][item_type]" class="w-full border border-slate-300 rounded px-2 py-1.5 item-type-select">
                    <option value="food">Food Item</option>
                    <option value="inventory">Raw Inventory</option>
                </select>
            </td>
            <td class="py-2 px-2">
                <input type="number" step="0.01" min="0.01" name="items[${rowCount}][quantity]" value="1" required class="w-full text-right border border-slate-300 rounded px-2 py-1.5 qty-input" oninput="calculateRow(${rowCount})">
            </td>
            <td class="py-2 px-2">
                <input type="text" name="items[${rowCount}][unit]" value="Pcs" class="w-full border border-slate-300 rounded px-2 py-1.5 unit-input">
            </td>
            <td class="py-2 px-2">
                <input type="number" step="0.01" min="0" name="items[${rowCount}][unit_price]" value="0.00" required class="w-full text-right border border-slate-300 rounded px-2 py-1.5 price-input" oninput="calculateRow(${rowCount})">
            </td>
            <td class="py-2 px-2">
                <select name="items[${rowCount}][tax_percent]" class="w-full text-right border border-slate-300 rounded px-2 py-1.5 tax-select" onchange="calculateRow(${rowCount})">
                    <option value="0">0%</option>
                    <option value="5" selected>5%</option>
                    <option value="12">12%</option>
                    <option value="18">18%</option>
                    <option value="28">28%</option>
                </select>
            </td>
            <td class="py-2 px-3 text-right font-mono font-bold text-slate-800 row-total">
                0.00
            </td>
            <td class="py-2 px-2 text-center">
                <button type="button" onclick="removeRow(this)" class="text-slate-300 hover:text-red-500"><i class="fa-solid fa-trash"></i></button>
            </td>
        `;
        tbody.appendChild(tr);
        rowCount++;
    }

    function removeRow(btn) {
        const tbody = document.getElementById('itemsTbody');
        if (tbody.querySelectorAll('tr').length > 1) {
            btn.closest('tr').remove();
            calculateAll();
        } else {
            alert('At least one item row is required.');
        }
    }

    function autoFillItem(input, index) {
        const val = input.value;
        const datalist = document.getElementById('presetItemsList');
        const opt = Array.from(datalist.options).find(o => o.value.toLowerCase() === val.toLowerCase());
        if (opt) {
            const tr = input.closest('tr');
            tr.querySelector('.item-id-input').value = opt.dataset.id || '';
            tr.querySelector('.item-type-select').value = opt.dataset.type || 'food';
            tr.querySelector('.price-input').value = opt.dataset.price || '0.00';
            tr.querySelector('.unit-input').value = opt.dataset.unit || 'Pcs';
            calculateRow(index, tr);
        }
    }

    function calculateRow(index, trElem) {
        const tr = trElem || document.querySelectorAll('.item-row')[index] || event?.target?.closest('tr');
        if (!tr) return;

        const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
        const price = parseFloat(tr.querySelector('.price-input').value) || 0;
        const taxPct = parseFloat(tr.querySelector('.tax-select').value) || 0;

        const lineSubtotal = qty * price;
        const lineTax = (lineSubtotal * taxPct) / 100;
        const lineTotal = lineSubtotal + lineTax;

        tr.querySelector('.row-total').textContent = lineTotal.toFixed(2);
        calculateAll();
    }

    function calculateAll() {
        let totalSub = 0;
        let totalTax = 0;

        document.querySelectorAll('.item-row').forEach(tr => {
            const qty = parseFloat(tr.querySelector('.qty-input').value) || 0;
            const price = parseFloat(tr.querySelector('.price-input').value) || 0;
            const taxPct = parseFloat(tr.querySelector('.tax-select').value) || 0;

            const lineSub = qty * price;
            const lineTax = (lineSub * taxPct) / 100;

            totalSub += lineSub;
            totalTax += lineTax;
        });

        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const grandTotal = Math.max(0, (totalSub + totalTax) - discount);

        document.getElementById('lblSubtotal').textContent = '₹ ' + totalSub.toFixed(2);
        document.getElementById('lblTax').textContent = '₹ ' + totalTax.toFixed(2);
        document.getElementById('lblGrandTotal').textContent = '₹ ' + grandTotal.toFixed(2);

        // Adjust paid amount if status is paid
        const status = document.getElementById('paymentStatus').value;
        if (status === 'paid') {
            document.getElementById('paidAmountInput').value = grandTotal.toFixed(2);
        } else if (status === 'unpaid') {
            document.getElementById('paidAmountInput').value = '0.00';
        }

        calculateBalanceDue();
    }

    function handlePaymentStatusChange() {
        calculateAll();
    }

    function calculateBalanceDue() {
        const grandTotalText = document.getElementById('lblGrandTotal').textContent.replace('₹ ', '');
        const grandTotal = parseFloat(grandTotalText) || 0;
        const paid = parseFloat(document.getElementById('paidAmountInput').value) || 0;
        const due = Math.max(0, grandTotal - paid);

        document.getElementById('lblDueAmount').textContent = '₹ ' + due.toFixed(2);
    }

    // Initialize calculation
    calculateAll();
</script>
@endsection

