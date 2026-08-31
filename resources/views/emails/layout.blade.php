<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', $settings->get('site_name', 'Sukaina Gems'))</title>
</head>
<body style="margin:0;padding:0;background:#0a1614;font-family:Helvetica,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0a1614;padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#0f201d;border:1px solid rgba(0,191,176,.15);border-radius:8px;overflow:hidden;">

    {{-- Header --}}
    <tr>
        <td style="padding:28px 32px;border-bottom:1px solid rgba(0,191,176,.12);" align="center">
            @if ($settings->logoUrl())
                <img src="{{ $settings->logoUrl() }}" alt="{{ $settings->get('site_name', 'Sukaina Gems') }}" height="36" style="height:36px;width:auto;display:inline-block;">
            @else
                <div style="font-family:Georgia,serif;font-size:22px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#ffffff;">
                    {{ $settings->get('site_name', 'Sukaina Gems') }}
                </div>
            @endif
        </td>
    </tr>

    {{-- Body --}}
    <tr>
        <td style="padding:36px 32px;color:#e6f5f2;font-size:14px;line-height:1.7;">
            @yield('content')
        </td>
    </tr>

    {{-- Footer --}}
    <tr>
        <td style="padding:20px 32px;border-top:1px solid rgba(0,191,176,.12);color:rgba(230,245,242,.45);font-size:12px;" align="center">
            &copy; {{ date('Y') }} {{ $settings->get('site_name', 'Sukaina Gems') }}. All rights reserved.
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
