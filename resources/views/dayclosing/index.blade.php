@extends('layouts.app')

@section('title', 'Day & Shift Closing')
@section('page_title', 'Cash Drawer & Day / Shift Closing')

@section('content')
<div class="space-y-6">
    <!-- Active Session Status Banner -->
    @if($currentSession)
    <div class="bg-white p-6 rounded-2xl border-2 border-orange-500 shadow-md">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                    <h3 class="text-base font-bold text-slate-900">Active {{ ucfirst($currentSession->shift_type) }} Session</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">OPEN</span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Opened by <strong>{{ $currentSession->openedByUser?->name }}</strong> on {{ $currentSession->opened_at->format('d/m/Y h:i A') }} ({{ $currentSession->opened_at->diffForHumans() }})
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" onclick="openWithdrawalModal()" class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                    <span>Record Cash Withdrawal</span>
                </button>

                <button type="button" onclick="openCloseModal()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-md shadow-rose-600/20 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-lock"></i>
                    <span>Close {{ ucfirst($currentSession->shift_type) }}</span>
                </button>
            </div>
        </div>

        <!-- Cash Reconciliation Grid -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 pt-5">
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">1. Opening Cash</span>
                <h4 class="text-lg font-black text-slate-900 mt-1">{{ $currency }}{{ number_format($openingCash, 2) }}</h4>
            </div>

            <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                <span class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">2. + Cash Sales</span>
                <h4 class="text-lg font-black text-emerald-800 mt-1">+{{ $currency }}{{ number_format($cashSales, 2) }}</h4>
            </div>

            <div class="p-3 bg-rose-50 rounded-xl border border-rose-200">
                <span class="text-[11px] font-semibold text-rose-700 uppercase tracking-wider">3. - Cash Expenses</span>
                <h4 class="text-lg font-black text-rose-800 mt-1">-{{ $currency }}{{ number_format($cashExpenses, 2) }}</h4>
            </div>

            <div class="p-3 bg-amber-50 rounded-xl border border-amber-200">
                <span class="text-[11px] font-semibold text-amber-700 uppercase tracking-wider">4. - Cash Withdrawals</span>
                <h4 class="text-lg font-black text-amber-800 mt-1">-{{ $currency }}{{ number_format($cashWithdrawals, 2) }}</h4>
            </div>

            <div class="p-3 bg-slate-900 text-white rounded-xl col-span-2 md:col-span-1 shadow-inner">
                <span class="text-[11px] font-bold text-orange-400 uppercase tracking-wider">Expected in Drawer</span>
                <h4 class="text-lg font-black text-white mt-1">{{ $currency }}{{ number_format($expectedCash, 2) }}</h4>
            </div>
        </div>
    </div>
    @else
    <!-- No Open Session - Call to Open -->
    <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm text-center">
        <div class="w-14 h-14 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3">
            <i class="fa-solid fa-cash-register"></i>
        </div>
        <h3 class="text-base font-bold text-slate-800">No Day / Shift Session Currently Open</h3>
        <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-4">Start your day or cashier shift by entering the starting register cash amount.</p>
        <button type="button" onclick="openStartModal()" class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-orange-600/30 transition inline-flex items-center gap-2">
            <i class="fa-solid fa-lock-open"></i>
            <span>Open New Shift / Day</span>
        </button>
    </div>
    @endif

    <!-- Past Closed Sessions History -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-800">Day / Shift Closing Archive</h3>
                <p class="text-xs text-slate-500">Historical records of closed registers & cash reconciliation</p>
            </div>
            <a href="{{ route('reports.dayclosing') }}" class="text-xs font-semibold text-orange-600 hover:underline">Full Report &rarr;</a>
        </div>

        @if($closedSessions->isEmpty())
        <div class="py-12 text-center text-slate-400">
            <p class="text-xs font-semibold">No past closed sessions yet.</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase font-semibold">
                        <th class="py-3 px-4">Shift Type</th>
                        <th class="py-3 px-4">Opened At</th>
                        <th class="py-3 px-4">Closed At</th>
                        <th class="py-3 px-4 text-right">Opening Cash</th>
                        <th class="py-3 px-4 text-right">Cash Sales</th>
                        <th class="py-3 px-4 text-right">Cash Exp</th>
                        <th class="py-3 px-4 text-right">Expected</th>
                        <th class="py-3 px-4 text-right">Actual Count</th>
                        <th class="py-3 px-4 text-right">Difference</th>
                        <th class="py-3 px-4">Closed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($closedSessions as $cs)
                    @php
                        $diff = (float) $cs->difference;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4 font-bold uppercase text-slate-700">{{ $cs->shift_type }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $cs->opened_at->format('d/m/Y h:i A') }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $cs->closed_at ? $cs->closed_at->format('d/m/Y h:i A') : '-' }}</td>
                        <td class="py-3 px-4 text-right text-slate-700 font-semibold">{{ $currency }}{{ number_format($cs->opening_cash, 2) }}</td>
                        <td class="py-3 px-4 text-right text-emerald-600 font-bold">+{{ $currency }}{{ number_format($cs->cash_sales, 2) }}</td>
                        <td class="py-3 px-4 text-right text-rose-600 font-bold">-{{ $currency }}{{ number_format($cs->cash_expenses, 2) }}</td>
                        <td class="py-3 px-4 text-right font-bold text-slate-900">{{ $currency }}{{ number_format($cs->expected_cash, 2) }}</td>
                        <td class="py-3 px-4 text-right font-black text-slate-900">{{ $currency }}{{ number_format($cs->actual_cash, 2) }}</td>
                        <td class="py-3 px-4 text-right font-black {{ $diff == 0 ? 'text-emerald-600' : ($diff > 0 ? 'text-blue-600' : 'text-rose-600') }}">
                            {{ $diff > 0 ? '+' : '' }}{{ $currency }}{{ number_format($diff, 2) }}
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $cs->closedByUser?->name ?? 'Staff' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100">
            {{ $closedSessions->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal 1: Open Shift / Day -->
<div id="startModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
            <h3 class="text-sm font-bold">Open Day / Shift Session</h3>
            <button type="button" onclick="document.getElementById('startModal').classList.add('hidden')" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('dayclosing.open') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Session Type *</label>
                <select name="shift_type" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold">
                    <option value="day">Full Day Closing</option>
                    <option value="shift">Shift Closing</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Opening Cash in Register ({{ $currency }}) *</label>
                <input type="number" step="0.01" min="0" name="opening_cash" value="0.00" required class="w-full px-3 py-2.5 rounded-xl border border-slate-300 font-black text-lg focus:ring-2 focus:ring-orange-500">
                <p class="text-[11px] text-slate-400 mt-1">Float cash present in the drawer at the start of the day.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Notes</label>
                <input type="text" name="notes" placeholder="Optional notes..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('startModal').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-xl text-xs font-bold">Open Session</button>
            </div>
        </form>
    </div>
</div>

@if($currentSession)
<!-- Modal 2: Cash Withdrawal -->
<div id="withdrawalModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-amber-900 text-white flex items-center justify-between">
            <h3 class="text-sm font-bold">Record Cash Withdrawal (Drop)</h3>
            <button type="button" onclick="document.getElementById('withdrawalModal').classList.add('hidden')" class="text-amber-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('dayclosing.withdrawal', $currentSession) }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Withdrawal Amount ({{ $currency }}) *</label>
                <input type="number" step="0.01" min="0.01" name="amount" required placeholder="0.00" class="w-full px-3 py-2 rounded-xl border border-slate-300 font-black text-lg">
                <p class="text-[11px] text-slate-400 mt-1">Cash removed from drawer (e.g. deposited to bank or safe).</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Reason / Notes</label>
                <input type="text" name="notes" placeholder="e.g. Mid-day safe drop" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs">
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('withdrawalModal').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold">Record Withdrawal</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Close Session -->
<div id="closeModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 bg-rose-900 text-white flex items-center justify-between">
            <h3 class="text-sm font-bold">Close {{ ucfirst($currentSession->shift_type) }}</h3>
            <button type="button" onclick="document.getElementById('closeModal').classList.add('hidden')" class="text-rose-200 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="{{ route('dayclosing.close', $currentSession) }}" class="p-6 space-y-4">
            @csrf
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
                <div class="flex justify-between">
                    <span class="text-slate-500">Expected Cash:</span>
                    <strong class="text-slate-900">{{ $currency }}{{ number_format($expectedCash, 2) }}</strong>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-800 uppercase mb-1">Actual Physical Cash Counted ({{ $currency }}) *</label>
                <input type="number" step="0.01" min="0" name="actual_cash" id="actualCashInput" oninput="updateDiff({{ $expectedCash }})" required placeholder="0.00" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 font-black text-xl text-slate-900 focus:ring-2 focus:ring-rose-500">
            </div>

            <div class="p-3 bg-slate-100 rounded-xl flex items-center justify-between text-xs">
                <span class="font-bold text-slate-600 uppercase">Cash Difference:</span>
                <span id="closeDiffDisplay" class="font-black text-lg text-slate-900">₹0.00</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Closing Remarks</label>
                <textarea name="notes" rows="2" placeholder="Any discrepancy notes..." class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2">
                <button type="button" onclick="document.getElementById('closeModal').classList.add('hidden')" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold">Confirm & Close Register</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
    function openStartModal() {
        document.getElementById('startModal').classList.remove('hidden');
    }
    function openWithdrawalModal() {
        document.getElementById('withdrawalModal').classList.remove('hidden');
    }
    function openCloseModal() {
        document.getElementById('closeModal').classList.remove('hidden');
    }
    function updateDiff(expected) {
        const actual = parseFloat(document.getElementById('actualCashInput').value) || 0;
        const diff = (actual - expected).toFixed(2);
        const el = document.getElementById('closeDiffDisplay');
        el.innerText = `₹${diff}`;
        el.className = diff >= 0 ? 'font-black text-lg text-emerald-600' : 'font-black text-lg text-rose-600';
    }
</script>
@endpush
@endsection
