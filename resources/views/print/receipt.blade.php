<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $receiptData['bill']['invoice_number'] }}</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace, -apple-system, sans-serif;
        }

        body {
            background-color: #f1f5f9;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .receipt-container {
            background: #fff;
            padding: 15px;
            color: #000;
            font-size: 12px;
            line-height: 1.4;
            /* Width determined by printer setting: 58mm (~48mm printable) or 80mm (~72mm printable) */
            width: {{ $receiptData['printer_type'] === '58mm' ? '58mm' : '80mm' }};
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .divider-double {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }

        .restaurant-title {
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.5px;
        }

        .copy-badge {
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 3px 0;
            vertical-align: top;
        }

        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            background: #ea580c;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
        }

        .btn-secondary {
            background: #1e293b;
        }

        @media print {
            body {
                background: transparent;
                padding: 0;
            }
            .receipt-container {
                width: 100%;
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- On-screen print controls -->
    <div class="no-print">
        <button type="button" onclick="window.print()" class="btn">Print Receipt</button>
        <button type="button" onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="receipt-container">
        <!-- Header -->
        <div class="text-center">
            @if(!empty($receiptData['restaurant']['logo']) && $receiptData['restaurant']['show_logo'])
            <img src="{{ asset('storage/' . $receiptData['restaurant']['logo']) }}" style="max-height: 45px; margin: 0 auto 5px;" alt="Logo">
            @endif

            <div class="restaurant-title uppercase">{{ $receiptData['restaurant']['name'] }}</div>
            <div>{{ $receiptData['restaurant']['address'] }}</div>
            <div>Tel: {{ $receiptData['restaurant']['contact'] }}</div>
            @if(!empty($receiptData['restaurant']['gstin']))
            <div class="font-bold">GSTIN: {{ $receiptData['restaurant']['gstin'] }}</div>
            @endif

            @if(!empty($receiptData['receipt']['header']))
            <div style="margin-top: 4px; font-style: italic;">{{ $receiptData['receipt']['header'] }}</div>
            @endif

            <!-- Copy type / reprint badge -->
            <div>
                <span class="copy-badge uppercase">
                    @if($receiptData['print_type'] === 'reprint')
                        ** REPRINT COPY **
                    @elseif($receiptData['print_type'] === 'duplicate')
                        ** DUPLICATE COPY **
                    @elseif($receiptData['copy_type'] === 'restaurant')
                        RESTAURANT KITCHEN COPY
                    @else
                        CUSTOMER TAX INVOICE
                    @endif
                </span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Bill Metadata -->
        <table style="font-size: 11px;">
            <tr>
                <td class="font-bold">Invoice: {{ $receiptData['bill']['invoice_number'] }}</td>
                <td class="text-right">Date: {{ $receiptData['bill']['date'] }}</td>
            </tr>
            <tr>
                <td>Cashier: {{ $receiptData['bill']['cashier'] }}</td>
                <td class="text-right">Time: {{ $receiptData['bill']['time'] }}</td>
            </tr>
            <tr>
                <td class="font-bold">Type: {{ $receiptData['bill']['order_type'] }}</td>
                @if($receiptData['bill']['table_number'])
                <td class="text-right font-bold">Table: {{ $receiptData['bill']['table_number'] }}</td>
                @else
                <td class="text-right">Counter</td>
                @endif
            </tr>
            @if(!empty($receiptData['bill']['waiter']))
            <tr>
                <td colspan="2"><span class="font-bold">Waiter:</span> {{ $receiptData['bill']['waiter'] }}</td>
            </tr>
            @endif
            @if(!empty($receiptData['bill']['customer_name']))
            <tr>
                <td colspan="2">Customer: {{ $receiptData['bill']['customer_name'] }}</td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>

        <!-- Line Items -->
        <table>
            <thead>
                <tr class="font-bold uppercase" style="border-bottom: 1px dashed #000;">
                    <th class="text-left" style="width: 50%;">Item</th>
                    <th class="text-center" style="width: 15%;">Qty</th>
                    <th class="text-right" style="width: 15%;">Rate</th>
                    <th class="text-right" style="width: 20%;">Amt</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receiptData['items'] as $item)
                <tr>
                    <td class="text-left">
                        <span class="font-bold">{{ $item['name'] }}</span>
                        @if(!empty($item['hsn_code']))
                        <div style="font-size: 9px; font-family: monospace; color: #333;">HSN: {{ $item['hsn_code'] }}</div>
                        @endif
                        @if(!empty($item['addons']))
                            @foreach($item['addons'] as $addon)
                            <div style="font-size: 10px; padding-left: 6px;">+ {{ $addon['name'] }} ({{ $receiptData['bill']['currency'] }}{{ number_format($addon['price'], 2) }})</div>
                            @endforeach
                        @endif
                        @if(!empty($item['notes']))
                        <div style="font-size: 10px; font-style: italic;">Note: {{ $item['notes'] }}</div>
                        @endif
                    </td>
                    <td class="text-center font-bold">{{ (float) $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['unit_price'], 2) }}</td>
                    <td class="text-right font-bold">{{ number_format($item['total'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <!-- Financial Summary -->
        <table>
            <tr>
                <td class="text-left">Subtotal:</td>
                <td class="text-right">{{ $receiptData['bill']['currency'] }}{{ number_format($receiptData['bill']['subtotal'], 2) }}</td>
            </tr>
            @if($receiptData['bill']['discount_amount'] > 0)
            <tr>
                <td class="text-left">Discount ({{ $receiptData['bill']['discount_type'] === 'percentage' ? $receiptData['bill']['discount_value'].'%' : 'Flat' }}):</td>
                <td class="text-right">-{{ $receiptData['bill']['currency'] }}{{ number_format($receiptData['bill']['discount_amount'], 2) }}</td>
            </tr>
            @endif
            @if($receiptData['bill']['cgst_total'] > 0)
            <tr>
                <td class="text-left">CGST:</td>
                <td class="text-right">{{ $receiptData['bill']['currency'] }}{{ number_format($receiptData['bill']['cgst_total'], 2) }}</td>
            </tr>
            @endif
            @if($receiptData['bill']['sgst_total'] > 0)
            <tr>
                <td class="text-left">SGST:</td>
                <td class="text-right">{{ $receiptData['bill']['currency'] }}{{ number_format($receiptData['bill']['sgst_total'], 2) }}</td>
            </tr>
            @endif
            @if($receiptData['bill']['rounding_difference'] != 0)
            <tr>
                <td class="text-left">Round Off:</td>
                <td class="text-right">{{ $receiptData['bill']['currency'] }}{{ number_format($receiptData['bill']['rounding_difference'], 2) }}</td>
            </tr>
            @endif
        </table>

        <div class="divider-double"></div>

        <!-- Grand Total -->
        <table style="font-size: 15px;">
            <tr class="font-bold">
                <td class="text-left">GRAND TOTAL:</td>
                <td class="text-right">{{ $receiptData['bill']['currency'] }}{{ number_format($receiptData['bill']['grand_total'], 2) }}</td>
            </tr>
        </table>

        <div class="divider-double"></div>

        <!-- Payment Details -->
        @if(!empty($receiptData['payments']))
        <table style="font-size: 11px;">
            @foreach($receiptData['payments'] as $pay)
            <tr>
                <td>Paid by {{ $pay['method'] }}:</td>
                <td class="text-right font-bold">{{ $receiptData['bill']['currency'] }}{{ number_format($pay['amount'], 2) }}</td>
            </tr>
            @if(!empty($pay['reference']))
            <tr>
                <td colspan="2" style="font-size: 10px;">Ref: {{ $pay['reference'] }}</td>
            </tr>
            @endif
            @endforeach
        </table>
        <div class="divider"></div>
        @endif

        <!-- QR Code for Payment -->
        @if(!empty($receiptData['qr']['show_qr']))
        <div class="divider"></div>
        <div class="text-center" style="margin: 10px 0;">
            @if($receiptData['qr']['qr_type'] === 'uploaded_image' && !empty($receiptData['qr']['image']))
                <img src="{{ asset('storage/' . $receiptData['qr']['image']) }}" style="width: 125px; height: 125px; margin: 0 auto; display: block; border: 1px solid #ddd; padding: 2px;" alt="UPI QR">
                <div class="font-bold uppercase" style="font-size: 11px; margin-top: 4px;">SCAN TO PAY VIA UPI</div>
                @if(!empty($receiptData['qr']['upi_id']))
                <div style="font-size: 10px; font-family: monospace; font-weight: bold;">{{ $receiptData['qr']['upi_id'] }}</div>
                @endif
            @elseif(!empty($receiptData['qr']['upi_uri']))
                <!-- Dynamic UPI QR with Bill Amount -->
                <div id="receiptQrCode" style="display: flex; justify-content: center; margin: 0 auto;"></div>
                <div class="font-bold uppercase" style="font-size: 11px; margin-top: 4px;">
                    SCAN & PAY {{ $receiptData['bill']['currency'] }}{{ number_format($receiptData['bill']['grand_total'], 2) }}
                </div>
                <div style="font-size: 9px; color: #444;">Scan with GPay, PhonePe, Paytm, BHIM</div>
                @if(!empty($receiptData['qr']['upi_id']))
                <div style="font-size: 9px; font-family: monospace; color: #222;">UPI: {{ $receiptData['qr']['upi_id'] }}</div>
                @endif
            @elseif(!empty($receiptData['qr']['image']))
                <img src="{{ asset('storage/' . $receiptData['qr']['image']) }}" style="width: 125px; height: 125px; margin: 0 auto; display: block; border: 1px solid #ddd; padding: 2px;" alt="UPI QR">
                <div class="font-bold uppercase" style="font-size: 11px; margin-top: 4px;">SCAN TO PAY VIA UPI</div>
            @endif
        </div>
        @endif

        <!-- Footer -->
        <div class="text-center" style="margin-top: 8px;">
            @if(!empty($receiptData['receipt']['footer']))
            <p>{{ $receiptData['receipt']['footer'] }}</p>
            @endif
            <p style="font-size: 10px; margin-top: 5px;">Printed: {{ date('d/m/Y h:i A') }}</p>
        </div>
    </div>

    <script>
        @if(!empty($receiptData['qr']['show_qr']) && !empty($receiptData['qr']['upi_uri']) && $receiptData['qr']['qr_type'] !== 'uploaded_image')
        try {
            const qrTarget = document.getElementById("receiptQrCode");
            const upiText = {!! json_encode($receiptData['qr']['upi_uri']) !!};
            if (qrTarget && typeof QRCode !== 'undefined') {
                new QRCode(qrTarget, {
                    text: upiText,
                    width: 120,
                    height: 120,
                    colorDark : "#000000",
                    colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.M
                });
            } else if (qrTarget) {
                const img = document.createElement('img');
                img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + encodeURIComponent(upiText);
                img.style.width = '120px';
                img.style.height = '120px';
                img.style.margin = '0 auto';
                qrTarget.appendChild(img);
            }
        } catch(e) {
            console.error('QR code generation error:', e);
        }
        @endif

        // Auto print upon opening if printer configured
        window.addEventListener('load', () => {
            // setTimeout(() => window.print(), 300);
        });
    </script>
</body>
</html>
