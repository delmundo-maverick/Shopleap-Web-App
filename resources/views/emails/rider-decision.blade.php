<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rider application</title>
</head>

<body
    style="margin:0; padding:0; background-color:#F5F7FA; font-family:'Instrument Sans', Helvetica, Arial, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background-color:#F5F7FA; padding:40px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:480px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 16px rgba(46,58,70,0.08);">
                    <tr>
                        <td align="center" style="background-color:#EAF2FB; padding:32px 24px;">
                            <span style="font-size:20px; font-weight:700; color:#1A5FB4;">Shopleap</span>
                            <div style="font-size:12px; color:#2E3A46; opacity:0.6; margin-top:2px;">Shop Smart. Shop
                                Simple.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px 28px 32px;">
                            <p style="margin:0 0 8px 0; font-size:16px; font-weight:700; color:#2E3A46;">
                                {{ $approved ? 'You’re approved, ' . $name . '!' : 'Hi ' . $name . ',' }}
                            </p>

                            @if ($approved)
                                <p
                                    style="margin:0 0 20px 0; font-size:14px; line-height:22px; color:#2E3A46; opacity:0.7;">
                                    <strong>{{ $hub }}</strong> approved your rider application. Open the
                                    Shopleap Rider app and sign in to start receiving pickups and deliveries.
                                </p>
                            @else
                                <p
                                    style="margin:0 0 16px 0; font-size:14px; line-height:22px; color:#2E3A46; opacity:0.7;">
                                    <strong>{{ $hub }}</strong> was not able to approve your rider application.
                                </p>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td
                                            style="background-color:#EAF2FB; border:1px solid rgba(26,95,180,0.15); border-radius:12px; padding:16px; font-size:14px; line-height:22px; color:#2E3A46;">
                                            <strong>Reason:</strong> {{ $reason }}
                                        </td>
                                    </tr>
                                </table>
                                <p
                                    style="margin:20px 0 0 0; font-size:14px; line-height:22px; color:#2E3A46; opacity:0.7;">
                                    You can fix the details and resubmit from the Shopleap Rider app, to the same
                                    sorting center or a different one.
                                </p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background-color:#F5F7FA; padding:20px 24px;">
                            <p style="margin:0; font-size:11px; color:#2E3A46; opacity:0.5;">© {{ date('Y') }}
                                Shopleap. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
