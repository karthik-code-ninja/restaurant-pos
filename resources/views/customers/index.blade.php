@extends('layouts.app')

@section('title', 'Customers & Ledger')
@section('page_title', 'Customer & Ledger Maintenance')

@section('content')
<div class="space-y-6">
    <!-- Top Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Customers</p>
                <h4 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalCustomers) }}</h4>
                <p class="text-xs text-slate-400 mt-1">Registered clients</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Total Outstanding Due</p>
                <h4 class="text-2xl font-black text-rose-600 mt-1">{{ $currency }}{{ number_format($totalDue, 2) }}</h4>
                <p class="text-xs text-rose-500 mt-1">{{ $dueCustomersCount }} customers with due</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Advance Deposits</p>
                <h4 class="text-2xl font-black text-emerald-600 mt-1">{{ $currency }}{{ number_format($totalAdvance, 2) }}</h4>
                <p class="text-xs text-slate-400 mt-1">Prepaid / Credit balance</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-indigo-600 uppercase tracking-wider">Net Receivables</p>
                @php $net = $totalDue - $totalAdvance; @endphp
                <h4 class="text-2xl font-black {{ $net >= 0 ? 'text-indigo-600' : 'text-emerald-600' }} mt-1">
                    {{ $currency }}{{ number_format($net, 2) }}
                </h4>
                <p class="text-xs text-slate-400 mt-1">Total Due minus Advances</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl font-bold">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
        </div>
    </div>

    <!-- Actions & Filter Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto flex-1">
            <div class="relative flex-1 min-w-[220px] max-w-md">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, phone, email, GSTIN..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500 bg-white">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
            </div>

            <!-- Balance Status Filter -->
            <select name="balance_status" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-slate-300 text-xs bg-white">
                <option value="all" {{ request('balance_status', 'all') === 'all' ? 'selected' : '' }}>All Balances</option>
                <option value="due" {{ request('balance_status') === 'due' ? 'selected' : '' }}>Has Due Balance (Debt)</option>
                <option value="advance" {{ request('balance_status') === 'advance' ? 'selected' : '' }}>Has Advance Deposit</option>
                <option value="settled" {{ request('balance_status') === 'settled' ? 'selected' : '' }}>Settled / Zero Balance</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-bold transition">
                Filter
            </button>
            @if(request()->hasAny(['search', 'balance_status']))
            <a href="{{ route('customers.index') }}" class="px-3 py-2 text-slate-600 hover:text-slate-900 text-xs font-semibold">
                Reset
            </a>
            @endif
        </form>

        <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
            @if(auth()->user()->hasPermission('customer.manage'))
            <button type="button" onclick="openCreateCustomerModal()" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-xs font-bold flex items-center gap-2 shadow-sm transition active:scale-95">
                <i class="fa-solid fa-user-plus"></i>
                <span>Add Customer</span>
            </button>
            @endif
        </div>
    </div>

    <!-- Customer Listing Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-4 py-3.5">Customer</th>
                        <th class="px-4 py-3.5">Contact Details</th>
                        <th class="px-4 py-3.5 text-right">Credit Limit</th>
                        <th class="px-4 py-3.5 text-right">Current Balance</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($customers as $c)
                    <tr class="hover:bg-slate-50/80 transition">
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs shrink-0 uppercase">
                                    {{ substr($c->name, 0, 2) }}
                                </div>
                                <div>
                                    <a href="{{ route('customers.ledger', $c) }}" class="font-bold text-slate-900 hover:text-orange-600 transition block">
                                        {{ $c->name }}
                                    </a>
                                    @if($c->gstin)
                                    <span class="text-[10px] text-slate-400 font-mono">GST: {{ $c->gstin }}</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-slate-600">
                            <div>{{ $c->phone ?: '-' }}</div>
                            @if($c->email)
                            <div class="text-[11px] text-slate-400">{{ $c->email }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right text-slate-600 font-mono">
                            {{ $c->credit_limit > 0 ? $currency . number_format($c->credit_limit, 2) : 'No Limit' }}
                        </td>
                        <td class="px-4 py-3.5 text-right">
                            @if($c->balance > 0.001)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-200">
                                    Due: {{ $currency }}{{ number_format($c->balance, 2) }}
                                </span>
                            @elseif($c->balance < -0.001)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    Advance: {{ $currency }}{{ number_format(abs($c->balance), 2) }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                                    Clear (₹0.00)
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center">
                            @if($c->status)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Active</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                            <!-- View Ledger -->
                            <a href="{{ route('customers.ledger', $c) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition" title="View Full Ledger Statement">
                                <i class="fa-solid fa-book"></i> Ledger
                            </a>

                            @if(auth()->user()->hasPermission('customer.manage'))
                            <!-- Quick Receive Payment / Credit -->
                            <button type="button" onclick="openReceivePaymentModal({{ $c->id }}, '{{ addslashes($c->name) }}', {{ (float)$c->balance }})" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold transition" title="Receive Payment towards Due">
                                <i class="fa-solid fa-hand-holding-dollar"></i> Settle
                            </button>

                            <!-- Edit Customer -->
                            <button type="button" onclick='openEditCustomerModal(@json($c))' class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 hover:text-slate-900 inline-flex items-center justify-center transition" title="Edit Customer Details">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>

                            <!-- Delete -->
                            <form action="{{ route('customers.destroy', $c) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 inline-flex items-center justify-center transition" title="Delete Customer">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                            <i class="fa-solid fa-users-slash text-3xl mb-2 text-slate-300 block"></i>
                            <p class="font-bold text-sm text-slate-600">No Customers Found</p>
                            <p class="text-xs text-slate-400 mt-1">Start by adding a new customer or adjusting your search filters.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
            {{ $customers->links() }}
        </div>
        @endif
    </div>
</div>

<!-- MODAL: Add Customer -->
<div id="createCustomerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[92vh]">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-orange-500"></i> Add New Customer
            </h3>
            <button type="button" onclick="closeCreateCustomerModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="{{ route('customers.store') }}" method="POST" class="p-6 space-y-4 overflow-y-auto flex-1 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Customer Name *</label>
                <input type="text" name="name" required placeholder="e.g. Ramesh Patel / ABC Enterprises" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Phone Number</label>
                    <input type="text" name="phone" placeholder="e.g. 9876543210" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Email</label>
                    <input type="email" name="email" placeholder="customer@example.com" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">GSTIN (Optional)</label>
                    <input type="text" name="gstin" placeholder="GST Number" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Credit Limit (₹)</label>
                    <input type="number" step="0.01" min="0" name="credit_limit" value="0.00" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                    <span class="text-[10px] text-slate-400">Set 0 for unlimited credit</span>
                </div>
            </div>

            <!-- Opening Balance Section -->
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                <span class="block font-bold text-slate-700 uppercase text-[11px]">Opening Ledger Balance (Optional)</span>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase mb-0.5">Amount (₹)</label>
                        <input type="number" step="0.01" min="0" name="opening_balance" value="0.00" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                    </div>
                    <div>
                        <label class="block text-[10px] text-slate-500 uppercase mb-0.5">Balance Type</label>
                        <select name="opening_balance_type" class="w-full px-3 py-1.5 rounded-lg border border-slate-300 text-xs bg-white">
                            <option value="due">Customer Owes (Due)</option>
                            <option value="advance">Customer Paid (Advance)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Address</label>
                <textarea name="address" rows="2" placeholder="Full address" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500"></textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Notes / Remarks</label>
                <input type="text" name="notes" placeholder="Optional notes" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                <button type="button" onclick="closeCreateCustomerModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-bold transition shadow-sm">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Edit Customer -->
<div id="editCustomerModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[92vh]">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
            <h3 class="font-bold text-base flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-orange-500"></i> Edit Customer Details
            </h3>
            <button type="button" onclick="closeEditCustomerModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="editCustomerForm" method="POST" class="p-6 space-y-4 overflow-y-auto flex-1 text-xs">
            @csrf
            @method('PUT')
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Customer Name *</label>
                <input type="text" id="editCustomerName" name="name" required class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Phone Number</label>
                    <input type="text" id="editCustomerPhone" name="phone" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Email</label>
                    <input type="email" id="editCustomerEmail" name="email" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">GSTIN</label>
                    <input type="text" id="editCustomerGstin" name="gstin" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 uppercase mb-1">Credit Limit (₹)</label>
                    <input type="number" step="0.01" min="0" id="editCustomerCreditLimit" name="credit_limit" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Status</label>
                <select id="editCustomerStatus" name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-white">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Address</label>
                <textarea id="editCustomerAddress" name="address" rows="2" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500"></textarea>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Notes</label>
                <input type="text" id="editCustomerNotes" name="notes" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs focus:ring-1 focus:ring-orange-500">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                <button type="button" onclick="closeEditCustomerModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-bold transition shadow-sm">Update Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: Quick Receive Payment -->
<div id="receivePaymentModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
            <div>
                <h3 class="font-bold text-base flex items-center gap-2">
                    <i class="fa-solid fa-hand-holding-dollar text-emerald-400"></i> Receive Customer Payment
                </h3>
                <p id="receivePaymentCustomerLabel" class="text-xs text-orange-400 font-medium mt-0.5">Customer Name</p>
            </div>
            <button type="button" onclick="closeReceivePaymentModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 flex items-center justify-center text-slate-400 hover:text-white transition"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="receivePaymentForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            <!-- Current Balance Banner -->
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                <span class="text-slate-500 font-bold uppercase text-[10px]">Current Balance</span>
                <span id="receivePaymentBalanceDisplay" class="font-black text-sm text-rose-700">₹0.00</span>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Amount Received (₹) *</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 font-bold text-sm">₹</span>
                    <input type="number" step="0.01" min="0.01" id="receivePaymentAmountInput" name="amount" required placeholder="0.00" class="w-full pl-7 pr-3 py-2 rounded-xl border border-slate-300 font-black text-base focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Payment Mode *</label>
                <select name="payment_mode" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs bg-white font-semibold">
                    <option value="cash">Cash</option>
                    <option value="upi">UPI / QR Code</option>
                    <option value="card">Card (Debit/Credit)</option>
                    <option value="bank_transfer">Bank Transfer / NEFT / IMPS</option>
                    <option value="cheque">Cheque</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Reference / UTR / Cheque No.</label>
                <input type="text" name="reference_number" placeholder="Optional transaction ID or cheque number" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs">
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Notes / Description</label>
                <input type="text" name="description" placeholder="e.g. Settle bill dues / Part payment" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs">
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                <button type="button" onclick="closeReceivePaymentModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold transition shadow-sm">Save Payment</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openCreateCustomerModal() {
        document.getElementById('createCustomerModal').classList.remove('hidden');
    }
    function closeCreateCustomerModal() {
        document.getElementById('createCustomerModal').classList.add('hidden');
    }

    function openEditCustomerModal(customer) {
        const form = document.getElementById('editCustomerForm');
        form.action = `/customers/${customer.id}`;
        document.getElementById('editCustomerName').value = customer.name || '';
        document.getElementById('editCustomerPhone').value = customer.phone || '';
        document.getElementById('editCustomerEmail').value = customer.email || '';
        document.getElementById('editCustomerGstin').value = customer.gstin || '';
        document.getElementById('editCustomerCreditLimit').value = customer.credit_limit || 0;
        document.getElementById('editCustomerStatus').value = customer.status ? '1' : '0';
        document.getElementById('editCustomerAddress').value = customer.address || '';
        document.getElementById('editCustomerNotes').value = customer.notes || '';
        document.getElementById('editCustomerModal').classList.remove('hidden');
    }
    function closeEditCustomerModal() {
        document.getElementById('editCustomerModal').classList.add('hidden');
    }

    function openReceivePaymentModal(id, name, balance) {
        const form = document.getElementById('receivePaymentForm');
        form.action = `/customers/${id}/payment`;
        document.getElementById('receivePaymentCustomerLabel').innerText = name;

        const balDisplay = document.getElementById('receivePaymentBalanceDisplay');
        const amtInput = document.getElementById('receivePaymentAmountInput');

        if (balance > 0.001) {
            balDisplay.className = 'font-black text-sm text-rose-700';
            balDisplay.innerText = `Due: ₹${balance.toFixed(2)}`;
            amtInput.value = balance.toFixed(2);
        } else if (balance < -0.001) {
            balDisplay.className = 'font-black text-sm text-emerald-700';
            balDisplay.innerText = `Advance: ₹${Math.abs(balance).toFixed(2)}`;
            amtInput.value = '';
        } else {
            balDisplay.className = 'font-black text-sm text-slate-700';
            balDisplay.innerText = 'Clear: ₹0.00';
            amtInput.value = '';
        }

        document.getElementById('receivePaymentModal').classList.remove('hidden');
    }
    function closeReceivePaymentModal() {
        document.getElementById('receivePaymentModal').classList.add('hidden');
    }
</script>
@endpush
@endsection

