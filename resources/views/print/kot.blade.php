<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOT #{{ $kotData['kot_number'] }} - Table {{ $kotData['table_number'] }}</title>
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

        .kot-container {
            background: #fff;
            padding: 12px;
            color: #000;
            font-size: 13px;
            line-height: 1.35;
            width: {{ ($kotData['printer_type'] ?? '80mm') === '58mm' ? '58mm' : '80mm' }};
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: 900; }
        .uppercase { text-transform: uppercase; }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .divider-double {
            border-top: 2px solid #000;
            margin: 6px 0;
        }

        .kot-badge {
            display: inline-block;
            border: 2px solid #000;
            padding: 2px 8px;
            font-size: 14px;
            font-weight: 900;
            margin: 2px 0;
        }

        .table-badge {
            font-size: 18px;
            font-weight: 900;
            padding: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 4px 0;
            vertical-align: top;
        }

        .item-name {
            font-size: 13px;
            font-weight: 900;
        }

        .item-qty {
            font-size: 15px;
            font-weight: 900;
            text-align: right;
            white-space: nowrap;
        }

        .item-notes {
            font-size: 11px;
            font-weight: bold;
            font-style: italic;
            padding-left: 6px;
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
            .kot-container {
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
        <button type="button" onclick="window.print()" class="btn">Print KOT</button>
        <button type="button" onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>

    <div class="kot-container">
        <!-- KOT Header -->
        <div class="text-center">
            <div class="kot-badge uppercase">
                *** KITCHEN ORDER TICKET ***
            </div>
            @if(!empty($kotData['is_reprint']))
            <div style="font-weight: bold; font-size: 11px; color: #000;">** REPRINT KOT **</div>
            @endif

            <div class="table-badge uppercase">
                TABLE: {{ $kotData['table_number'] }}
            </div>
        </div>

        <div class="divider-double"></div>

        <!-- Metadata -->
        <table style="font-size: 11px;">
            <tr>
                <td class="font-bold">KOT #: {{ $kotData['kot_number'] }}</td>
                <td class="text-right">{{ $kotData['date'] }}</td>
            </tr>
            <tr>
                <td>Order: {{ $kotData['invoice_number'] }}</td>
                <td class="text-right">{{ $kotData['time'] }}</td>
            </tr>
            @if(!empty($kotData['waiter']))
            <tr>
                <td colspan="2" class="font-bold">Waiter: {{ $kotData['waiter'] }}</td>
            </tr>
            @endif
            @if(!empty($kotData['order_notes']))
            <tr>
                <td colspan="2" style="font-style: italic;">Special: {{ $kotData['order_notes'] }}</td>
            </tr>
            @endif
        </table>

        <div class="divider-double"></div>

        <!-- Kitchen Items Table -->
        <table>
            <thead>
                <tr class="font-bold uppercase" style="border-bottom: 1px dashed #000; font-size: 11px;">
                    <th class="text-left" style="width: 75%;">Item Description</th>
                    <th class="text-right" style="width: 25%;">Qty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($kotData['items'] as $item)
                <tr style="border-bottom: 1px dotted #ccc;">
                    <td class="text-left">
                        <div class="item-name">{{ $item['name'] }}</div>
                        @if(!empty($item['addons']))
                            @foreach($item['addons'] as $addon)
                            <div style="font-size: 10px; padding-left: 6px;">+ {{ $addon['name'] }}</div>
                            @endforeach
                        @endif
                        @if(!empty($item['notes']))
                        <div class="item-notes">** Note: {{ $item['notes'] }}</div>
                        @endif
                    </td>
                    <td class="item-qty">[ {{ (float) $item['quantity'] }} ]</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider-double"></div>

        <!-- Summary -->
        <div style="display: flex; justify-content: space-between; font-size: 11px; font-weight: bold;">
            <span>Items: {{ count($kotData['items']) }}</span>
            <span>Total Qty: {{ (float) $kotData['total_quantity'] }}</span>
        </div>

        <div class="divider"></div>

        <div class="text-center" style="font-size: 10px; margin-top: 4px; font-style: italic;">
            -- Kitchen Copy -- Printed: {{ date('h:i A') }} --
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
