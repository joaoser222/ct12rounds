<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject ?? config('app.name') }}</title>
</head>

<body
    style="margin:0;padding:0;background-color:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#1f2933;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background-color:#f4f5f7;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                    style="max-width:600px;width:100%;background-color:#ffffff;border-radius:8px;padding:24px;">
                    <tr>
                        <td style="font-size:18px;font-weight:700;padding-bottom:16px;">
                            {{ config('app.name') }}
                        </td>
                    </tr>
                    @isset($title)
                    <tr>
                        <td style="font-size:20px;font-weight:700;padding-bottom:8px;">
                            {{ $title }}
                        </td>
                    </tr>
                    @endisset
                    <tr>
                        <td style="font-size:14px;line-height:22px;">
                            {{ $body ?? '' }}
                        </td>
                    </tr>
                    @isset($actionUrl)
                    <tr>
                        <td style="padding-top:20px;">
                            <a href="{{ $actionUrl }}"
                                style="background-color:#0057ff;color:#ffffff;padding:12px 20px;border-radius:4px;text-decoration:none;display:inline-block;">
                                {{ $actionText ?? 'Acessar' }}
                            </a>
                        </td>
                    </tr>
                    @endisset
                    <tr>
                        <td style="padding-top:24px;font-size:12px;color:#6b7280;">
                            {{ config('app.name') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
