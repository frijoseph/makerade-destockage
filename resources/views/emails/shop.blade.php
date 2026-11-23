<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mailSubject }}</title>
</head>
<body style="margin:0;padding:0;background:#f2f5f3;font-family:Arial,Helvetica,sans-serif;color:#1c2620;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f5f3;padding:24px 12px;">
    <tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;">
            <tr><td style="background:{{ $color }};padding:20px 24px;color:#ffffff;font-size:20px;font-weight:bold;">{{ $shopName }}</td></tr>
            <tr><td style="padding:24px;font-size:15px;line-height:1.55;">
                @if ($greeting)<p style="margin:0 0 14px;font-weight:bold;">{{ $greeting }}</p>@endif
                @foreach ($paragraphs as $paragraph)
                    <p style="margin:0 0 14px;">{!! nl2br(e($paragraph)) !!}</p>
                @endforeach

                @if ($details)
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 14px;border:1px solid #e3e9e5;border-radius:10px;">
                        @foreach ($details as $row)
                            <tr>
                                <td style="padding:9px 12px;color:#5f6a64;font-size:13px;width:36%;vertical-align:top;border-bottom:1px solid #eef2ef;">{{ $row['label'] }}</td>
                                <td style="padding:9px 12px;font-size:14px;border-bottom:1px solid #eef2ef;">{!! nl2br(e($row['value'])) !!}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                @if ($order)
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:6px 0 14px;font-size:14px;">
                        @foreach ($order['lines'] as $line)
                            <tr>
                                <td style="padding:8px 0;border-bottom:1px solid #eef2ef;">{{ $line['name'] }} <span style="color:#5f6a64;">× {{ $line['quantity'] }}</span></td>
                                <td align="right" style="padding:8px 0;border-bottom:1px solid #eef2ef;white-space:nowrap;">{{ $line['total'] }}</td>
                            </tr>
                        @endforeach
                        @foreach ($order['totals'] as $total)
                            <tr>
                                <td style="padding:6px 0;{{ ! empty($total['strong']) ? 'font-weight:bold;font-size:16px;' : 'color:#5f6a64;' }}">{{ $total['label'] }}</td>
                                <td align="right" style="padding:6px 0;white-space:nowrap;{{ ! empty($total['strong']) ? 'font-weight:bold;font-size:16px;' : '' }}">{{ $total['value'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif

                @if ($actionLabel && $actionUrl)
                    <p style="margin:18px 0;"><a href="{{ $actionUrl }}" style="display:inline-block;background:{{ $color }};color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:10px;">{{ $actionLabel }}</a></p>
                @endif

                @if ($signature)<p style="margin:18px 0 0;color:#5f6a64;">{{ $signature }}</p>@endif
            </td></tr>
            @if ($contact)<tr><td style="padding:14px 24px;background:#f7faf8;color:#7a857f;font-size:12px;text-align:center;">{{ $contact }}</td></tr>@endif
        </table>
    </td></tr>
</table>
</body>
</html>
