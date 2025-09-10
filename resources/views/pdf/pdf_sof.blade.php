<!DOCTYPE html>
<html>

<head>
		<meta charset="UTF-8">
		<title>{{ $order->sof_id }}</title>
		<style>
				body {
						font-family: DejaVu Sans, sans-serif;
						font-size: 11px;
						color: #333;
						margin: 0px;
				}

				h2 {
						margin: 0 0 15px 0;
						padding: 0;
						font-size: 16px;
						text-align: center;
						font-weight: bold;
						text-transform: uppercase;
				}

				.section {
						border: 1px solid #ccc;
						border-radius: 4px;
						margin-bottom: 20px;
						padding: 10px;
				}

				.section-title {
						font-weight: bold;
						font-size: 12px;
						margin-bottom: 5px;
						padding-bottom: 3px;
						color: #111;
				}

				.info-table {
						width: 100%;
				}

				.info-table td {
						padding: 1px;
						vertical-align: center;
						font-size: 10px;
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
		</style>
</head>
@php
		$locationMap = [
		    'f2' => 'F2 - Metro Wholesalemart Colon',
		    's10' => 'S10 - Metro Maasin',
		    's17' => 'S17 - Metro Tacloban',
		    's19' => 'S19 - Metro Bay-Bay',
		    'f18' => 'F18 - Metro Alang-Alang',
		    'f19' => 'F19 - Metro Hilongos',
		    's8' => 'S8 - Metro Toledo',
		    'h8' => 'H8 - Super Metro Antipolo',
		    'h9' => 'H9 - Super Metro Carcar',
		    'h10' => 'H10 - Super Metro Bogo',
		];
		$order->requesting_store = $locationMap[strtolower($order->requesting_store)] ?? $order->requesting_store;
@endphp

<body>

		<!-- Header -->
		<h2>Sales Order Form (SOF)</h2>

		<!-- Customer/Payment/Delivery/Order Info -->
		<div class="section">
				<table class="info-table">
						<tr>
								<td width="25%">
										<div class="section-title">Customer Info</div>
										<strong>MRC Card No:</strong> {{ $order->mbc_card_no }}<br>
										<strong>Customer Name:</strong> {{ $order->customer_name }}<br>
										<strong>Contact No:</strong> {{ $order->contact_number }}
								</td>
								<td width="25%">
										<div class="section-title">Payment Info</div>
										<strong>Center:</strong> {{ $order->payment_center }}<br>
										<strong>Mode:</strong> {{ $order->mode_payment }}<br>
										<strong>Date:</strong> {{ \Carbon\Carbon::parse($order->payment_date)->format('m/d/Y') }}
								</td>
								<td width="25%">
										<div class="section-title">Delivery Info</div>
										<strong>Dispatch:</strong> {{ $order->mode_dispatching }}<br>
										<strong>Date:</strong> {{ \Carbon\Carbon::parse($order->delivery_date)->format('m/d/Y') }}<br>
										<strong>Address:</strong> {{ $order->delivery_address ?? '-' }}<br>
										<strong>Landmark:</strong> {{ $order->delivery_landmark ?? '-' }}
								</td>
								<td width="25%">
										<div class="section-title">Order Info</div>
										<strong>SOF ID:</strong> {{ $order->sof_id }}<br>
										<strong>Store & Personnel:</strong> {{ $order->requesting_store }} - {{ \App\Models\User::find($order->requested_by)?->name ?? $order->requested_by }}<br>
										<strong>Channel:</strong> {{ $order->channel_order }}<br>
										<strong>Date/Time:</strong> {{ \Carbon\Carbon::parse($order->time_order)->format('M d, Y - h:i A') }}<br>
										<strong>Status:</strong> {{ ucwords($order->order_status) }}
								</td>
						</tr>
				</table>
		</div>

		<!-- Ordered Items -->
		<div class="section">
				<div class="section-title">Ordered Items</div>

				<table class="order-items">
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
												<td>{{ number_format($item->price_pc, 2) }}</td>
												<td>{{ number_format($item->price, 2) }}</td>
												<td>{{ $item->discount ? $item->discount : '-' }}</td>
												<td>{{ $item->qty_per_pc }}</td>
												<td>{{ $item->qty_per_cs }}</td>
												<td>{{ $item->freebies ?? 'N/A' }}</td>
												<td>{{ $item->total_qty }}</td>
												<td>{{ number_format($item->amount, 2) }}</td>
												<td>{{ $item->remarks }}</td>
												<td>{{ $item->store_order_no ?? '-' }}</td>
										</tr>
								@endforeach
						</tbody>
				</table>
		</div>

</body>

</html>
