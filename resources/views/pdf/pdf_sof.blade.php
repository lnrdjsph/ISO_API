<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $order->sof_id }}</title>
    <style nonce="{{ $cspNonce ?? '' }}">
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #333;
            margin: 0px;
        }

        h2 {
            margin: 0 0 15px 0;
            padding: 0;
            font-size: 12px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }

        .section {
            border: none;
            border-radius: 4px;
            margin-bottom: 10px;
            padding: 10px;
        }

        .section-title {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 3px;
            padding-bottom: 3px;
            color: #111;
        }

        .info-table {
            width: 100%;
        }

        .info-table td {
            padding: 1px;
            vertical-align: center;
            font-size: 8px;
        }

        /* Ordered Items Table */
        table.order-items {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            table-layout: auto;
            /* allow flexible widths */
        }

        table.order-items th,
        table.order-items td {
            border: 1px solid #999;
            padding: 1px;
            text-align: center;
            vertical-align: center;
            word-wrap: break-word;
            /* wrap long words */
            white-space: normal;
            /* allow wrapping */
        }

        table.order-items th {
            background: #f7f7f7;
            font-weight: bold;
        }

        /* Fixed width columns */
        table.order-items th:nth-child(1),
        table.order-items td:nth-child(1) {
            width: 18px;
        }

        /* No. */

        table.order-items th:nth-child(2),
        table.order-items td:nth-child(2) {
            width: 40px;
        }

        /* SKU */

        table.order-items th:nth-child(4),
        table.order-items td:nth-child(4),
        table.order-items th:nth-child(5),
        table.order-items td:nth-child(5),
        table.order-items th:nth-child(6),
        table.order-items td:nth-child(6),
        table.order-items th:nth-child(7),
        table.order-items td:nth-child(7),
        table.order-items th:nth-child(8),
        table.order-items td:nth-child(8),
        table.order-items th:nth-child(9),
        table.order-items td:nth-child(9),
        table.order-items th:nth-child(10),
        table.order-items td:nth-child(10),
        table.order-items th:nth-child(11),
        table.order-items td:nth-child(11),
        table.order-items th:nth-child(12),
        table.order-items td:nth-child(12),
        table.order-items th:nth-child(14),
        table.order-items td:nth-child(14) {
            width: 50px;
            /* compact columns */
        }

        /* Flexible columns */
        table.order-items td:nth-child(3) {
            min-width: 120px;
            /* Item Description */
            text-align: left;
        }

        table.order-items th:nth-child(13),
        table.order-items td:nth-child(13) {
            min-width: 120px;
            /* Remarks */
            text-align: left;
        }

        /* Signature container */
        .signature-container {
            display: flex;
            justify-content: space-around;
            /* spaces them evenly */
            margin-top: 60px;
        }

        /* Individual signature blocks */
        .signature-area {
            text-align: center;
            font-size: 9px;
        }

        /* Signature line */
        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #000;
            width: 200px;
            margin-left: auto;
            margin-right: auto;
            padding-top: 3px;
        }
    </style>
</head>
@php
    $locationMap = [
        '4002' => 'F2 - Metro Wholesalemart Colon',
        '2010' => 'S10 - Metro Maasin',
        '2017' => 'S17 - Metro Tacloban',
        '2019' => 'S19 - Metro Bay-Bay',
        '3018' => 'F18 - Metro Alang-Alang',
        '3019' => 'F19 - Metro Hilongos',
        '2008' => 'S8 - Metro Toledo',
        '6012' => 'H8 - Super Metro Antipolo',
        '6009' => 'H9 - Super Metro Carcar',
        '6010' => 'H10 - Super Metro Bogo',
    ];
    $order->requesting_store_name = $locationMap[strtolower($order->requesting_store)] ?? $order->requesting_store;
@endphp

<body>

    <!-- Header -->
    <h2>Sales Order Form (SOF)</h2>

    <!-- Customer/Payment/Delivery/Order Info -->
    <div class="section">
        <table
            class="info-table"
            width="100%"
            cellspacing="0"
            cellpadding="6"
            style="border-collapse: collapse;">
            <tr>
                <!-- LEFT COLUMN: Customer + Payment + Delivery -->
                <td
                    width="50%"
                    valign="top"
                    style="vertical-align: top;">
                    <div class="section-title">Customer Info</div>
                    <table
                        width="100%"
                        style="border: none;">
                        <tr>
                            <td width="40%"><strong>MRC Card No:</strong></td>
                            <td>{{ $order->mbc_card_no }}</td>
                        </tr>
                        <tr>
                            <td><strong>Customer Name:</strong></td>
                            <td>{{ $order->customer_name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Contact No:</strong></td>
                            <td>{{ $order->contact_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>{{ $order->email }}</td>
                        </tr>
                    </table>



                    <div
                        class="section-title"
                        style="margin-top:10px;">Delivery Info</div>
                    <table
                        width="100%"
                        style="border: none;">
                        <tr>
                            <td width="40%"><strong>Dispatch:</strong></td>
                            <td>{{ $order->mode_dispatching }}</td>
                        </tr>
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td>{{ \Carbon\Carbon::parse($order->delivery_date)->format('m/d/Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Address:</strong></td>
                            <td>{{ $order->address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Landmark:</strong></td>
                            <td>{{ $order->landmark ?? '-' }}</td>
                        </tr>
                    </table>
                </td>

                <!-- RIGHT COLUMN: Order Info -->
                <td
                    width="50%"
                    valign="top"
                    style="vertical-align: top;">
                    <div class="section-title">Payment Info</div>
                    <table
                        width="100%"
                        style="border: none;">
                        <tr>
                            <td width="40%"><strong>Center:</strong></td>
                            <td>{{ $order->payment_center }}</td>
                        </tr>
                        <tr>
                            <td><strong>Mode:</strong></td>
                            <td>{{ $order->mode_payment }}</td>
                        </tr>
                        <tr>
                            <td><strong>Date:</strong></td>
                            <td>{{ \Carbon\Carbon::parse($order->payment_date)->format('m/d/Y') }}</td>
                        </tr>
                    </table>

                    <div
                        class="section-title"
                        style="margin-top:10px;">Order Info</div>
                    <table
                        width="100%"
                        style="border: none;">
                        <tr>
                            <td width="40%"><strong>SOF ID:</strong></td>
                            <td>{{ $order->sof_id }}</td>
                        </tr>
                        <tr>
                            <td><strong>Store:</strong></td>
                            <td>{{ $order->requesting_store_name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Personnel:</strong></td>
                            <td>{{ \App\Models\User::find($order->requested_by)?->name ?? $order->requested_by }}</td>
                        </tr>
                        <tr>
                            <td><strong>Channel:</strong></td>
                            <td>{{ $order->channel_order }}</td>
                        </tr>
                        <tr>
                            <td><strong>Date/Time:</strong></td>
                            <td>{{ \Carbon\Carbon::parse($order->time_order)->format('M d, Y - h:i A') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Status:</strong></td>
                            <td>{{ ucwords($order->order_status) }}</td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>

    </div>

    <!-- Ordered Items -->
    <div class="section">
        <div class="section-title">Ordered Items</div>

        <table
            class="order-items"
            style="border-collapse: collapse; width: 100%;">
            <thead>
                <tr>
                    <th rowspan="2">No.</th>
                    <th rowspan="2">SKU</th>
                    <th rowspan="2">Item Description</th>
                    <th rowspan="2">Scheme</th>
                    <th rowspan="2">Price/PC</th>
                    <th rowspan="2">Price</th>
                    <th rowspan="2">Discount</th>
                    <th colspan="4">Order in Cases</th>
                    <th rowspan="2">Amount</th>
                    <th rowspan="2">Remarks</th>
                    <th rowspan="2">Store Order No.</th>
                </tr>
                <tr>
                    <th>QTY/PC</th>
                    <th>QTY/CS</th>
                    <th>Freebies</th>
                    <th>Total QTY</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->sku }}</td>
                        <td>{{ $item->item_description }}</td>
                        <td>{{ $item->scheme ?? '-' }}</td>
                        <td>{{ number_format($item->price_per_pc, 2) }}</td>
                        <td>{{ number_format($item->price, 2) }}</td>
                        <td>{{ $item->discount ? $item->discount : '-' }}</td>
                        <td>{{ $item->qty_per_pc }}</td>
                        <td>{{ $item->qty_per_cs }}</td>
                        <td>{{ $item->freebies_per_cs ?? 'N/A' }}</td>
                        <td>{{ $item->total_qty }}</td>
                        <td>{{ number_format($item->amount, 2) }}</td>
                        <td>{{ $item->remarks }}</td>
                        <td>{{ $item->store_order_no ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td
                        colspan="11"
                        style="text-align: right; font-weight: bold;">Total Amount:</td>
                    <td style="font-weight: bold;">
                        {{ number_format($order->items->sum('amount'), 2) }}
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

    </div>
    <!-- Signature Container (Table) -->
    <!-- Signature Container (Table) -->
    @php
        $storeCode = strtolower($order->requesting_store);
        $storeName = $locationMap[$storeCode] ?? $storeCode;
        $approver = $order->approver_name; // pulled from model
    @endphp
    <table style="width:100%; margin-top:60px; text-align:center; border:0;">
        <tr>
            <td style="width:50%; text-align:center; vertical-align:bottom;">
                <span style="font-size: 10px; text-transform: uppercase; padding-bottom:3px;">
                    {{ \App\Models\User::find($order->requested_by)?->name ?? $order->requested_by }}
                </span>
                <div style="border-top:1px solid #000; width:200px; margin:0 auto; font-size:9px; padding-top:3px;">
                    Prepared by
                </div>
            </td>
            <td style="width:50%; text-align:center; vertical-align:bottom;">
                <span style="font-size: 10px; text-transform: uppercase; padding-bottom:3px;">
                    {{ strtoupper($order->approver_name ?? 'N/A') }}

                </span>
                <div style="border-top:1px solid #000; width:200px; margin:0 auto; font-size:9px; padding-top:3px;">
                    Approved by
                </div>
            </td>
        </tr>

    </table>



</body>

</html>
