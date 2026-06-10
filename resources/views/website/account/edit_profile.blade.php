@extends('website.layout')

@section('title', 'Edit Profile — Sukaina Gems')

@section('content')
<div style="min-height:calc(100vh - 64px);padding:60px;background:var(--dark-900)">
  <div style="max-width:800px;margin:0 auto">

    <div style="margin-bottom:32px">
      <a href="{{ route('website.account.profile') }}" style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:var(--teal-300);text-decoration:none">← Back to Profile</a>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:600;color:var(--white);margin-top:10px">Edit Profile</h1>
    </div>

    @if(session('success'))
      <div style="padding:14px 18px;background:rgba(80,200,130,.1);border:1px solid rgba(80,200,130,.25);border-radius:4px;font-size:13px;color:#7ec87e;margin-bottom:24px">{{ session('success') }}</div>
    @endif

    @if($errors->any())
      <div style="padding:14px 18px;background:rgba(220,80,80,.1);border:1px solid rgba(220,80,80,.25);border-radius:4px;font-size:13px;color:#e07070;margin-bottom:24px">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
      </div>
    @endif

    {{-- Profile form --}}
    <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;padding:32px;margin-bottom:24px">
      <div style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid rgba(0,191,176,.08)">Personal Information</div>

      <form method="POST" action="{{ route('website.account.update') }}">
        @csrf
        @method('PATCH')

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
          <div>
            <label class="sg-label">Full Name *</label>
            <input type="text" name="name" value="{{ old('name', $customer->name) }}" required class="sg-input">
          </div>
          <div>
            <label class="sg-label">Phone</label>
            <input type="tel" name="phone" value="{{ old('phone', $customer->phone) }}" class="sg-input">
          </div>
          <div>
            <label class="sg-label">Alternate Phone</label>
            <input type="tel" name="alternate_phone" value="{{ old('alternate_phone', $customer->alternate_phone) }}" class="sg-input">
          </div>
        </div>

        <div style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;margin:28px 0 20px;padding-top:20px;border-top:1px solid rgba(0,191,176,.08)">Shipping Address</div>

        <div style="margin-bottom:16px">
          <label class="sg-label">Address Line 1</label>
          <input type="text" name="address_line1" value="{{ old('address_line1', $customer->address_line1) }}" class="sg-input" placeholder="Street address">
        </div>
        <div style="margin-bottom:16px">
          <label class="sg-label">Address Line 2</label>
          <input type="text" name="address_line2" value="{{ old('address_line2', $customer->address_line2) }}" class="sg-input" placeholder="Apt, Suite, etc.">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
          <div>
            <label class="sg-label">City</label>
            <input type="text" name="city" value="{{ old('city', $customer->city) }}" class="sg-input">
          </div>
          <div>
            <label class="sg-label">State</label>
            <input type="text" name="state" value="{{ old('state', $customer->state) }}" class="sg-input">
          </div>
          <div>
            <label class="sg-label">Zip Code</label>
            <input type="text" name="zip_code" value="{{ old('zip_code', $customer->zip_code) }}" class="sg-input">
          </div>
        </div>
        <div style="margin-bottom:28px">
          <label class="sg-label">Country</label>
          <input type="text" name="country" value="{{ old('country', $customer->country) }}" class="sg-input">
        </div>

        <button type="submit" class="sg-btn-primary">Save Changes →</button>
      </form>
    </div>

    {{-- Change password --}}
    <div style="background:var(--dark-800);border:1px solid rgba(0,191,176,.1);border-radius:6px;padding:32px">
      <div style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid rgba(0,191,176,.08)">Change Password</div>

      <form method="POST" action="{{ route('website.account.change-password') }}">
        @csrf
        @method('PATCH')

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px">
          <div>
            <label class="sg-label">Current Password</label>
            <input type="password" name="current_password" class="sg-input" placeholder="Current password">
          </div>
          <div>
            <label class="sg-label">New Password</label>
            <input type="password" name="password" class="sg-input" placeholder="Min. 8 characters">
          </div>
          <div>
            <label class="sg-label">Confirm New Password</label>
            <input type="password" name="password_confirmation" class="sg-input" placeholder="Repeat new password">
          </div>
        </div>

        <button type="submit" class="sg-btn-outline">Update Password</button>
      </form>
    </div>

  </div>
</div>

@push('head_styles')
<style>
.sg-label{display:block;font-size:11px;font-weight:500;letter-spacing:1.5px;text-transform:uppercase;color:var(--white-dim);margin-bottom:8px}
.sg-input{width:100%;background:var(--dark-750);border:1px solid rgba(0,191,176,.2);border-radius:3px;color:var(--white);font-family:'Jost',sans-serif;font-size:14px;padding:11px 14px;outline:none;transition:border .3s;display:block}
.sg-input:focus{border-color:var(--teal-400)}
</style>
@endpush
@endsection
