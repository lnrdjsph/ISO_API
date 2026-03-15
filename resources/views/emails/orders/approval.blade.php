<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Order Approval Request</title>
</head>

<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f8f9fa;">
    <table
        width="100%"
        cellpadding="0"
        cellspacing="0">
        <tr>
            <td align="center">
                <table
                    width="600"
                    cellpadding="0"
                    cellspacing="0"
                    style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1);">

                    <!-- Header -->
                    <tr>
                        <td style="background:#004362; padding:20px; color:#fff; font-size:20px; font-weight:bold; text-align:center;">
                            Request Approval
                        </td>
                    </tr>

                    <!-- Intro -->
                    <tr>
                        <td style="padding:20px; font-size:14px; color:#333;">
                            Asking for approval from
                            <strong>{{ $storeName }} - {{ $order->requested_by }}</strong>.
                        </td>
                    </tr>

                    <!-- Order Details -->
                    <tr>
                        <td style="padding:0 20px 20px;">
                            <table
                                width="100%"
                                cellpadding="8"
                                cellspacing="0"
                                style="border-collapse:collapse; font-size:14px; color:#333;">
                                <tr style="background:#f1f5f9;">
                                    <td><strong>SOF Order ID</strong></td>
                                    <td>{{ $order->sof_id }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Date & Time of Order</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($order->time_order)->format('F d, Y - h:i A') }}</td>
                                </tr>
                                <tr style="background:#f1f5f9;">
                                    <td><strong>Customer Name</strong></td>
                                    <td>{{ $order->customer_name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Mode of Payment</strong></td>
                                    <td>{{ $order->mode_payment }}</td>
                                </tr>
                                <tr style="background:#f1f5f9;">
                                    <td><strong>Mode of Dispatch</strong></td>
                                    <td>{{ $order->mode_dispatching }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Delivery Date</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($order->delivery_date)->timezone('Asia/Manila')->format('F d, Y') }}</td>
                                </tr>
                                <tr style="background:#f1f5f9;">
                                    <td><strong>Channel Order</strong></td>
                                    <td>{{ $order->channel_order }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Button -->
                    <tr>
                        <td
                            align="center"
                            style="padding:20px;">
                            <a
                                href="{{ route('orders.show', $order->id) }}"
                                style="background:#004362; color:#fff; text-decoration:none; padding:12px 24px; border-radius:6px; font-weight:bold; display:inline-block;">
                                View Order Details
                            </a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:15px; font-size:12px; color:#6b7280; text-align:center; background:#f8f9fa;">
                            © {{ date('Y') }} The Metro Stores. All rights reserved.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
