<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Ledger Statement - {{ $customer->name }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.5;
        }
        .container {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #ea580c;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
        }
        .header p {
            margin: 2px 0;
            color: #64748b;
            font-size: 12px;
        }
        .statement-title {
            text-align: right;
        }
        .statement-title h2 {
            font-size: 18px;
            font-weight: 800;
            color: #ea580c;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .statement-title span {
            font-size: 11px;
            color: #64748b;
        }
        .customer-card {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .customer-card h3 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
        }
        .customer-card p {
            margin: 2px 0;
            font-size: 12px;
            color: #475569;
        }
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .kpi-box {
            padding: 10px 12px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }
        .kpi-box .label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
        }
        .kpi-box .val {
            font-size: 16px;
            font-weight: 800;
            margin-top: 2px;
            font-family: monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }
        th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            border-bottom: 2px solid #cbd5e1;
            text-align: left;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
        }
        tr:hover td {
            background: #fdfdfe;
        }
        .text-right {
            text-align: right;
        }
        .font-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
        .badge-debit {
            color: #b91c1c;
            font-weight: 700;
        }
        .badge-credit {
            color: #047857;
            font-weight: 700;
        }
        .closing-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0f172a;
            color: #ffffff;
            padding: 14px 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .closing-banner .label {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .closing-banner .amount {
            font-size: 20px;
            font-weight: 900;
            font-family: monospace;
            color: #fb923c;
        }
        .footer-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e1;
            font-size: 11px;
            color: #64748b;
        }
        .print-btn-bar {
            max-width: 850px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-print {
            background: #ea580c;
            color: white;
        }
        .btn-close {
            background: #64748b;
            color: white;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                border: none;
                padding: 0;
            }
            .print-btn-bar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="print-btn-bar">
        <button onclick="window.print()" class="btn btn-print">Print Statement</button>
        <button onclick="window.close()" class="btn btn-close">Close Window</button>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1>{{ $restaurantName }}</h1>
                @if($restaurantAddress) <p>{{ $restaurantAddress }}</p> @endif
                @if($restaurantContact) <p>Contact: {{ $restaurantContact }}</p> @endif
                @if($restaurantGstin) <p>GSTIN: {{ $restaurantGstin }}</p> @endif
            </div>
            <div class="statement-title">
                <h2>Customer Statement</h2>
                <span>Date: {{ now()->format('d M Y, h:i A') }}</span>
                @if($startDate || $endDate)
                <div style="font-size: 11px; font-weight: bold; margin-top: 4px; color: #334155;">
                    Period: {{ $startDate ?: 'Inception' }} to {{ $endDate ?: 'Present' }}
                </div>
                @endif
            </div>
        </div>

        <!-- Customer Card -->
        <div class="customer-card">
            <div>
                <h3>{{ $customer->name }}</h3>
                <p>Phone: <strong>{{ $customer->phone ?: 'N/A' }}</strong></p>
                <p>Email: {{ $customer->email ?: 'N/A' }}</p>
            </div>
            <div>
                <p>GSTIN: {{ $customer->gstin ?: 'N/A' }}</p>
                @if($customer->address)
                <p>Address: {{ $customer->address }}</p>
                @endif
                <p>Credit Limit: {{ $customer->credit_limit > 0 ? $currency . number_format($customer->credit_limit, 2) : 'No Limit' }}</p>
            </div>
        </div>

        <!-- KPI Row -->
        <div class="kpi-row">
            <div class="kpi-box">
                <div class="label">Opening Balance</div>
                <div class="val" style="color: {{ $openingBalance > 0 ? '#b91c1c' : ($openingBalance < 0 ? '#047857' : '#334155') }};">
                    {{ $currency }}{{ number_format(abs($openingBalance), 2) }} {{ $openingBalance > 0 ? 'Dr' : ($openingBalance < 0 ? 'Cr' : '') }}
                </div>
            </div>
            <div class="kpi-box">
                <div class="label" style="color: #b91c1c;">Total Debits (+)</div>
                <div class="val" style="color: #b91c1c;">
                    {{ $currency }}{{ number_format($periodDebits, 2) }}
                </div>
            </div>
            <div class="kpi-box">
                <div class="label" style="color: #047857;">Total Credits (-)</div>
                <div class="val" style="color: #047857;">
                    {{ $currency }}{{ number_format($periodCredits, 2) }}
                </div>
            </div>
            <div class="kpi-box">
                <div class="label">Net Closing Due</div>
                <div class="val" style="color: {{ $closingBalance > 0 ? '#b91c1c' : ($closingBalance < 0 ? '#047857' : '#334155') }};">
                    {{ $currency }}{{ number_format(abs($closingBalance), 2) }} {{ $closingBalance > 0 ? 'Dr' : ($closingBalance < 0 ? 'Cr' : '') }}
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 14%;">Date</th>
                    <th style="width: 10%;">Type</th>
                    <th style="width: 16%;">Reference / Bill</th>
                    <th>Description</th>
                    <th class="text-right" style="width: 13%;">Debit (₹)</th>
                    <th class="text-right" style="width: 13%;">Credit (₹)</th>
                    <th class="text-right" style="width: 14%;">Balance (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ledgers as $entry)
                <tr>
                    <td class="font-mono">{{ $entry->entry_date->format('d/m/Y') }}</td>
                    <td>
                        <span class="{{ $entry->type === 'debit' ? 'badge-debit' : 'badge-credit' }}">
                            {{ strtoupper($entry->type) }}
                        </span>
                    </td>
                    <td>
                        @if($entry->bill)
                            #{{ $entry->bill->invoice_number }}
                        @elseif($entry->reference_number)
                            {{ $entry->reference_number }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $entry->description }}</td>
                    <td class="text-right font-mono badge-debit">
                        {{ $entry->type === 'debit' ? number_format($entry->amount, 2) : '-' }}
                    </td>
                    <td class="text-right font-mono badge-credit">
                        {{ $entry->type === 'credit' ? number_format($entry->amount, 2) : '-' }}
                    </td>
                    <td class="text-right font-mono" style="font-weight: 700;">
                        {{ number_format(abs($entry->balance_after), 2) }}
                        <span style="font-size: 10px; font-weight: normal;">{{ $entry->balance_after > 0 ? 'Dr' : ($entry->balance_after < 0 ? 'Cr' : '') }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 25px; color: #94a3b8;">
                        No transactions recorded for this customer in this period.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Closing Balance Banner -->
        <div class="closing-banner">
            <div class="label">
                @if($closingBalance > 0.001)
                    Total Outstanding Due Balance (Receivable)
                @elseif($closingBalance < -0.001)
                    Advance Credit Balance (Customer Deposit)
                @else
                    Account Settled (Zero Balance)
                @endif
            </div>
            <div class="amount">
                {{ $currency }}{{ number_format(abs($closingBalance), 2) }}
                <span style="font-size: 14px; font-weight: normal;">{{ $closingBalance > 0 ? 'Due' : ($closingBalance < 0 ? 'Advance' : '') }}</span>
            </div>
        </div>

        <!-- Signatures -->
        <div class="footer-signatures">
            <div>
                <p>Prepared By: ___________________________</p>
                <p>Staff Signature & Stamp</p>
            </div>
            <div style="text-align: right;">
                <p>Customer Signature: ___________________________</p>
                <p>Acknowledgment</p>
            </div>
        </div>
    </div>
</body>
</html>

