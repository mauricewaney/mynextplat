<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm your notification</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f3f4f6; padding:40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                    <tr>
                        <td style="padding:32px 32px 20px;">
                            <p style="margin:0 0 8px; font-size:13px; color:#6b7280; letter-spacing:0.5px; text-transform:uppercase;">MyNextPlat</p>
                            <h1 style="margin:0; font-size:22px; font-weight:600; color:#111827;">
                                @if ($gameTitle)
                                    Confirm notifications for {{ $gameTitle }}
                                @else
                                    Confirm your notifications
                                @endif
                            </h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 8px; font-size:15px; line-height:1.6; color:#374151;">
                            <p style="margin:0 0 16px;">
                                @if ($gameTitle)
                                    Click the button below to confirm. We'll email you once a trophy guide is added for <strong>{{ $gameTitle }}</strong> — and any other games you opt in for.
                                @else
                                    Click the button below to confirm your subscription.
                                @endif
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 32px 24px;" align="left">
                            <a href="{{ $confirmUrl }}"
                               style="display:inline-block; background-color:#2563eb; color:#ffffff; text-decoration:none; padding:12px 24px; border-radius:8px; font-weight:600; font-size:15px;">
                                Confirm notification
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 24px; font-size:13px; line-height:1.5; color:#6b7280;">
                            <p style="margin:0 0 8px;">If the button doesn't work, paste this URL into your browser:</p>
                            <p style="margin:0; word-break:break-all;"><a href="{{ $confirmUrl }}" style="color:#2563eb; text-decoration:none;">{{ $confirmUrl }}</a></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 32px 28px; border-top:1px solid #e5e7eb; font-size:12px; line-height:1.5; color:#9ca3af;">
                            This link expires in 7 days. Didn't ask for this? You can safely ignore this email.
                        </td>
                    </tr>
                </table>
                <p style="margin:16px 0 0; font-size:11px; color:#9ca3af;">
                    MyNextPlat · Trophy guide notifications
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
