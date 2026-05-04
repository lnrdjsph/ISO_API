<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>

<body style="margin:0; padding:0; font-family:Arial, sans-serif; background-color:#f8f9fa;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table
                    width="600"
                    cellpadding="0"
                    cellspacing="0"
                    style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.1);">

                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding:24px 20px 0; background:#ffffff;">
                            <img
                                src="{{ $logoCid }}"
                                alt="Metro Stores"
                                width="180"
                                style="display:block; height:auto; max-width:180px;">
                        </td>
                    </tr>

                    <!-- Header -->
                    {{-- rounded to 8px --}}
                    <tr>
                        <td style="background:#004362; padding:16px 20px; color:#ffffff; font-size:20px; font-weight:bold; text-align:center;" border-radius="8px 8px 0 0;">
                            Reset Password
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:28px 28px 8px; font-size:14px; color:#333333; line-height:1.7;">
                            Hello <strong>{{ $userName }}</strong>,
                            <br><br>
                            You are receiving this email because we received a password reset
                            request for your account. Click the button below to proceed.
                        </td>
                    </tr>
                    <!-- Button -->
                    <tr>
                        <td align="center" style="padding:24px 20px;">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" bgcolor="#004362" style="border-radius:6px; padding:12px 32px;">
                                        <a href="{{ $resetUrl }}" style="color:#ffffff; font-family:Arial,sans-serif; font-size:14px; font-weight:bold; text-decoration:none;">Reset
                                            Password</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Expiry notice -->
                    <tr>
                        <td style="padding:0 28px 24px; font-size:13px; color:#555555; line-height:1.7;">
                            This link will expire in <strong>60 minutes</strong>.
                            <br><br>
                            If you did not request a password reset, no further action is required.
                        </td>
                    </tr>

                    <!-- Fallback URL -->
                    <tr>
                        <td style="padding:16px 28px 24px; border-top:1px solid #e5e7eb; font-size:12px; color:#6b7280; line-height:1.7;">
                            If the button above isn't working, copy and paste this link into your browser:
                            <br>
                            <a href="{{ $resetUrl }}" style="color:#004362; word-break:break-all;">{{ $resetUrl }}</a>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:16px 20px; font-size:12px; color:#6b7280; text-align:center; background:#f8f9fa;">
                            &copy; {{ date('Y') }} The Metro Stores. All rights reserved.
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
