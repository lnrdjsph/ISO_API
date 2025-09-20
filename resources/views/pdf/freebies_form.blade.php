<!DOCTYPE html>
<html>

<head>
		<style>
				body {
						font-size: 12px;
				}

				table {
						width: 100%;
						border-collapse: collapse;
				}

				th,
				td {
						border: 1px solid black;
						padding: 4px;
						text-align: left;
				}

				.header-table td {
						border: 0;
						font-size: 12px;
				}

				.underline {
						border-bottom: 1px solid black;
						display: inline-block;
						width: 60%;
						text-align: center;
						font-weight: normal;
				}

				.signature-table td {
						border: 0;
						padding-top: 40px;
				}
		</style>
</head>

<body>
		<h2 style="text-align: center;">FREEBIES FORM</h2>

		<table
				class="header-table"
				style="margin-bottom: 20px;"
		>
				<tr>
						<td>
								<strong>CUSTOMER'S NAME:</strong>
								<span class="underline">{{ $customer_name }}</span>
						</td>
						<td style="text-align: right;">
								<strong>DATE:</strong>
								<span class="underline">{{ $date }}</span>
						</td>
				</tr>
		</table>

		<table>
				<thead>
						<tr>
								<th
										colspan="3"
										style="text-align: center;"
								>PRINCIPAL ITEM</th>
								<th
										rowspan="2"
										style="text-align: center;"
								>SCHEME</th>
								<th
										colspan="3"
										style="text-align: center;"
								>FREEBIE ITEM</th>
						</tr>
						<tr>
								<th>SKU</th>
								<th>DESCRIPTION</th>
								<th>QTY</th>
								<th>SKU</th>
								<th>DESCRIPTION</th>
								<th>QTY</th>
						</tr>
				</thead>
				<tbody>
						@foreach ($rows as $row)
								<tr>
										<td>{{ $row['main_sku'] }}</td>
										<td>{{ $row['main_description'] }}</td>
										<td>{{ $row['total_main_qty'] }} pcs / {{ $row['main_qty'] }} cs</td>
										<td>{{ $row['main_scheme'] }}</td>
										<td>{{ $row['freebie_sku'] }}</td>
										<td>{{ $row['freebie_description'] }}</td>
										<td>{{ $row['total_freebie_qty'] }} pcs / {{ $row['freebie_qty'] }} cs</td>
								</tr>
						@endforeach

						{{-- Ensure at least 8 rows --}}
						@for ($i = count($rows); $i < 8; $i++)
								<tr>
										<td>&nbsp;</td>
										<td></td>
										<td></td>
										<td></td>
										<td></td>
										<td></td>
										<td></td>
								</tr>
						@endfor
				</tbody>

		</table>

		<table
				class="signature-table"
				style="margin-top: 40px; width: 100%;"
		>
				<tr>
						<td>
								<strong>PREPARED BY:</strong>
								<span class="underline">&nbsp;</span>
						</td>
						<td>
								<strong>RELEASED BY:</strong>
								<span class="underline">&nbsp;</span>
						</td>
				</tr>
				<tr>
						<td>
								<strong>APPROVED BY:</strong>
								<span class="underline">&nbsp;</span>
						</td>
						<td>
								<strong>CLAIMED BY:</strong>
								<span class="underline">&nbsp;</span>
						</td>
				</tr>
		</table>
</body>

</html>
