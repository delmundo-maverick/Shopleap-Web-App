<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
</head>

<body
    style="margin:0; padding:0; background-color:#F5F7FA; font-family: 'Instrument Sans', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background-color:#F5F7FA; padding:40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:480px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 16px rgba(46,58,70,0.08);">

                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color:#EAF2FB; padding:32px 24px;">
                            <span style="font-size:20px; font-weight:700; color:#1A5FB4;">Shopleap</span>
                            <div style="font-size:12px; color:#2E3A46; opacity:0.6; margin-top:2px;">Shop Smart. Shop
                                Simple.</div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:36px 32px 24px 32px;">
                            <p style="margin:0 0 8px 0; font-size:16px; font-weight:700; color:#2E3A46;">
                                Verify your e-mail
                            </p>
                            <p style="margin:0 0 24px 0; font-size:14px; line-height:22px; color:#2E3A46; opacity:0.7;">
                                Enter this code to finish your registration on Shopleap. It expires in
                                <strong>10 minutes</strong>.
                            </p>

                            <!-- OTP box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center"
                                        style="background-color:#EAF2FB; border:1px solid rgba(26,95,180,0.15); border-radius:12px; padding:20px;">
                                        <span
                                            style="font-size:32px; font-weight:700; letter-spacing:10px; color:#1A5FB4;">
                                            {{ $otp }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <p
                                style="margin:24px 0 0 0; font-size:13px; line-height:20px; color:#2E3A46; opacity:0.55;">
                                If you didn't request this code, you can safely ignore this email — no account will be
                                created without it.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:0 32px;">
                            <div style="border-top:1px solid #F5F7FA;"></div>
                        </td>
                    </tr>

                    <!-- Login CTA -->
                    <tr>
                        <td align="center" style="padding:24px 32px 32px 32px;">
                            <p style="margin:0 0 16px 0; font-size:13px; color:#2E3A46; opacity:0.6;">
                                Already verified, or coming back later?
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="border-radius:10px; background-color:#1A5FB4;">
                                        <a href="{{ route('login') }}"
                                            style="display:inline-block; padding:12px 28px; font-size:14px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:10px;">
                                            Go to Login
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background-color:#F5F7FA; padding:20px 24px;">
                            <p style="margin:0; font-size:11px; color:#2E3A46; opacity:0.5;">
                                © {{ date('Y') }} Shopleap. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
