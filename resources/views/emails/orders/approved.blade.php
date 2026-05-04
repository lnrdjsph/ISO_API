<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Order Approved</title>
</head>

<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f8f9fa;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table
                    width="600"
                    cellpadding="0"
                    cellspacing="0"
                    style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1);">

                    <!-- Logo (white bg, above header) -->
                    <tr>
                        <td align="center" style="padding:24px 20px 16px; background:#ffffff;">
                            <img
                                src="{{ $logoCid }}"
                                alt="Metro Stores"
                                width="180"
                                style="display:block; height:auto; max-width:180px;">
                        </td>
                    </tr>

                    <!-- Header (green, no logo inside) -->
                    <tr>
                        <td style="background:#296D38; padding:16px 20px; color:#ffffff; font-size:20px; font-weight:bold; text-align:center; border-radius:8px 8px 0 0;">
                            Order Approved
                        </td>
                    </tr>

                    <!-- Intro -->
                    <tr>
                        <td style="padding:20px; font-size:14px; color:#333;">
                            Hello <strong>{{ $requesterName }}</strong>,
                            <br><br>
                            Your order request has been reviewed and <strong>approved</strong>.
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
                                    <td style="padding:8px;"><strong>SOF Order ID</strong></td>
                                    <td style="padding:8px;">{{ $order->sof_id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px;"><strong>Date &amp; Time of Order</strong></td>
                                    <td style="padding:8px;">{{ \Carbon\Carbon::parse($order->time_order)->format('F d, Y - h:i A') }}</td>
                                </tr>
                                <tr style="background:#f1f5f9;">
                                    <td style="padding:8px;"><strong>Customer Name</strong></td>
                                    <td style="padding:8px;">{{ $order->customer_name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px;"><strong>Mode of Payment</strong></td>
                                    <td style="padding:8px;">{{ $order->mode_payment }}</td>
                                </tr>
                                <tr style="background:#f1f5f9;">
                                    <td style="padding:8px;"><strong>Mode of Dispatch</strong></td>
                                    <td style="padding:8px;">{{ $order->mode_dispatching }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:8px;"><strong>Delivery Date</strong></td>
                                    <td style="padding:8px;">{{ \Carbon\Carbon::parse($order->delivery_date)->timezone('Asia/Manila')->format('F d, Y') }}</td>
                                </tr>
                                <tr style="background:#f1f5f9;">
                                    <td style="padding:8px;"><strong>Channel Order</strong></td>
                                    <td style="padding:8px;">{{ $order->channel_order }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Button -->
                    <tr>
                        <td align="center" style="padding:20px;">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" bgcolor="#296D38" style="border-radius:6px; padding:12px 32px;">
                                        <a href="{{ route('orders.show', $order->id) }}"
                                            style="color:#ffffff; font-family:Arial,sans-serif; font-size:14px; font-weight:bold; text-decoration:none;">View Approved Order</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:15px; font-size:12px; color:#6b7280; text-align:center; background:#f8f9fa;">
                            &copy; {{ date('Y') }} The Metro Stores. All rights reserved.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
