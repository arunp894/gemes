@extends('website.layout')

@section('title', 'Contact Us')
@section('meta_desc', "Get in touch with {$settings->get('site_name', 'Sukaina Gems')} — questions about a specific gemstone, custom layouts, or wholesale supply.")

@push('head_styles')
<style>
.sg-contact-hero{padding:150px 0 40px}
.sg-contact-hero .sg-container{max-width:1000px}
.sg-contact-eyebrow{font-size:12px;letter-spacing:2px;text-transform:uppercase;color:var(--teal-400);margin-bottom:14px}
.sg-contact-title{font-family:'Cormorant Garamond',serif;font-size:42px;font-weight:600;line-height:1.2;color:var(--white);margin-bottom:18px}
.sg-contact-lead{font-size:16px;line-height:1.8;color:var(--white-dim);max-width:620px}
.sg-contact-wrap{max-width:1000px;margin:0 auto 100px}
.sg-contact-grid{display:grid;grid-template-columns:0.85fr 1.15fr;gap:28px;align-items:start}
.sg-contact-card{background:var(--dark-800);border:1px solid rgba(0,191,176,.12);border-radius:4px;padding:8px}
.sg-contact-row{display:flex;align-items:flex-start;gap:16px;padding:20px 24px;border-bottom:1px solid rgba(0,191,176,.08)}
.sg-contact-row:last-child{border-bottom:none}
.sg-contact-icon{font-size:22px;flex-shrink:0;width:32px;text-align:center;margin-top:2px}
.sg-contact-label{font-size:11px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:var(--teal-400);margin-bottom:4px}
.sg-contact-value{font-size:16px;color:var(--white);text-decoration:none}
.sg-contact-value:hover{color:var(--teal-300)}
.sg-contact-empty{padding:32px 24px;text-align:center;color:var(--white-faint);font-size:14px}
.sg-contact-form-card{background:var(--dark-800);border:1px solid rgba(0,191,176,.12);border-radius:4px;padding:32px}
.sg-contact-form-title{font-family:'Cormorant Garamond',serif;font-size:24px;font-weight:600;color:var(--white);margin:0 0 20px}
.sg-contact-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
.sg-contact-alert{padding:14px 18px;border-radius:4px;font-size:13px;margin-bottom:24px}
.sg-contact-alert-success{background:rgba(80,200,130,.1);border:1px solid rgba(80,200,130,.25);color:#7ec87e}
.sg-contact-alert-error{background:rgba(220,80,80,.1);border:1px solid rgba(220,80,80,.25);color:#e07070}
@media(max-width:768px){.sg-contact-title{font-size:30px}.sg-contact-hero{padding:120px 0 24px}.sg-contact-grid{grid-template-columns:1fr}}
@media(max-width:480px){.sg-contact-hero{padding:104px 0 20px}.sg-contact-title{font-size:26px}.sg-contact-form-card{padding:22px}.sg-contact-form-grid{grid-template-columns:1fr;margin-bottom:0}}
.sg-label{display:block;font-size:11px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-dim);margin-bottom:8px}
.sg-input{width:100%;background:var(--dark-750);border:1px solid rgba(0,191,176,.2);border-radius:3px;color:var(--white);font-family:'Jost',sans-serif;font-size:14px;padding:12px 16px;outline:none;transition:border .3s;display:block;min-height:44px}
.sg-input:focus{border-color:var(--teal-400)}
textarea.sg-input{min-height:auto;resize:vertical}
</style>
@endpush

@section('content')

@php
    $contactEmail    = $settings->get('contact_email');
    $contactPhone    = $settings->get('contact_phone');
    $contactWhatsapp = $settings->get('contact_whatsapp');
    $contactAddress  = $settings->get('contact_address');
    $hasAnyContact   = $contactEmail || $contactPhone || $contactWhatsapp || $contactAddress;
@endphp

<section class="sg-contact-hero">
    <div class="sg-container">
        <div class="sg-contact-eyebrow sg-reveal">Get In Touch</div>
        <h1 class="sg-contact-title sg-reveal">Let's Find Your Perfect Gemstone</h1>
        <p class="sg-contact-lead sg-reveal">
            Whether you are looking for a specific single gemstone, custom layouts, or wholesale supply,
            our team is here to assist you.
        </p>
    </div>
</section>

<div class="sg-container">
    <div class="sg-contact-wrap">
        <div class="sg-contact-grid">

            <div class="sg-contact-card sg-reveal">
                @if ($hasAnyContact)
                    @if ($contactAddress)
                        <div class="sg-contact-row">
                            <span class="sg-contact-icon">📍</span>
                            <div>
                                <div class="sg-contact-label">Location</div>
                                <span class="sg-contact-value">{{ $contactAddress }}</span>
                            </div>
                        </div>
                    @endif
                    @if ($contactEmail)
                        <div class="sg-contact-row">
                            <span class="sg-contact-icon">📧</span>
                            <div>
                                <div class="sg-contact-label">Email</div>
                                <a href="mailto:{{ $contactEmail }}" class="sg-contact-value">{{ $contactEmail }}</a>
                            </div>
                        </div>
                    @endif
                    @if ($contactPhone)
                        <div class="sg-contact-row">
                            <span class="sg-contact-icon">📞</span>
                            <div>
                                <div class="sg-contact-label">Phone</div>
                                <a href="tel:{{ $contactPhone }}" class="sg-contact-value">{{ $contactPhone }}</a>
                            </div>
                        </div>
                    @endif
                    @if ($contactWhatsapp)
                        <div class="sg-contact-row">
                            <span class="sg-contact-icon">💬</span>
                            <div>
                                <div class="sg-contact-label">WhatsApp</div>
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contactWhatsapp) }}" class="sg-contact-value" target="_blank" rel="noopener">{{ $contactWhatsapp }}</a>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="sg-contact-empty">Contact details coming soon.</div>
                @endif
            </div>

            <div class="sg-contact-form-card sg-reveal">
                <h2 class="sg-contact-form-title">Send Us a Message</h2>

                @if (session('success'))
                    <div class="sg-contact-alert sg-contact-alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="sg-contact-alert sg-contact-alert-error">
                        @foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('website.contact.submit') }}">
                    @csrf

                    <div class="sg-contact-form-grid">
                        <div>
                            <label class="sg-label">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="sg-input" placeholder="Your name">
                        </div>
                        <div>
                            <label class="sg-label">Phone (optional)</label>
                            <input type="tel" name="phone" value="{{ old('phone') }}" class="sg-input" placeholder="+66 …">
                        </div>
                    </div>

                    <div style="margin-bottom:18px">
                        <label class="sg-label">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="sg-input" placeholder="you@example.com">
                    </div>

                    <div style="margin-bottom:22px">
                        <label class="sg-label">Message</label>
                        <textarea name="message" rows="5" required class="sg-input" placeholder="Tell us what you're looking for…">{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="sg-btn-primary" style="width:100%;justify-content:center">Send Message &rarr;</button>
                </form>
            </div>

        </div>
    </div>
</div>

@endsection
