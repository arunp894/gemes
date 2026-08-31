@extends('emails.layout')

@section('title', 'New Contact Form Submission')

@section('content')
    <h1 style="margin:0 0 16px;font-family:Georgia,serif;font-size:22px;font-weight:600;color:#ffffff;">
        New Contact Form Submission
    </h1>

    <p style="margin:0 0 20px;">
        Someone submitted the Contact Us form on {{ $settings->get('site_name', 'Sukaina Gems') }}.
        Reply directly to this email to respond to them.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#0a1614;border:1px solid rgba(0,191,176,.15);border-radius:6px;margin:0 0 20px;">
        <tr>
            <td style="padding:18px 20px;font-size:13px;color:rgba(230,245,242,.85);">
                <strong style="color:#ffffff;">Name:</strong> {{ $contactMessage->name }}<br>
                <strong style="color:#ffffff;">Email:</strong> {{ $contactMessage->email }}<br>
                @if ($contactMessage->phone)
                    <strong style="color:#ffffff;">Phone:</strong> {{ $contactMessage->phone }}<br>
                @endif
                <strong style="color:#ffffff;">Submitted:</strong> {{ $contactMessage->created_at->format('d M Y, h:i A') }}
            </td>
        </tr>
    </table>

    <p style="margin:0 0 8px;font-size:11px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:#2dd4bf;">Message</p>
    <p style="margin:0;white-space:pre-line;">{{ $contactMessage->message }}</p>
@endsection
