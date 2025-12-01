<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>INVOICE-{{ $order->sof_id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #333;
            margin: 0px;
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
            max-width: 100%;
            /* force table not to exceed page width */
            border-collapse: collapse;
            font-size: 8px;
            table-layout: fixed;
            /* force equal distribution of widths */
            word-wrap: break-word;
        }

        table.order-items th,
        table.order-items td {
            border: 1px solid #999;
            padding: 1px;
            text-align: center;
            vertical-align: middle;
            white-space: normal;
            /* allow wrapping */
            word-break: break-word;
            /* break long words */
            overflow-wrap: break-word;
        }


        table.order-items th {
            background: #f7f7f7;
            font-weight: bold;
        }



        /* Flexible columns */
        table.order-items td:nth-child(3) {
            min-width: 120px !important;
            /* Item Description */
            text-align: left;
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

    $src = public_path('images/metro-white-logo-80.png');
    $dest = storage_path('app/public/metro-black-logo.png');

    $image = imagecreatefrompng($src);

    // Invert colors pixel by pixel
    imagefilter($image, IMG_FILTER_NEGATE);

    // Save to new file
    imagepng($image, $dest);
    imagedestroy($image);
@endphp

<body>
    <div style="display:flex; align-items:center; justify-content:center; position:relative; width:100%; margin-bottom:30px;">
        <!-- Logo on the left -->
        <div style="position:absolute; left:15px; top:0;">
            <img
                src="{{ public_path('images/MRSGI-LOGIN-LOGO.png') }}"
                width="75"
                alt="Metro Logo">
        </div>

        <!-- Centered Title -->
        <h2 style="margin:0; margin-top:25px; text-align:center;">SOF Invoice</h2>
    </div>

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
                            <td>{{ $order->delivery_address ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Landmark:</strong></td>
                            <td>{{ $order->delivery_landmark ?? '-' }}</td>
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
            style="border-collapse: collapse; width: 100%; ">
            <thead>
                <tr>
                    <th rowspan="2">No.</th>
                    <th rowspan="2">SKU</th>
                    <th
                        rowspan="2"
                        colspan="2">Item Description</th>
                    <th rowspan="2">Scheme</th>
                    <th rowspan="2">Price/PC</th>
                    <th rowspan="2">Price</th>
                    <th rowspan="2">Discount</th>
                    <th colspan="2">Order in Cases</th>
                    <th
                        rowspan="2"
                        colspan="2">Amount</th>
                    {{-- <th rowspan="2">Remarks</th>
										<th rowspan="2">Store Order No.</th> --}}
                </tr>
                <tr>
                    <th>QTY/PC</th>
                    <th>QTY/CS</th>
                    {{-- <th>Freebies</th>
										<th>Total QTY</th> --}}
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->sku }}</td>
                        <td colspan="2">{{ $item->item_description }}</td>
                        <td>{{ $item->scheme ?? '-' }}</td>
                        <td>{{ number_format($item->price_per_pc, 2) }}</td>
                        <td>{{ number_format($item->price, 2) }}</td>
                        <td>{{ $item->discount ? $item->discount : '-' }}</td>
                        <td>{{ $item->qty_per_pc }}</td>
                        {{-- <td>{{ $item->qty_per_cs }}</td>
												<td>{{ $item->freebies_per_cs ?? 'N/A' }}</td> --}}
                        <td>{{ $item->total_qty }}</td>
                        <td colspan="2">{{ number_format($item->amount, 2) }}</td>
                        {{-- <td>{{ $item->remarks }}</td>
												<td>{{ $item->store_order_no ?? '-' }}</td> --}}
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                {{-- <tr>
										<td
												colspan="12"
												style="padding:7px;"
										> </td>

								</tr> --}}
                @php
                    $freebieAmount = $order->items->filter(fn($item) => strtoupper(trim($item->scheme)) === 'FREEBIE')->sum('amount');
                @endphp

                @if ($freebieAmount > 0)
                    <tr>
                        <td
                            colspan="8"
                            style="border-right: none"></td>
                        <td
                            colspan="2"
                            style="padding-left:3px; text-align: left; font-weight: bold; border-left:none;">
                            Freebie Amount:
                        </td>
                        <td
                            style="text-align: center; font-weight: bold;"
                            colspan="2">
                            ₱{{ number_format($freebieAmount, 2) }}
                        </td>
                    </tr>
                @endif

                <tr>
                    <td
                        colspan="8"
                        style="border-right: none"></td>
                    <td
                        colspan="2"
                        style="padding-left:3px; text-align: left; font-weight: bold; border-left:none;">Total Payable Amount:</td>
                    <td
                        style="text-align: center; font-weight: bold;"
                        colspan="2">
                        ₱{{ number_format($order->items->filter(fn($item) => strtoupper(trim($item->scheme)) !== 'FREEBIE')->sum('amount'), 2) }}
                    </td>
                    {{-- <td colspan="2"></td> --}}
                </tr>
            </tfoot>
        </table>

    </div>




</body>

</html>
