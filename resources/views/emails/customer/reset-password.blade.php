@extends('emails.layout')

@section('title', 'Reset your password')

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:24px;font-weight:600;color:#ffffff;">
        Reset your password
    </h1>

    <p style="margin:0 0 24px;">
        We received a request to reset the password for your {{ $settings->get('site_name', 'Sukaina Gems') }}
        account ({{ $customer->email }}). Click the button below to choose a new password.
        This link will expire in {{ $expireMinutes }} minutes.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:4px;background:linear-gradient(135deg,#2dd4bf,#0f766e);">
                <a href="{{ $url }}"
                   style="display:inline-block;padding:12px 28px;font-size:13px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:#04211d;text-decoration:none;">
                    Reset Password &rarr;
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:28px 0 0;font-size:12px;color:rgba(230,245,242,.5);">
        If you didn't request a password reset, no action is needed — your password will remain unchanged.
    </p>

    <p style="margin:16px 0 0;font-size:12px;color:rgba(230,245,242,.4);word-break:break-all;">
        Trouble with the button? Copy and paste this link into your browser:<br>
        <a href="{{ $url }}" style="color:#2dd4bf;">{{ $url }}</a>
    </p>
@endsection
