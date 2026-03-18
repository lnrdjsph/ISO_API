<!DOCTYPE html>
<html>

<head>
    <style nonce="{{ $cspNonce }}">
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
            padding: 2px 0;
        }

        .underline {
            border-bottom: 1px solid black;
            display: inline-block;
            width: 60%;
            padding: 5px 10px;
            margin: 0;
        }

        .signature-table td {
            border: 0;
            padding-top: 40px;
        }
    </style>
</head>

<body>
    <h2 style="text-align: center;">ORDER SLIP FORM</h2>

    <table
        class="header-table"
        style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 15%;"><strong>NAME:</strong></td>
            <td style="width: 35%;"><span class="underline">{{ $customer_name }}</span></td>
            <td style="width: 15%;"><strong>DATE:</strong></td>
            <td style="width: 35%;"><span class="underline">{{ $date }}</span></td>
        </tr>
        <tr>
            <td><strong>ADDRESS:</strong></td>
            <td><span class="underline">{{ $address }}</span></td>
            <td><strong>MODE OF PAYMENT:</strong></td>
            <td><span class="underline">{{ $payment_mode }}</span></td>
        </tr>
        <tr>
            <td><strong>TELEPHONE NO.:</strong></td>
            <td><span class="underline">{{ $telephone }}</span></td>
            <td><strong>SCHEME:</strong></td>
            <td><span class="underline">{{ $scheme }}</span></td>
        </tr>
        <tr>
            <td><strong>Email:</strong></td>
            <td><span class="underline">{{ $email }}</span></td>
            <td><strong>CASHIER:</strong></td>
            <td><span class="underline">{{ $cashier }}</span></td>

        </tr>
    </table>


    <table style="margin-top: 15px;">
        <thead>
            <tr>
                <th>NO. OF CASE</th>
                <th>ITEM DESCRIPTION</th>
                <th>REMARKS</th>
                <th>QTY / CASE</th>
                <th>TOTAL QTY</th>
                <th>PUNCH</th>
                <th>SKU</th>
                <th>PRICE / PIECE</th>
                <th>TOTAL AMOUNT</th>
                <th>TRANS#</th>
                <th>TERMINAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['no_of_case'] }}</td>
                    <td>{{ $row['item_description'] }}</td>
                    <td>{{ $row['remarks'] }}</td>
                    <td>{{ $row['qty_per_case'] }}</td>
                    <td>{{ $row['total_qty'] }}</td>
                    <td>{{ $row['punch'] }}</td>
                    <td>{{ $row['sku'] }}</td>
                    <td>{{ number_format($row['price_per_piece'], 2) }}</td>
                    <td>{{ number_format($row['total_amount'], 2) }}</td>
                    <td>{{ $row['trans_no'] }}</td>
                    <td>{{ $row['terminal'] }}</td>
                </tr>
            @endforeach

            {{-- Ensure at least 10 rows --}}
            @for ($i = count($rows); $i < 10; $i++)
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
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
        style="margin-top: 40px; width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 15%;"><strong>CHECKED BY:</strong></td>
            <td style="width: 35%;"><span class="underline">&nbsp;</span><br><small>PRINTED NAME & SIGNATURE</small></td>
            <td style="width: 15%;"><strong>RELEASED BY:</strong></td>
            <td style="width: 35%;"><span class="underline">&nbsp;</span><br><small>PRINTED NAME & SIGNATURE</small></td>
        </tr>
    </table>
</body>

</html>
