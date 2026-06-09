@extends('layout.app')

@section('title', 'Application Settings')

@section('content')
<div class="container-fluid">

  {{-- Page Header --}}
  <div class="row">
    <div class="col-12">
      <div class="page-title-box">
        <div class="page-title-right">
          <ol class="breadcrumb m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Settings</li>
          </ol>
        </div>
        <h4 class="page-title"><i class="ti ti-settings me-2"></i>Application Settings</h4>
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <form id="settingsForm" method="POST" action="{{ route('settings.save') }}">
    @csrf

    <div class="row">

      {{-- Sidebar Tabs --}}
      <div class="col-xl-2 col-md-3">
        <div class="card">
          <div class="card-body p-2">
            <div class="nav flex-column nav-pills" id="settings-tab" role="tablist" aria-orientation="vertical">
              <button class="nav-link active text-start" id="general-tab" data-bs-toggle="pill" data-bs-target="#general" type="button">
                <i class="ti ti-world me-2"></i> General
              </button>
              <button class="nav-link text-start" id="paypal-tab" data-bs-toggle="pill" data-bs-target="#paypal" type="button">
                <i class="ti ti-brand-paypal me-2"></i> PayPal
              </button>
            </div>
          </div>
        </div>
      </div>

      {{-- Tab Content --}}
      <div class="col-xl-10 col-md-9">
        <div class="tab-content" id="settings-tabContent">

          {{-- ═══════════════════════ GENERAL ═══════════════════════ --}}
          <div class="tab-pane fade show active" id="general" role="tabpanel">
            <div class="card">
              <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-world me-2 text-muted"></i>General Settings</h5>
              </div>
              <div class="card-body">

                <h6 class="text-muted fw-semibold mb-3 text-uppercase small">Storefront Identity</h6>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Site Name <span class="text-danger">*</span></label>
                    <input type="text" name="site_name" class="form-control @error('site_name') is-invalid @enderror"
                      value="{{ old('site_name', $all['site_name'] ?? 'Sukaina Gems') }}" required>
                    @error('site_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Site Tagline</label>
                    <input type="text" name="site_tagline" class="form-control"
                      value="{{ old('site_tagline', $all['site_tagline'] ?? '') }}" placeholder="Specialists in…">
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control"
                      value="{{ old('contact_email', $all['contact_email'] ?? '') }}" placeholder="hello@sukainagems.com">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Contact Phone</label>
                    <input type="text" name="contact_phone" class="form-control"
                      value="{{ old('contact_phone', $all['contact_phone'] ?? '') }}" placeholder="+91 …">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">WhatsApp Number</label>
                    <input type="text" name="contact_whatsapp" class="form-control"
                      value="{{ old('contact_whatsapp', $all['contact_whatsapp'] ?? '') }}" placeholder="+91 …">
                  </div>
                </div>

                <hr class="my-4">
                <h6 class="text-muted fw-semibold mb-3 text-uppercase small">Currency</h6>
                <div class="row align-items-end">
                  <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Symbol <span class="text-danger">*</span></label>
                    <input type="text" name="currency_symbol" id="currencySymbol"
                      class="form-control @error('currency_symbol') is-invalid @enderror"
                      value="{{ old('currency_symbol', $all['currency_symbol'] ?? '₹') }}" maxlength="10" required>
                    <div class="form-text">Displayed on storefront (₹, $, €, £…)</div>
                    @error('currency_symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">ISO Code <span class="text-danger">*</span></label>
                    <input type="text" name="currency_code" id="currencyCode"
                      class="form-control @error('currency_code') is-invalid @enderror text-uppercase"
                      value="{{ old('currency_code', $all['currency_code'] ?? 'USD') }}"
                      maxlength="3" minlength="3" required oninput="this.value=this.value.toUpperCase()">
                    <div class="form-text">3-letter code sent to PayPal (USD, EUR, INR…)</div>
                    @error('currency_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  </div>
                  <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Symbol Position</label>
                    <select name="currency_position" id="currencyPosition" class="form-select">
                      <option value="before" {{ (old('currency_position', $all['currency_position'] ?? 'before')) === 'before' ? 'selected' : '' }}>Before amount — ₹ 1,250</option>
                      <option value="after"  {{ (old('currency_position', $all['currency_position'] ?? 'before')) === 'after'  ? 'selected' : '' }}>After amount — 1,250 USD</option>
                    </select>
                  </div>
                  <div class="col-md-3 mb-3">
                    <label class="form-label fw-semibold">Live Preview</label>
                    <div class="form-control bg-light text-dark fw-semibold" id="currencyPreview">₹ 1,250</div>
                  </div>
                </div>

                <hr class="my-4">
                <h6 class="text-muted fw-semibold mb-3 text-uppercase small">Storefront Features</h6>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <div class="card border">
                      <div class="card-body d-flex align-items-center justify-content-between py-3">
                        <div>
                          <div class="fw-semibold"><i class="ti ti-shopping-cart me-2 text-muted"></i>Shopping Cart</div>
                          <small class="text-muted">Allow visitors to add gems to a cart before checkout.</small>
                        </div>
                        <div class="form-check form-switch form-switch-lg mb-0">
                          <input class="form-check-input" type="checkbox" name="cart_enabled" value="1"
                            {{ (old('cart_enabled', $all['cart_enabled'] ?? '1')) === '1' ? 'checked' : '' }}>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <div class="card border">
                      <div class="card-body d-flex align-items-center justify-content-between py-3">
                        <div>
                          <div class="fw-semibold"><i class="ti ti-credit-card me-2 text-muted"></i>Online Checkout</div>
                          <small class="text-muted">Show the checkout & PayPal payment page to visitors.</small>
                        </div>
                        <div class="form-check form-switch form-switch-lg mb-0">
                          <input class="form-check-input" type="checkbox" name="checkout_enabled" value="1"
                            {{ (old('checkout_enabled', $all['checkout_enabled'] ?? '1')) === '1' ? 'checked' : '' }}>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>

          {{-- ═══════════════════════ PAYPAL ════════════════════════ --}}
          <div class="tab-pane fade" id="paypal" role="tabpanel">
            <div class="card">
              <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-brand-paypal me-2 text-muted"></i>PayPal Payment Gateway</h5>
              </div>
              <div class="card-body">

                <div class="alert alert-info border-0 d-flex gap-2 align-items-start">
                  <i class="ti ti-info-circle fs-xl mt-1 flex-shrink-0"></i>
                  <div>
                    <strong>Setup:</strong> Log in to your
                    <a href="https://developer.paypal.com/dashboard/" target="_blank" class="alert-link">PayPal Developer Dashboard</a>,
                    create a REST App, then copy the Client ID and Secret into the fields below.
                    Make sure the <strong>currency code</strong> (General tab) matches the currency enabled in your PayPal account.
                  </div>
                </div>

                <div class="mb-4">
                  <div class="card border">
                    <div class="card-body d-flex align-items-center justify-content-between py-3">
                      <div>
                        <div class="fw-semibold"><i class="ti ti-brand-paypal me-2 text-muted"></i>Enable PayPal Checkout</div>
                        <small class="text-muted">Show the PayPal payment button on the storefront checkout page.</small>
                      </div>
                      <div class="form-check form-switch form-switch-lg mb-0">
                        <input class="form-check-input" type="checkbox" name="paypal_enabled" value="1"
                          {{ (old('paypal_enabled', $all['paypal_enabled'] ?? '0')) === '1' ? 'checked' : '' }}>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Mode</label>
                    <select name="paypal_mode" class="form-select">
                      <option value="sandbox" {{ (old('paypal_mode', $all['paypal_mode'] ?? 'sandbox')) === 'sandbox' ? 'selected' : '' }}>🧪 Sandbox (Testing)</option>
                      <option value="live"    {{ (old('paypal_mode', $all['paypal_mode'] ?? 'sandbox')) === 'live'    ? 'selected' : '' }}>🚀 Live (Production)</option>
                    </select>
                    <div class="form-text text-warning"><i class="ti ti-alert-triangle me-1"></i>Always verify in Sandbox before going Live.</div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Client ID</label>
                    <input type="text" name="paypal_client_id" class="form-control font-monospace"
                      value="{{ old('paypal_client_id', $all['paypal_client_id'] ?? '') }}"
                      placeholder="AaBb…" autocomplete="off">
                    <div class="form-text">Public key — loaded by the PayPal JS SDK in the browser.</div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold">Client Secret</label>
                    <div class="input-group">
                      <input type="password" name="paypal_secret" id="paypalSecretField" class="form-control font-monospace"
                        value="{{ old('paypal_secret', $all['paypal_secret'] ?? '') }}"
                        placeholder="EeFf…" autocomplete="new-password">
                      <button type="button" class="btn btn-outline-secondary" id="toggleSecret">
                        <i class="ti ti-eye" id="toggleSecretIcon"></i>
                      </button>
                    </div>
                    <div class="form-text text-danger"><i class="ti ti-lock me-1"></i>Server-side only — never exposed to browsers.</div>
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold">Webhook ID <span class="text-muted fw-normal">(optional)</span></label>
                  <input type="text" name="paypal_webhook_id" class="form-control font-monospace"
                    value="{{ old('paypal_webhook_id', $all['paypal_webhook_id'] ?? '') }}" placeholder="5ML2…">
                  <div class="form-text">From Developer Dashboard → Webhooks. Used to verify event authenticity.</div>
                </div>

                <hr class="my-4">
                <div class="d-flex align-items-center gap-3">
                  <button type="button" id="testPaypalBtn" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-plug me-1"></i> Test Connection
                  </button>
                  <span id="testPaypalResult" class="small"></span>
                </div>

              </div>
            </div>
          </div>

        </div>{{-- /tab-content --}}

        {{-- Save Row --}}
        <div class="d-flex justify-content-end gap-2 mb-4">
          <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary" id="saveBtn">
            <i class="ti ti-device-floppy me-1"></i> Save Settings
          </button>
        </div>

      </div>
    </div>
  </form>

</div>
@endsection

@push('scripts')
<script>
$(function () {

  // ── Currency preview ──────────────────────────────────────────
  function updatePreview() {
    var sym = $('#currencySymbol').val() || '₹';
    var code = $('#currencyCode').val() || 'USD';
    var pos  = $('#currencyPosition').val();
    $('#currencyPreview').text(pos === 'before' ? sym + ' 1,250' : '1,250 ' + code);
  }
  $('#currencySymbol, #currencyCode, #currencyPosition').on('input change', updatePreview);
  updatePreview();

  // ── Secret visibility toggle ──────────────────────────────────
  $('#toggleSecret').on('click', function () {
    var f   = $('#paypalSecretField');
    var ico = $('#toggleSecretIcon');
    if (f.attr('type') === 'password') {
      f.attr('type', 'text');
      ico.removeClass('ti-eye').addClass('ti-eye-off');
    } else {
      f.attr('type', 'password');
      ico.removeClass('ti-eye-off').addClass('ti-eye');
    }
  });

  // ── AJAX form save ────────────────────────────────────────────
  $('#settingsForm').on('submit', function (e) {
    e.preventDefault();
    var $btn = $('#saveBtn').prop('disabled', true).html('<i class="ti ti-loader me-1"></i> Saving…');

    fetch(this.action, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') },
      body: new FormData(this),
    })
    .then(r => r.json())
    .then(function (d) {
      if (d.success) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'success', title: 'Saved!', text: d.message, timer: 1800, showConfirmButton: false });
        } else {
          alert(d.message);
        }
      } else {
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: 'Error', text: d.message || 'Could not save.' });
        } else {
          alert(d.message || 'Could not save.');
        }
      }
    })
    .catch(function () {
      alert('An unexpected error occurred.');
    })
    .finally(function () {
      $btn.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Settings');
    });
  });

  // ── PayPal connection test ─────────────────────────────────────
  $('#testPaypalBtn').on('click', function () {
    var $btn = $(this).prop('disabled', true).html('<i class="ti ti-loader me-1"></i> Testing…');
    var $res = $('#testPaypalResult').text('').removeClass('text-success text-danger');

    fetch('{{ route("settings.paypal-test") }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'),
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        paypal_client_id: $('input[name=paypal_client_id]').val(),
        paypal_secret:    $('#paypalSecretField').val(),
        paypal_mode:      $('select[name=paypal_mode]').val(),
      }),
    })
    .then(r => r.json())
    .then(function (d) {
      if (d.success) {
        $res.addClass('text-success').html('<i class="ti ti-check me-1"></i>' + d.message);
      } else {
        $res.addClass('text-danger').html('<i class="ti ti-x me-1"></i>' + (d.error || 'Connection failed.'));
      }
    })
    .catch(function () { $res.addClass('text-danger').text('Request failed.'); })
    .finally(function () { $btn.prop('disabled', false).html('<i class="ti ti-plug me-1"></i> Test Connection'); });
  });

});
</script>
@endpush
