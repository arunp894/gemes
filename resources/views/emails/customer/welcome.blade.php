@extends('emails.layout')

@section('title', 'Welcome to ' . $settings->get('site_name', 'Sukaina Gems'))

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:24px;font-weight:600;color:#ffffff;">
        Welcome, {{ $customer->name }}!
    </h1>

    <p style="margin:0 0 16px;">
        Thank you for creating an account with {{ $settings->get('site_name', 'Sukaina Gems') }}.
        Your account is ready — you can now track orders, save your details for faster checkout,
        and browse our collection of rare and precious stones.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#0a1614;border:1px solid rgba(0,191,176,.15);border-radius:6px;margin:0 0 24px;">
        <tr>
            <td style="padding:16px 20px;font-size:13px;color:rgba(230,245,242,.7);">
                <strong style="color:#ffffff;">Account email:</strong> {{ $customer->email }}<br>
                @if ($customer->phone)
                    <strong style="color:#ffffff;">Phone:</strong> {{ $customer->phone }}
                @endif
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:4px;background:linear-gradient(135deg,#2dd4bf,#0f766e);">
                <a href="{{ route('website.collections') }}"
                   style="display:inline-block;padding:12px 28px;font-size:13px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:#04211d;text-decoration:none;">
                    Start Browsing &rarr;
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:28px 0 0;font-size:12px;color:rgba(230,245,242,.5);">
        If you didn't create this account, please contact us and we'll help sort it out.
    </p>
@endsection
